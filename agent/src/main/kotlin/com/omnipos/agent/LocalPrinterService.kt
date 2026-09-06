package com.omnipos.agent

import java.io.File
import java.io.FileOutputStream
import java.util.concurrent.CompletableFuture
import java.util.concurrent.TimeUnit
import java.util.concurrent.TimeoutException
import javax.print.DocFlavor
import javax.print.PrintServiceLookup
import javax.print.SimpleDoc
import javax.print.attribute.standard.PrinterState
import kotlinx.serialization.Serializable
import kotlinx.serialization.json.Json
import kotlinx.serialization.json.jsonArray
import kotlinx.serialization.json.jsonObject
import kotlinx.serialization.json.jsonPrimitive
import org.slf4j.LoggerFactory

@Serializable
data class PrinterInfo(
    val name: String,
    val status: String,
    val defaultPrinter: Boolean,
    val type: String = "SPOOLER",
    val port: String? = null,
)

@Serializable
data class BluetoothDeviceInfo(
    val name: String,
    val status: String,
    val instanceId: String,
    val port: String? = null,
    val isPrinter: Boolean = false,
)

@Serializable
data class SerialPortInfo(
    val port: String,
    val name: String,
    val pnpDeviceId: String = "",
    val isBluetooth: Boolean = false,
)

@Serializable
data class DeviceSummary(
    val printers: List<PrinterInfo>,
    val bluetoothDevices: List<BluetoothDeviceInfo>,
    val serialPorts: List<SerialPortInfo>,
)

private val logger = LoggerFactory.getLogger("LocalPrinterService")
private val jsonParser = Json { ignoreUnknownKeys = true }

/**
 * Servicio de periféricos e impresoras Windows (Spooler, Bluetooth SPP y Puertos COM).
 */
open class LocalPrinterService {

    @Volatile
    private var cachedDevices: DeviceSummary? = null
    @Volatile
    private var lastScanTime: Long = 0L
    private val cacheTtlMs = 6000L

    init {
        Thread {
            runCatching { scanAndCache() }
        }.apply {
            isDaemon = true
            name = "OmniPOS-HardwareScanner"
            start()
        }
    }

    /**
     * Lista todas las impresoras disponibles (Spooler de Windows y dispositivos térmicos Bluetooth/Serial).
     */
    open fun list(): List<PrinterInfo> = runCatching {
        val summary = getDeviceSummary(forceRefresh = false)
        summary.printers
    }.getOrElse { error ->
        logger.warn("Error al listar impresoras: {}", error.message)
        listSpoolerPrintersOnly()
    }

    /**
     * Resumen exhaustivo de periféricos: Impresoras Spooler, dispositivos Bluetooth y puertos seriales.
     */
    open fun listDevices(): DeviceSummary {
        return getDeviceSummary(forceRefresh = true)
    }

    private fun scanAndCache(): DeviceSummary {
        val now = System.currentTimeMillis()
        val spoolerPrinters = listSpoolerPrintersOnly()
        val (bluetoothDevices, serialPorts) = scanHardwareDevices()

        val combinedPrinters = spoolerPrinters.toMutableList()

        // 1. Identificar impresoras entre dispositivos Bluetooth
        bluetoothDevices.filter { it.isPrinter }.forEach { bt ->
            val printerPort = bt.port
            val displayName = if (printerPort != null) "${bt.name} ($printerPort)" else "${bt.name} (Bluetooth)"
            if (combinedPrinters.none { it.name.contains(bt.name, ignoreCase = true) }) {
                combinedPrinters.add(
                    PrinterInfo(
                        name = displayName,
                        status = bt.status,
                        defaultPrinter = false,
                        type = "BLUETOOTH",
                        port = printerPort,
                    ),
                )
            }
        }

        // 2. Agregar puertos COM bluetooth como impresoras directas si no se mapearon
        serialPorts.forEach { port ->
            val alreadyMapped = combinedPrinters.any { it.port.equals(port.port, ignoreCase = true) }
            if (!alreadyMapped) {
                combinedPrinters.add(
                    PrinterInfo(
                        name = "${port.port} - ${port.name}",
                        status = "AVAILABLE",
                        defaultPrinter = false,
                        type = if (port.isBluetooth) "BLUETOOTH" else "SERIAL",
                        port = port.port,
                    ),
                )
            }
        }

        val summary = DeviceSummary(
            printers = combinedPrinters.sortedWith(compareByDescending<PrinterInfo> { it.defaultPrinter }.thenBy { it.name.lowercase() }),
            bluetoothDevices = bluetoothDevices,
            serialPorts = serialPorts,
        )

        cachedDevices = summary
        lastScanTime = now
        return summary
    }

