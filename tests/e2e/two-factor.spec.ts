import { expect, test } from '@playwright/test';
import { DEMO_USER } from './support/env';
import { loginAsDemo, selectContext, submitLogin } from './support/session';
import { totpCode } from './support/totp';

/**
 * Flujo E2E del segundo factor (la característica insignia de la Fase 14):
 * activar 2FA en /seguridad → reto en el login → acceso con código TOTP.
 * Se desactiva al final para dejar limpio al usuario demo.
 */
test.describe('Verificación en dos pasos (2FA)', () => {
    test('activar, retar en login y desactivar', async ({ page }) => {
        await loginAsDemo(page);

        // 1. Activar en la pantalla de Seguridad.
        await page.goto('/seguridad');
        await expect(page.getByText('Inactiva', { exact: true })).toBeVisible();
        await page.getByRole('button', { name: 'Activar 2FA' }).click();

        // La clave de configuración se muestra agrupada de 4 en 4; la normalizamos.
        const groupedSecret = await page.locator('p.font-mono.tracking-wider').first().innerText();
        const secret = groupedSecret.replace(/\s+/g, '');
        expect(secret.length).toBeGreaterThanOrEqual(16);

        await page.getByRole('textbox').first().fill(totpCode(secret));
        await page.getByRole('button', { name: 'Confirmar y activar' }).click();
        await expect(page.getByText('Activa', { exact: true })).toBeVisible();

        // 2. Cerrar sesión y comprobar el reto de segundo factor en el login.
        await page.goto('/seleccionar-contexto');
        await page.getByRole('button', { name: 'Cerrar sesión' }).click();
        await expect(page).toHaveURL(/ingresar/);

        await submitLogin(page, DEMO_USER.email, DEMO_USER.password);
        await expect(page.getByRole('alert')).toContainText(/código/i);
        await expect(page.getByLabel('Código de verificación (2FA)')).toBeVisible();

        // 3. Acceso con un código TOTP válido.
        await page.getByLabel('Código de verificación (2FA)').fill(totpCode(secret));
        await page.getByRole('button', { name: 'Iniciar sesión' }).click();
        await expect(page).toHaveURL(/seleccionar-contexto/);
        await selectContext(page);

        // 4. Desactivar para dejar el usuario demo sin 2FA.
        await page.goto('/seguridad');
        await expect(page.getByText('Activa', { exact: true })).toBeVisible();
        await page.getByLabel('Código').fill(totpCode(secret));
        await page.getByRole('button', { name: 'Desactivar 2FA' }).click();
        await expect(page.getByText('Inactiva', { exact: true })).toBeVisible();
    });
});
