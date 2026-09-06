import { expect, test } from '@playwright/test';
import { loginAsDemo } from './support/session';

/**
 * Regla dura de existencias: el POS no debe permitir vender un producto
 * inventariable sin stock. "Botellón de Agua 20 L" se siembra a propósito con
 * cero existencias; el backend rechaza la orden (400 "Stock insuficiente") y la
 * interfaz muestra el error sin emitir comprobante.
 */
test.describe('Venta POS sin stock', () => {
    test('bloquea la venta de un producto inventariable agotado', async ({ page }) => {
        await loginAsDemo(page);
        await page.getByRole('navigation').getByRole('link', { name: 'POS' }).click();

        await page.getByText('Fondo Inicial DOP').locator('..').getByRole('spinbutton').fill('200');
        await page.getByRole('button', { name: 'Abrir Caja e Iniciar Ventas' }).click();

        await expect(page.getByText('BSM-POS · Caja Demo')).toBeVisible();
        await page.getByRole('button', { name: /Botellón de Agua 20 L/ }).click();
        await page.getByRole('button', { name: /Completar Venta/ }).click();

        const paymentDialog = page.getByRole('dialog').filter({
            has: page.getByRole('heading', { name: 'Detalles del Cobro' }),
        });
        await paymentDialog.getByText('Monto Entregado').locator('..').getByRole('spinbutton').fill('200');

        const orderResponse = page.waitForResponse(
            (response) => response.url().includes('/api/v1/orders') && response.request().method() === 'POST',
        );
        await paymentDialog.getByRole('button', { name: 'Confirmar e Imprimir' }).click();

        // El backend rechaza la orden por falta de existencias.
        expect((await orderResponse).status()).toBe(400);

        // La interfaz muestra el error y NO emite comprobante fiscal.
        await expect(page.getByText(/Stock insuficiente/i)).toBeVisible();
        await expect(page.getByRole('heading', { name: 'Comprobante Emitido' })).toHaveCount(0);

        // Cerrar el cobro y arquear la caja para no dejar el turno abierto
        // (una sola "Caja Demo" compartida entre escenarios).
        await paymentDialog.getByRole('button', { name: /Cerrar|✕/ }).click();
        await page.getByRole('button', { name: /Cerrar Caja/ }).click();
        const closeDialog = page.getByRole('dialog').filter({
            has: page.getByRole('heading', { name: 'Cierre de Caja y Arqueo' }),
        });
        await closeDialog
            .getByText('Efectivo Contado Físicamente (DOP)')
            .locator('..')
            .getByRole('spinbutton')
            .fill('200');
        await closeDialog.getByRole('button', { name: 'Confirmar Cierre de Caja' }).click();
        await expect(page.getByText('Caja cerrada y arqueada. Revisa los resultados del arqueo.')).toBeVisible();
    });
});
