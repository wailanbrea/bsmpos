package com.omnipos.agent

import io.ktor.http.HttpMethod
import io.ktor.server.application.ApplicationCall
import java.nio.charset.StandardCharsets
import java.security.MessageDigest
import java.time.Clock
import javax.crypto.Mac
import javax.crypto.spec.SecretKeySpec

private const val TOKEN_HEADER = "X-Agent-Token"
private const val TIMESTAMP_HEADER = "X-Agent-Timestamp"
private const val SIGNATURE_HEADER = "X-Agent-Signature"
private const val SIGNATURE_ALGORITHM = "HmacSHA256"
private const val SIGNATURE_WINDOW_SECONDS = 300L
private val SECURITY_LOOPBACK_HOSTS = setOf("127.0.0.1", "::1", "0:0:0:0:0:0:0:1", "localhost")

enum class SecurityDecision {
    ALLOWED,
    FORBIDDEN,
    RATE_LIMITED,
    UNAUTHORIZED,
}

/** Comprobaciones de seguridad para el agente local de hardware. */
class AgentSecurity(
    private val config: AgentConfig,
    private val clock: Clock = Clock.systemUTC(),
) {
    private val requestCounts = mutableMapOf<String, RequestWindow>()
    private val trustedProxyHosts = System.getenv("AGENT_TRUSTED_PROXY_HOSTS")
        ?.split(',', ';', ' ', '\t', '\r', '\n')
        ?.map { it.trim().lowercase() }
        ?.filter { it.isNotEmpty() }
        ?.toSet()
        ?: emptySet()

    fun check(call: ApplicationCall): SecurityDecision {
        val remoteHost = call.request.local.remoteHost.lowercase()
        if ((!isLoopbackHost(remoteHost) && remoteHost !in trustedProxyHosts) ||
            !isAllowedOrigin(call.request.headers["Origin"])
        ) {
            return SecurityDecision.FORBIDDEN
        }
        val origin = call.request.headers["Origin"] ?: "-"
        if (!allowRequest("$remoteHost|$origin")) {
            return SecurityDecision.RATE_LIMITED
        }
        if (config.terminalToken.isNotBlank() && !isAuthorized(call)) {
            return SecurityDecision.UNAUTHORIZED
        }
        return SecurityDecision.ALLOWED
    }

    fun isAllowedOrigin(origin: String?): Boolean {
        if (origin == null) {
            return true
        }
        return config.allowedOrigins.contains(origin.trim().lowercase().trimEnd('/'))
    }

    fun allowRequest(key: String): Boolean {
        val now = clock.millis()
        val windowStart = now - 60_000L
        synchronized(requestCounts) {
            requestCounts.entries.removeIf { it.value.startedAt < windowStart }
            val window = requestCounts.getOrPut(key) { RequestWindow(now, 0) }
            if (window.startedAt < windowStart) {
                window.startedAt = now
                window.count = 0
            }
            if (window.count >= config.rateLimitPerMinute) {
                return false
            }
            window.count += 1
            return true
        }
    }

    fun isAuthorized(call: ApplicationCall): Boolean {
        if (config.terminalToken.isBlank()) {
            return false
        }
        val timestamp = call.request.headers[TIMESTAMP_HEADER]?.toLongOrNull() ?: return false
        val now = clock.instant().epochSecond
        if (kotlin.math.abs(now - timestamp) > SIGNATURE_WINDOW_SECONDS) {
            return false
        }
        val suppliedToken = call.request.headers[TOKEN_HEADER] ?: return false
        if (!MessageDigest.isEqual(
                suppliedToken.toByteArray(StandardCharsets.UTF_8),
                config.terminalToken.toByteArray(StandardCharsets.UTF_8),
            )
        ) {
            return false
        }
        val suppliedSignature = call.request.headers[SIGNATURE_HEADER] ?: return false
        val expectedSignature = sign(config.terminalToken, call.request.local.method, call.request.local.uri, timestamp)
        return MessageDigest.isEqual(
            suppliedSignature.toByteArray(StandardCharsets.UTF_8),
            expectedSignature.toByteArray(StandardCharsets.UTF_8),
        )
    }

    companion object {
        fun sign(token: String, method: HttpMethod, path: String, timestamp: Long): String {
            val payload = "${method.value}\n$path\n$timestamp"
            val mac = Mac.getInstance(SIGNATURE_ALGORITHM)
            mac.init(SecretKeySpec(token.toByteArray(StandardCharsets.UTF_8), SIGNATURE_ALGORITHM))
            return mac.doFinal(payload.toByteArray(StandardCharsets.UTF_8)).joinToString("") { "%02x".format(it) }
        }
    }

    private data class RequestWindow(var startedAt: Long, var count: Int)

    private fun isLoopbackHost(host: String): Boolean {
        if (host in SECURITY_LOOPBACK_HOSTS || host == "kubernetes.docker.internal" || host.endsWith(".internal") || host.endsWith(".local")) {
            return true
        }
        return runCatching { java.net.InetAddress.getByName(host).isLoopbackAddress }.getOrDefault(false)
    }
}
