import { defineConfig, devices } from '@playwright/test';
import { APP_ENV_VARS, BASE_URL, HOST, phpBinary, PORT } from './tests/e2e/support/env';

const phpServerCommand =
    process.platform === 'win32'
        ? `powershell -NoProfile -Command "& '${phpBinary()}' -S ${HOST}:${PORT} -t public"`
        : `${phpBinary()} -S ${HOST}:${PORT} -t public`;

/**
 * Suite E2E de OmniPOS. Levanta un servidor Laravel real contra una base
 * SQLite aislada (ver global-setup) y ejecuta los flujos críticos en Chromium.
 *
 * Comandos:
 *   npm run test:e2e         corrida headless (CI)
 *   npm run test:e2e:ui      modo interactivo para depurar
 */
export default defineConfig({
    testDir: './tests/e2e',
    testMatch: '**/*.spec.ts',
    globalSetup: './tests/e2e/global-setup.ts',
    fullyParallel: false,
    workers: 1,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 1 : 0,
    reporter: process.env.CI ? [['github'], ['html', { open: 'never' }]] : [['list']],
    timeout: 30_000,
    expect: { timeout: 10_000 },
    use: {
        baseURL: BASE_URL,
        locale: 'es-DO',
        trace: 'on-first-retry',
        screenshot: 'only-on-failure',
    },
    projects: [{ name: 'chromium', use: { ...devices['Desktop Chrome'] } }],
    webServer: {
        command: phpServerCommand,
        url: BASE_URL,
        env: APP_ENV_VARS,
        // Un servidor reutilizado puede apuntar a una base SQLite E2E previa.
        // Se permite solo cuando se solicita de forma explícita para depuración.
        reuseExistingServer: process.env.PLAYWRIGHT_REUSE_SERVER === 'true',
        timeout: 120_000,
    },
});
