package com.omnipos.agent

import io.ktor.client.request.get
import io.ktor.client.statement.bodyAsText
import io.ktor.http.HttpStatusCode
import io.ktor.server.testing.testApplication
import kotlin.test.Test
import kotlin.test.assertEquals
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
}