    /**
     * Obtiene el resumen de dispositivos combinando Java Print Service y detección de hardware PnP/COM.
     */
    @Synchronized
    open fun getDeviceSummary(forceRefresh: Boolean = false): DeviceSummary {
        val now = System.currentTimeMillis()
        if (cachedDevices != null && (!forceRefresh || (now - lastScanTime) < cacheTtlMs)) {
            return cachedDevices!!
        }
        return scanAndCache()
    }

    /** Envía un texto de prueba plano al dispositivo seleccionado. */
    open fun testPrint(printerName: String?, message: String): Result<String> = runCatching {
        printRaw(printerName, (message + "\n").toByteArray(Charsets.UTF_8)).getOrThrow()
    }

    /**
     * Envía bytes sin procesar (raw ESC/POS) a la impresora seleccionada:
     * - Si el nombre corresponde o contiene un puerto COM (ej. 'COM6', '2C-P58-C (COM6)'), escribe directo al puerto.
     * - Si es una impresora Spooler de Windows, la envía a través de Java Print Service.
     */
    open fun printRaw(printerName: String?, payload: ByteArray): Result<String> = runCatching {
        val target = printerName?.trim().orEmpty()

        // 1. Detectar si apunta a un puerto serial / Bluetooth COM
        val comMatch = Regex("""\b(COM\d+)\b""", RegexOption.IGNORE_CASE).find(target)
        if (comMatch != null) {
            val port = comMatch.value.uppercase()
            return@runCatching printToSerialPort(port, payload).getOrThrow()
        }

        // 2. Si no es COM, imprimir mediante Java Print Service (Spooler)
        val services = PrintServiceLookup.lookupPrintServices(null, null).toList()
        val service = if (target.isBlank()) {
            PrintServiceLookup.lookupDefaultPrintService()
        } else {
            services.firstOrNull { it.name.equals(target, ignoreCase = true) }
                ?: services.firstOrNull { it.name.contains(target, ignoreCase = true) }
        }

        if (service == null) {
            val dev = getDeviceSummary(forceRefresh = false).printers.firstOrNull {
                it.name.contains(target, ignoreCase = true)
            }
            if (dev?.port != null) {
                return@runCatching printToSerialPort(dev.port, payload).getOrThrow()
            }
            throw NoSuchElementException("No se encontró la impresora local solicitada: '$target'")
        }

        val flavor = DocFlavor("application/octet-stream", "[B")
        val doc = SimpleDoc(payload, flavor, null)
        service.createPrintJob().print(doc, null)
        service.name
    }

