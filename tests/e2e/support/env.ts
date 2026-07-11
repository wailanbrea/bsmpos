import { existsSync } from 'node:fs';
import { resolve } from 'node:path';

/** Puerto y URL del servidor Laravel que Playwright levanta para las pruebas E2E. */
export const HOST = '127.0.0.1';
export const PORT = 8123;
export const BASE_URL = `http://${HOST}:${PORT}`;

/** Base de datos SQLite aislada para E2E (se recrea en cada corrida). */
export const E2E_DATABASE = resolve(process.cwd(), 'database/e2e.sqlite');

/**
 * Binario de PHP. En CI `php` está en el PATH; en local usamos el PHP 8.3
 * portátil del repo si existe. Se puede forzar con la variable PHP_BINARY.
 */
export function phpBinary(): string {
    if (process.env.PHP_BINARY) {
        return process.env.PHP_BINARY;
    }

    const portable = resolve(process.cwd(), '.tools/php83/php.exe');

    return existsSync(portable) ? portable : 'php';
}

/**
 * Variables de entorno que aíslan la app para E2E: SQLite dedicado, colas
 * síncronas, sin correos reales y una APP_KEY fija de pruebas (NO producción).
 */
export const APP_ENV_VARS: Record<string, string> = {
    APP_ENV: 'testing',
    APP_DEBUG: 'true',
    APP_KEY: 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=',
    APP_URL: BASE_URL,
    DB_CONNECTION: 'sqlite',
    DB_DATABASE: E2E_DATABASE,
    // La suite reutiliza el mismo usuario/IP en varios escenarios. Cache efímero
    // evita que el rate limiter de un caso contamine el siguiente; los límites
    // se cubren por separado en las pruebas backend.
    CACHE_STORE: 'array',
    SESSION_DRIVER: 'file',
    QUEUE_CONNECTION: 'sync',
    MAIL_MAILER: 'log',
};

/** Credenciales del usuario demo sembrado por DemoSeeder. */
export const DEMO_USER = {
    email: 'demo@omnipos.test',
    password: 'Password123!',
} as const;
