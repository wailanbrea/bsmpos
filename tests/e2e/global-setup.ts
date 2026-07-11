import { execFileSync } from 'node:child_process';
import { rmSync, writeFileSync } from 'node:fs';
import { APP_ENV_VARS, E2E_DATABASE, phpBinary } from './support/env';

/**
 * Prepara una base de datos SQLite limpia antes de la corrida E2E:
 * migra desde cero, siembra catálogos (DatabaseSeeder) y crea el negocio demo
 * determinista (DemoSeeder). Se ejecuta una vez por `npx playwright test`.
 */
export default function globalSetup(): void {
    const php = phpBinary();
    const env = { ...process.env, ...APP_ENV_VARS };

    // Base de datos fresca y vacía para cada corrida.
    rmSync(E2E_DATABASE, { force: true });
    writeFileSync(E2E_DATABASE, '');

    const run = (args: string[]): void => {
        execFileSync(php, ['artisan', ...args], { env, stdio: 'inherit' });
    };

    run(['migrate:fresh', '--seed', '--force']);
    run(['db:seed', '--class=DemoSeeder', '--force']);
    run(['config:clear']);
}
