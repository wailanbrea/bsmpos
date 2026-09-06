package com.omnipos.agent

import io.ktor.server.application.Application
import io.ktor.server.application.ApplicationCall
import io.ktor.server.application.ApplicationCallPipeline
import io.ktor.server.application.install
import io.ktor.server.netty.EngineMain
import io.ktor.server.plugins.contentnegotiation.ContentNegotiation
import io.ktor.server.plugins.calllogging.CallLogging
import io.ktor.server.plugins.cors.routing.CORS
import io.ktor.http.HttpHeaders
import io.ktor.http.HttpMethod
import io.ktor.serialization.kotlinx.json.json
import io.ktor.server.request.receive
import io.ktor.server.response.respond
import io.ktor.server.routing.get
import io.ktor.server.routing.options
import io.ktor.server.routing.post
import io.ktor.server.routing.routing
import io.ktor.http.HttpStatusCode
import java.nio.charset.Charset
import kotlinx.serialization.Serializable
import org.slf4j.LoggerFactory
import java.net.URI

private val logger = LoggerFactory.getLogger("OmniPOSAgent")

@Serializable
data class AgentStatusResponse(
    val status: String,
    val agent: String,
    val version: String,
    val host: String,
    val port: Int,
)

@Serializable
data class PrinterTestRequest(
    val printer: String? = null,
    val message: String = "OmniPOS Windows Agent - Prueba de impresora",
)

@Serializable
data class PrinterTestResponse(
    val status: String,
    val printer: String,
    val message: String,
)

@Serializable
data class PrintTicketRequest(
    val printer: String? = null,
    val content: String,
)

@Serializable
data class PrintTicketResponse(
    val status: String,
    val printer: String,
    val message: String,
)

@Serializable
data class DrawerOpenRequest(
    val printer: String? = null,
)

@Serializable
data class DrawerOpenResponse(
    val status: String,
    val printer: String,
    val message: String,
)

@Serializable
data class TerminalConfigResponse(
    val printer: String?,
    val drawerEnabled: Boolean,
    val paperWidthMm: Int,
    val encoding: String,
    val terminalId: String,
)

@Serializable
data class EnableBluetoothSppRequest(
    val target: String? = null,
)

@Serializable
data class EnableBluetoothSppResponse(
    val status: String,
    val success: Boolean,
    val message: String,
    val devices: DeviceSummary,
)

private object EscPos {
    private val init = byteArrayOf(0x1B.toByte(), 0x40.toByte())
    private val lineFeed = byteArrayOf(0x0A.toByte(), 0x0A.toByte(), 0x0A.toByte())
    private val cut = byteArrayOf(0x1D.toByte(), 0x56.toByte(), 0x00.toByte())

    fun encode(content: String, encoding: String): ByteArray {
        val normalized = content.replace("\r\n", "\n").replace('\r', '\n')
        return init + normalized.toByteArray(Charset.forName(encoding)) + lineFeed + cut
    }
}

fun main(args: Array<String>): Unit = EngineMain.main(args)

fun Application.module() {
    agentModule(AgentConfig.from(environment.config))
}

