import { expect, type Page } from '@playwright/test';
import { DEMO_USER } from './env';

/** Rellena el formulario de acceso y envía. No selecciona contexto. */
export async function submitLogin(page: Page, email: string, password: string, code?: string): Promise<void> {
    await page.goto('/ingresar');
    await page.getByLabel('Correo electrónico').fill(email);
    await page.getByLabel('Contraseña').fill(password);
    if (code !== undefined) {
        await page.getByLabel('Código de verificación (2FA)').fill(code);
    }
    await page.getByRole('button', { name: 'Iniciar sesión' }).click();
}

/** Selecciona la compañía/sucursal precargadas y entra al panel. */
export async function selectContext(page: Page): Promise<void> {
    await expect(page.getByRole('heading', { name: /Selecciona dónde vas a operar/ })).toBeVisible();
    await page.getByRole('button', { name: 'Continuar al panel' }).click();
    await expect(page.getByRole('heading', { name: 'Panel de control' })).toBeVisible();
}

/** Flujo completo: acceso del usuario demo (sin 2FA) hasta el panel. */
export async function loginAsDemo(page: Page): Promise<void> {
    await submitLogin(page, DEMO_USER.email, DEMO_USER.password);
    await expect(page).toHaveURL(/seleccionar-contexto/);
    await selectContext(page);
}
