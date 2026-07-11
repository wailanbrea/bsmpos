import { expect, test } from '@playwright/test';
import { DEMO_USER } from './support/env';
import { loginAsDemo, selectContext, submitLogin } from './support/session';

test.describe('Acceso y contexto', () => {
    test('la pantalla de acceso se muestra', async ({ page }) => {
        await page.goto('/ingresar');
        await expect(page.getByRole('heading', { name: /Entra a tu operación/ })).toBeVisible();
        await expect(page.getByRole('button', { name: 'Iniciar sesión' })).toBeVisible();
    });

    test('credenciales inválidas muestran un error y no autentican', async ({ page }) => {
        await submitLogin(page, DEMO_USER.email, 'clave-incorrecta');
        await expect(page.getByRole('alert')).toBeVisible();
        await expect(page).toHaveURL(/ingresar/);
    });

    test('acceso válido lleva a selección de contexto y al panel', async ({ page }) => {
        await submitLogin(page, DEMO_USER.email, DEMO_USER.password);
        await expect(page).toHaveURL(/seleccionar-contexto/);
        await selectContext(page);
        await expect(page).toHaveURL(/\/$/);
    });

    test('cerrar sesión regresa al acceso y protege el panel', async ({ page }) => {
        await loginAsDemo(page);
        await page.goto('/seleccionar-contexto');
        await page.getByRole('button', { name: 'Cerrar sesión' }).click();
        await expect(page).toHaveURL(/ingresar/);

        // Sin sesión, el panel redirige de vuelta al acceso.
        await page.goto('/');
        await expect(page).toHaveURL(/ingresar/);
    });
});