fun Application.agentModule(config: AgentConfig, printerService: LocalPrinterService = LocalPrinterService()) {
    val security = AgentSecurity(config)
    install(CallLogging)
    intercept(ApplicationCallPipeline.Setup) {
        context.response.headers.append("Access-Control-Allow-Private-Network", "true")
    }
    install(CORS) {
        if ("*" in config.allowedOrigins) {
            anyHost()
        } else {
            config.allowedOrigins.forEach { rawOrigin ->
                runCatching { URI(rawOrigin) }.getOrNull()?.let { origin ->
                    val host = origin.host ?: return@let
                    val hostWithPort = if (origin.port > 0) "$host:${origin.port}" else host
                    val schemes = origin.scheme?.let(::listOf) ?: emptyList()
                    allowHost(hostWithPort, schemes = schemes)
                }
            }
        }
        allowMethod(HttpMethod.Get)
        allowMethod(HttpMethod.Post)
        allowMethod(HttpMethod.Options)
        allowHeader(HttpHeaders.ContentType)
        allowHeader(HttpHeaders.Accept)
        allowHeader("X-Agent-Token")
        allowHeader("X-Agent-Timestamp")
        allowHeader("X-Agent-Signature")
        allowHeader("Access-Control-Request-Private-Network")
        allowHeader("access-control-request-private-network")
    }
    install(ContentNegotiation) {
        json()
    }
    routing {
        options("{...}") {
            call.response.headers.append("Access-Control-Allow-Private-Network", "true")
            call.respond(HttpStatusCode.OK)
        }
        get("/api/status") {
            if (!call.enforce(security)) return@get
            call.respond(AgentStatusResponse("ok", "OmniPOS Windows Agent", config.version, config.host, config.port))
        }
        get("/api/config") {
            if (!call.enforce(security)) return@get
            call.respond(
                TerminalConfigResponse(
                    printer = config.printerName,
                    drawerEnabled = config.drawerEnabled,
                    paperWidthMm = config.paperWidthMm,
                    encoding = config.encoding,
                    terminalId = config.terminalId,
                ),
            )
        }
        get("/api/printers") {
            if (!call.enforce(security)) return@get
            call.respond(printerService.list())
        }
        get("/api/devices") {
            if (!call.enforce(security)) return@get
            call.respond(printerService.listDevices())
        }
        post("/api/devices/bluetooth/enable-spp") {
            if (!call.enforce(security)) return@post
            val request = try {
                call.receive<EnableBluetoothSppRequest>()
            } catch (_: Exception) {
                EnableBluetoothSppRequest(null)
            }
            val ok = printerService.enableBluetoothSerialPort(request.target)
            val updatedDevices = printerService.getDeviceSummary(forceRefresh = true)
            call.respond(
                EnableBluetoothSppResponse(
                    status = if (ok) "ok" else "failed",
                    success = ok,
                    message = if (ok) "Servicio SPP Bluetooth verificado/habilitado correctamente." else "No se pudo habilitar el servicio SPP o no se encontró el dispositivo Bluetooth.",
                    devices = updatedDevices,
                ),
            )
        }
        post("/api/test") {
            if (!call.enforce(security)) return@post

            val request = try {
                call.receive<PrinterTestRequest>()
            } catch (_: Exception) {
                call.respond(
                    HttpStatusCode.BadRequest,
                    PrinterTestResponse("invalid_request", "", "El cuerpo debe ser JSON válido."),
                )
                return@post
            }

            val printer = request.printer?.trim()?.ifBlank { null } ?: config.printerName
            val message = request.message.trim()
            if (message.isBlank() || message.length > 500 ||
                (printer != null && (printer.isBlank() || printer.length > 200))) {
                call.respond(
                    HttpStatusCode.BadRequest,
                    PrinterTestResponse("invalid_request", printer.orEmpty(), "Texto o impresora no válidos."),
                )
                return@post
            }

            printerService.testPrint(printer, message).fold(
                onSuccess = { selectedPrinter ->
                    call.respond(
                        PrinterTestResponse("printed", selectedPrinter, "Prueba enviada correctamente a la impresora."),
                    )
                },
                onFailure = { error ->
                    if (error is NoSuchElementException) {
                        call.respond(
                            HttpStatusCode.NotFound,
                            PrinterTestResponse("printer_not_found", printer.orEmpty(), "No se encontró la impresora local."),
                        )
                    } else {
                        logger.error("Error al imprimir prueba local: {}", error.message)
                        call.respond(
                            HttpStatusCode.ServiceUnavailable,
                            PrinterTestResponse("printer_error", printer.orEmpty(), error.message ?: "La impresora no está disponible."),
                        )
                    }
                },
            )
        }
        post("/api/print") {
            if (!call.enforce(security)) return@post

            val request = try {
                call.receive<PrintTicketRequest>()
            } catch (_: Exception) {
                call.respond(
                    HttpStatusCode.BadRequest,
                    PrintTicketResponse("invalid_request", "", "El cuerpo debe ser JSON válido."),
                )
                return@post
            }

            val printer = request.printer?.trim()?.ifBlank { null } ?: config.printerName
            val content = request.content.trim()
            if (content.isBlank() || content.length > 8_000 ||
                (printer != null && (printer.isBlank() || printer.length > 200))) {
                call.respond(
                    HttpStatusCode.BadRequest,
                    PrintTicketResponse("invalid_request", printer.orEmpty(), "Contenido o impresora no válidos."),
                )
                return@post
            }

            printerService.printRaw(printer, EscPos.encode(content, config.encoding)).fold(
                onSuccess = { selectedPrinter ->
                    call.respond(
                        PrintTicketResponse("printed", selectedPrinter, "Ticket enviado correctamente."),
                    )
                },
                onFailure = { error ->
                    if (error is NoSuchElementException) {
                        call.respond(
                            HttpStatusCode.NotFound,
                            PrintTicketResponse("printer_not_found", printer.orEmpty(), "No se encontró la impresora local."),
                        )
                    } else {
                        logger.error("Error al imprimir ticket: {}", error.message)
                        call.respond(
                            HttpStatusCode.ServiceUnavailable,
                            PrintTicketResponse("printer_error", printer.orEmpty(), error.message ?: "La impresora no está disponible."),
                        )
                    }
                },
            )
        }
        post("/api/drawer/open") {
            if (!call.enforce(security)) return@post
            if (config.terminalToken.isNotBlank() && !security.isAuthorized(call)) {
                call.respond(
                    HttpStatusCode.Unauthorized,
                    DrawerOpenResponse("unauthorized", "", "La gaveta requiere autenticación de terminal válida."),
                )
                return@post
            }
            if (!config.drawerEnabled) {
                call.respond(
                    HttpStatusCode.Conflict,
                    DrawerOpenResponse("drawer_disabled", config.printerName.orEmpty(), "La gaveta está deshabilitada en esta terminal."),
                )
                return@post
            }

            val request = try {
                call.receive<DrawerOpenRequest>()
            } catch (_: Exception) {
                call.respond(
                    HttpStatusCode.BadRequest,
                    DrawerOpenResponse("invalid_request", "", "El cuerpo debe ser JSON válido."),
                )
                return@post
            }

            val printer = request.printer?.trim()?.ifBlank { null } ?: config.printerName
            if (printer != null && (printer.isBlank() || printer.length > 200)) {
                call.respond(
                    HttpStatusCode.BadRequest,
                    DrawerOpenResponse("invalid_request", printer.orEmpty(), "Impresora no válida."),
                )
                return@post
            }

            printerService.openDrawer(printer).fold(
                onSuccess = { selectedPrinter ->
                    logger.info("Pulso de apertura de gaveta enviado a {}", selectedPrinter)
                    call.respond(
                        DrawerOpenResponse("opened", selectedPrinter, "Pulso de gaveta enviado correctamente."),
                    )
                },
                onFailure = { error ->
                    if (error is NoSuchElementException) {
                        logger.warn("No se encontró impresora para gaveta {}", printer.orEmpty())
                        call.respond(
                            HttpStatusCode.NotFound,
                            DrawerOpenResponse("printer_not_found", printer.orEmpty(), "No se encontró la impresora local."),
                        )
                    } else {
                        logger.error("Error al abrir gaveta: {}", error.message)
                        call.respond(
                            HttpStatusCode.ServiceUnavailable,
                            DrawerOpenResponse("drawer_error", printer.orEmpty(), "La gaveta no está disponible."),
                        )
                    }
                },
            )
        }
    }
    logger.info("OmniPOS Windows Agent {} escuchando en {}:{}", config.version, config.host, config.port)
}

private val LOOPBACK_HOSTS = setOf("127.0.0.1", "::1", "0:0:0:0:0:0:0:1", "localhost")

internal fun isLoopbackHost(host: String): Boolean = host.lowercase() in LOOPBACK_HOSTS

private suspend fun ApplicationCall.enforce(security: AgentSecurity): Boolean {
    return when (security.check(this)) {
        SecurityDecision.ALLOWED -> true
        SecurityDecision.FORBIDDEN -> {
            logger.warn("Rechazada solicitud al agente local desde {}", request.local.remoteHost)
            respond(HttpStatusCode.Forbidden)
            false
        }
        SecurityDecision.RATE_LIMITED -> {
            logger.warn("Límite de tasa excedido para {}", request.local.remoteHost)
            respond(HttpStatusCode.TooManyRequests)
            false
        }
        SecurityDecision.UNAUTHORIZED -> {
            logger.warn("Firma o token inválido desde {}", request.local.remoteHost)
            respond(HttpStatusCode.Unauthorized)
            false
        }
    }
}
