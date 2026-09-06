package com.omnipos.agent

import io.ktor.server.config.ApplicationConfig

/** Configuración de ejecución para el agente de hardware local de OmniPOS. */
data class AgentConfig(
    val host: String,
    val port: Int,
    val version: String,
    val terminalToken: String = "",
    val allowedOrigins: Set<String> = DEFAULT_ALLOWED_ORIGINS,
    val rateLimitPerMinute: Int = DEFAULT_RATE_LIMIT_PER_MINUTE,
    val printerName: String? = null,
    val drawerEnabled: Boolean = true,
    val paperWidthMm: Int = 80,
    val encoding: String = "UTF-8",
    val terminalId: String = "OMNIPOS-TERMINAL",
) {
    init {
        require(host.isNotBlank()) { "agent.host no puede estar vacío" }
        require(port in 1..65_535) { "agent.port debe estar entre 1 y 65535" }
        require(version.isNotBlank()) { "agent.version no puede estar vacío" }
        require(rateLimitPerMinute > 0) { "agent.security.rate_limit_per_minute debe ser mayor a cero" }
        require(paperWidthMm in SUPPORTED_PAPER_WIDTHS) { "agent.paper_width_mm debe ser 58 o 80" }
        require(encoding.equals("UTF-8", ignoreCase = true) || encoding.equals("windows-1252", ignoreCase = true)) {
            "agent.encoding debe ser UTF-8 o windows-1252"
        }
        require(terminalId.matches(TERMINAL_ID_PATTERN)) {
            "agent.terminal_id debe contener de 1 a 64 caracteres alfanuméricos, puntos, guiones o guiones bajos"
        }
    }

    companion object {
        fun from(config: ApplicationConfig): AgentConfig = AgentConfig(
            host = config.propertyOrNull("agent.host")?.getString() ?: DEFAULT_HOST,
            port = config.propertyOrNull("agent.port")?.getString()?.toIntOrNull() ?: DEFAULT_PORT,
            version = config.propertyOrNull("agent.version")?.getString() ?: DEFAULT_VERSION,
            terminalToken = config.propertyOrNull("agent.security.token")?.getString()?.trim()
                ?.ifEmpty { System.getenv("AGENT_TERMINAL_TOKEN")?.trim().orEmpty() }
                ?: System.getenv("AGENT_TERMINAL_TOKEN")?.trim().orEmpty(),
            allowedOrigins = config.propertyOrNull("agent.security.allowed_origins")?.getList()
                ?.map { it.trim().lowercase().trimEnd('/') }
                ?.filter { it.isNotEmpty() }
                ?.toSet()
                ?.ifEmpty { DEFAULT_ALLOWED_ORIGINS }
                ?: DEFAULT_ALLOWED_ORIGINS,
            rateLimitPerMinute = config.propertyOrNull("agent.security.rate_limit_per_minute")?.getString()?.toIntOrNull()
                ?: DEFAULT_RATE_LIMIT_PER_MINUTE,
            printerName = config.propertyOrNull("agent.printer.name")?.getString()?.trim()?.ifBlank { null },
            drawerEnabled = config.propertyOrNull("agent.drawer.enabled")?.getString()?.trim()?.let {
                when (it.lowercase()) {
                    "true" -> true
                    "false" -> false
                    else -> throw IllegalArgumentException("agent.drawer.enabled debe ser true o false")
                }
            } ?: true,
            paperWidthMm = config.propertyOrNull("agent.paper_width_mm")?.getString()?.toIntOrNull() ?: 80,
            encoding = config.propertyOrNull("agent.encoding")?.getString()?.trim()?.ifBlank { "UTF-8" } ?: "UTF-8",
            terminalId = config.propertyOrNull("agent.terminal_id")?.getString()?.trim()?.ifBlank { "OMNIPOS-TERMINAL" }
                ?: "OMNIPOS-TERMINAL",
        )

        const val DEFAULT_HOST = "127.0.0.1"
        const val DEFAULT_PORT = 8765
        const val DEFAULT_VERSION = "0.1.0"
        const val DEFAULT_RATE_LIMIT_PER_MINUTE = 120
        val SUPPORTED_PAPER_WIDTHS = setOf(58, 80)
        val TERMINAL_ID_PATTERN = Regex("[A-Za-z0-9._:-]{1,64}")
        val DEFAULT_ALLOWED_ORIGINS = setOf(
            "http://127.0.0.1:8001",
            "http://localhost:8001",
            "http://127.0.0.1:8000",
            "http://localhost:8000",
            "http://127.0.0.1:5173",
            "http://localhost:5173",
        )
    }
}
