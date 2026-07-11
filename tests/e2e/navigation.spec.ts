import { expect, test } from '@playwright/test';
import { loginAsDemo } from './support/session';

test.describe('Navegación y sistema de módulos', () => {
    test.beforeEach(async ({ page }) => {
        await loginAsDemo(page);
    });

    test('el menú muestra los módulos activos del giro minimarket', async ({ page }) => {
        const nav = page.getByRole('navigation', { name: 'Navegación principal' });
        await expect(nav.getByRole('link', { name: 'POS' })).toBeVisible();
        await expect(nav.getByRole('link', { name: 'Productos' })).toBeVisible();
        await expect(nav.getByRole('link', { name: 'Inventario' })).toBeVisible();
        await expect(nav.getByRole('link', { name: 'Reportes' })).toBeVisible();
        await expect(nav.getByRole('link', { name: 'Seguridad' })).toBeVisible();
    });

    test('un módulo no activado no aparece en el menú', async ({ page }) => {
        const nav = page.getByRole('navigation', { name: 'Navegación principal' });
        await expect(nav.getByRole('link', { name: 'Facturación electrónica' })).toHaveCount(0);
    });

    test('navegar a Reportes carga la pantalla', async ({ page }) => {
        await page.getByRole('navigation').getByRole('link', { name: 'Reportes' }).click();
        await expect(page).toHaveURL(/reportes/);
        await expect(page.getByRole('heading', { name: /Reportes/ })).toBeVisible();
    });

    test('navegar a Configuración carga la pantalla fiscal', async ({ page }) => {
        await page.getByRole('navigation').getByRole('link', { name: 'Configuración' }).click();
        await expect(page).toHaveURL(/configuracion\/fiscal/);
    });

    test('el POS es accesible con el módulo activo', async ({ page }) => {
        await page.getByRole('navigation').getByRole('link', { name: 'POS' }).click();
        await expect(page).toHaveURL(/pos/);
    });

    test('una ruta de módulo desactivado redirige al panel', async ({ page }) => {
        // electronic_invoice no está activo para el giro minimarket.
        await page.goto('/facturacion-electronica');
        await expect(page).toHaveURL(/\/$/);
        await expect(page.getByRole('heading', { name: 'Panel de control' })).toBeVisible();
    });
});
