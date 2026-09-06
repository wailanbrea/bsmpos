package com.omnipos.agent

import io.ktor.client.request.get
import io.ktor.client.request.post
import io.ktor.client.statement.bodyAsText
import io.ktor.http.HttpStatusCode
import io.ktor.server.testing.testApplication
import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertNull
import kotlin.test.assertTrue

class ApplicationTest {
    @Test
    fun testStatusEndpoint() = testApplication {
        application {
            agentModule(
                AgentConfig(
                    host = "127.0.0.1",
                    port = 8765,
                    version = "0.1.0",
                ),
            )
        }
        val response = client.get("/api/status")
        assertEquals(HttpStatusCode.OK, response.status)
        assertTrue(response.bodyAsText().contains("OmniPOS Windows Agent"))
        assertTrue(response.bodyAsText().contains("0.1.0"))
    }

    @Test
    fun testConfigEndpoint() = testApplication {
        application {
            agentModule(
                AgentConfig(
                    host = "127.0.0.1",
                    port = 8765,
                    version = "0.1.0",
                    terminalId = "TEST-TERM-01",
                ),
            )
        }
        val response = client.get("/api/config")
        assertEquals(HttpStatusCode.OK, response.status)
        assertTrue(response.bodyAsText().contains("TEST-TERM-01"))
        assertTrue(response.bodyAsText().contains("80"))
    }

    @Test
    fun testPrintersEndpoint() = testApplication {
        application {
            agentModule(
                AgentConfig(
                    host = "127.0.0.1",
                    port = 8765,
                    version = "0.1.0",
                ),
            )
        }
        val response = client.get("/api/printers")
        assertEquals(HttpStatusCode.OK, response.status)
        assertTrue(response.bodyAsText().startsWith("["))
    }

    @Test
    fun testDevicesEndpoint() = testApplication {
        application {
            agentModule(
                AgentConfig(
                    host = "127.0.0.1",
                    port = 8765,
                    version = "0.1.0",
                ),
            )
        }
        val response = client.get("/api/devices")
        assertEquals(HttpStatusCode.OK, response.status)
        val text = response.bodyAsText()
        assertTrue(text.contains("printers"))
        assertTrue(text.contains("bluetoothDevices"))
        assertTrue(text.contains("serialPorts"))
    }

    @Test
    fun testEnableBluetoothSppEndpoint() = testApplication {
        application {
            agentModule(
                AgentConfig(
                    host = "127.0.0.1",
                    port = 8765,
                    version = "0.1.0",
                ),
            )
        }
        val response = client.post("/api/devices/bluetooth/enable-spp")
        assertEquals(HttpStatusCode.OK, response.status)
        val text = response.bodyAsText()
        assertTrue(text.contains("status"))
        assertTrue(text.contains("devices"))
    }

    @Test
    fun serialPortNamesAreNormalizedToWindowsDevicePaths() {
        assertEquals("COM4", normalizeSerialPortName("2C-P58-C (COM4)"))
        assertEquals("COM12", normalizeSerialPortName("\\\\.\\COM12"))
        assertNull(normalizeSerialPortName("Bluetooth printer without port"))
        assertEquals("\\\\.\\COM4", serialDevicePath("COM4"))
    }

    @Test
    fun hardwareParserAssociatesBluetoothPrinterWithItsSerialPort() {
        val (bluetooth, serial) = LocalPrinterService().parseHardwareJson(
            """
            {
              "ports": {
                "DeviceID": "COM4",
                "Name": "Standard Serial over Bluetooth link (COM4)",
                "PNPDeviceID": "BTHENUM\\DEV_001122334455",
                "Status": "OK"
              },
              "bluetooth": {
                "FriendlyName": "2C-P58-C",
                "InstanceId": "BTHENUM\\DEV_001122334455",
                "Status": "OK"
              }
            }
            """.trimIndent(),
        )

        assertEquals("COM4", serial.single().port)
        assertEquals("COM4", bluetooth.single().port)
    }
}