    /**
     * Escribe bytes crudos directamente al puerto serial o Bluetooth virtual COM en Windows (\\\\.\\COMx).
     */
    open fun printToSerialPort(port: String, payload: ByteArray): Result<String> = runCatching {
        val cleanPort = port.uppercase().trim()
        val portPath = if (cleanPort.startsWith("""\\.\""")) cleanPort else """\\.\$cleanPort"""
        try {
            FileOutputStream(File(portPath)).use { fos ->
                fos.write(payload)
                fos.flush()
            }
            logger.info("Impresión enviada exitosamente al puerto {}", cleanPort)
            cleanPort
        } catch (e: Exception) {
            logger.error("Error al escribir en el puerto {}: {}", cleanPort, e.message)
            throw IllegalStateException("No se pudo comunicar con el puerto $cleanPort: ${e.message}")
        }
    }

    /** Envía el pulso estándar ESC/POS para abrir gaveta de dinero conectada a la impresora (pin 2 / 5). */
    open fun openDrawer(printerName: String?): Result<String> =
        printRaw(
            printerName,
            byteArrayOf(0x1B.toByte(), 0x70.toByte(), 0x00.toByte(), 0x19.toByte(), 0xFA.toByte()),
        )

    private fun listSpoolerPrintersOnly(): List<PrinterInfo> = runCatching {
        val defaultName = PrintServiceLookup.lookupDefaultPrintService()?.name
        PrintServiceLookup.lookupPrintServices(null, null)
            .map { service ->
                PrinterInfo(
                    name = service.name,
                    status = statusOf(service.getAttribute(PrinterState::class.java)),
                    defaultPrinter = service.name == defaultName,
                    type = "SPOOLER",
                    port = null,
                )
            }
    }.getOrElse { emptyList() }

    private fun statusOf(state: PrinterState?): String = when (state) {
        PrinterState.IDLE, PrinterState.PROCESSING -> "AVAILABLE"
        PrinterState.STOPPED -> "ERROR"
        else -> "UNKNOWN"
    }

    /**
     * Ejecuta una consulta ligera de PowerShell para enumerar dispositivos Bluetooth emparejados y puertos COM.
     */
    private fun scanHardwareDevices(): Pair<List<BluetoothDeviceInfo>, List<SerialPortInfo>> {
        val isWindows = System.getProperty("os.name")?.contains("Windows", ignoreCase = true) == true
        if (!isWindows) {
            return Pair(emptyList(), emptyList())
        }

        return runCatching {
            val psCommand = """
                & {
                    ${'$'}ports = @(Get-CimInstance -ClassName Win32_SerialPort -ErrorAction SilentlyContinue | Where-Object { ${'$'}_.Status -eq 'OK' } | Select-Object DeviceID, Name, Description, PNPDeviceID)
                    ${'$'}bt = @(Get-PnpDevice -Class Bluetooth -PresentOnly -ErrorAction SilentlyContinue | Select-Object FriendlyName, InstanceId, Status)
                    [PSCustomObject]@{ ports = ${'$'}ports; bluetooth = ${'$'}bt } | ConvertTo-Json -Compress
                }
            """.trimIndent()

            val process = ProcessBuilder("powershell.exe", "-NoLogo", "-NoProfile", "-NonInteractive", "-ExecutionPolicy", "Bypass", "-Command", psCommand)
                .redirectErrorStream(true)
                .start()

            val future = CompletableFuture.supplyAsync {
                process.inputStream.bufferedReader().use { it.readText().trim() }
            }

            val output = try {
                future.get(20, TimeUnit.SECONDS)
            } catch (_: TimeoutException) {
                process.destroyForcibly()
                return Pair(emptyList(), emptyList())
            } catch (_: Exception) {
                process.destroyForcibly()
                return Pair(emptyList(), emptyList())
            }

            process.waitFor(2, TimeUnit.SECONDS)

            if (output.isBlank() || !output.startsWith("{")) {
                return Pair(emptyList(), emptyList())
            }

            parseHardwareJson(output)
        }.getOrElse { error ->
            logger.warn("Excepción al escanear hardware Bluetooth/COM: {}", error.message)
            Pair(emptyList(), emptyList())
        }
    }

    private fun parseHardwareJson(rawJson: String): Pair<List<BluetoothDeviceInfo>, List<SerialPortInfo>> {
        val root = jsonParser.parseToJsonElement(rawJson).jsonObject

        val serialPorts = mutableListOf<SerialPortInfo>()
        val portsElement = root["ports"]
        val portsArray = when {
            portsElement == null -> emptyList()
            portsElement is kotlinx.serialization.json.JsonArray -> portsElement.jsonArray
            portsElement is kotlinx.serialization.json.JsonObject -> listOf(portsElement)
            else -> emptyList()
        }

        portsArray.forEach { elem ->
            val obj = elem.jsonObject
            val deviceId = obj["DeviceID"]?.jsonPrimitive?.content.orEmpty()
            val name = obj["Name"]?.jsonPrimitive?.content ?: deviceId
            val pnpDeviceId = obj["PNPDeviceID"]?.jsonPrimitive?.content.orEmpty()
            val isBt = name.contains("Bluetooth", ignoreCase = true) ||
                pnpDeviceId.contains("BTHENUM", ignoreCase = true)
            if (deviceId.isNotBlank()) {
                serialPorts.add(SerialPortInfo(port = deviceId, name = name, pnpDeviceId = pnpDeviceId, isBluetooth = isBt))
            }
        }

        val bluetoothDevices = mutableListOf<BluetoothDeviceInfo>()
        val btElement = root["bluetooth"]
        val btArray = when {
            btElement == null -> emptyList()
            btElement is kotlinx.serialization.json.JsonArray -> btElement.jsonArray
            btElement is kotlinx.serialization.json.JsonObject -> listOf(btElement)
            else -> emptyList()
        }

        val btSerialPorts = serialPorts.filter { it.isBluetooth }

        btArray.forEach { elem ->
            val obj = elem.jsonObject
            val friendlyName = obj["FriendlyName"]?.jsonPrimitive?.content.orEmpty()
            val instanceId = obj["InstanceId"]?.jsonPrimitive?.content.orEmpty()
            val status = obj["Status"]?.jsonPrimitive?.content ?: "OK"

            if (friendlyName.isNotBlank()) {
                val isPrinter = isPrinterDevice(friendlyName)
                val deviceAddress = Regex("""(?:DEV_|_)?([0-9A-F]{12})\b""", RegexOption.IGNORE_CASE)
                    .find(instanceId)?.groupValues?.getOrNull(1)?.uppercase()
                val associatedPort = if (isPrinter) {
                    val matchedByAddress = if (deviceAddress != null) {
                        btSerialPorts.firstOrNull { port ->
                            val portAddress = Regex("""(?:DEV_|_|&)?([0-9A-F]{12})\b""", RegexOption.IGNORE_CASE)
                                .find(port.pnpDeviceId.ifBlank { port.name })?.groupValues?.getOrNull(1)?.uppercase()
                            (portAddress != null && portAddress == deviceAddress) ||
                                port.pnpDeviceId.contains(deviceAddress, ignoreCase = true)
                        }?.port
                    } else null

                    // No asignar ciegamente si hay más de un puerto Bluetooth disponible
                    matchedByAddress ?: if (btSerialPorts.size == 1) btSerialPorts.first().port else null
                } else null

                bluetoothDevices.add(
                    BluetoothDeviceInfo(
                        name = friendlyName,
                        status = status,
                        instanceId = instanceId,
                        port = associatedPort,
                        isPrinter = isPrinter,
                    ),
                )
            }
        }

        return Pair(bluetoothDevices, serialPorts)
    }

    private fun isPrinterDevice(name: String): Boolean {
        val lower = name.lowercase()
        if (lower.contains("identificaci") || lower.contains("servicio de") || lower.contains("transporte")) return false
        return lower.contains("p58") ||
            lower.contains("pos") ||
            lower.contains("print") ||
            lower.contains("pt-210") ||
            lower.contains("mpt") ||
            lower.contains("58mm") ||
            lower.contains("80mm") ||
            lower.contains("thermal") ||
            lower.contains("receipt") ||
            lower.contains("rpp") ||
            lower.contains("zebra") ||
            lower.contains("epson")
    }
}
