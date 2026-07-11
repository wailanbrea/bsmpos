import { expect, test } from '@playwright/test';
import { loginAsDemo } from './support/session';

test.describe('Venta POS fiscal', () => {
    test('abre caja, cobra en efectivo, emite B02 y descuenta inventario', async ({ page }) => {
        await loginAsDemo(page);
        await page.getByRole('navigation').getByRole('link', { name: 'POS' }).click();

        await expect(page.getByRole('button', { name: 'Abrir Caja e Iniciar Ventas' })).toBeVisible();
        await page.getByText('Fondo Inicial DOP').locator('..').getByRole('spinbutton').fill('200');
        await page.getByRole('button', { name: 'Abrir Caja e Iniciar Ventas' }).click();

        await expect(page.getByText('OMNIPOS · Caja Demo')).toBeVisible();
        await page.getByRole('button', { name: /Café Santo Domingo 8 oz/ }).click();

        const cart = page.getByLabel('Carrito de compra');
        await expect(cart.getByText('Café Santo Domingo 8 oz')).toBeVisible();
        await expect(cart.getByText('Total General:').locator('..').getByText('RD$ 118.00')).toBeVisible();

        await page.getByRole('button', { name: /Completar Venta/ }).click();
        const paymentDialog = page.getByRole('dialog').filter({
            has: page.getByRole('heading', { name: 'Detalles del Cobro' }),
        });

        await expect(paymentDialog).toBeVisible();
        await paymentDialog.getByText('Monto Entregado').locator('..').getByRole('spinbutton').fill('200');
        await expect(paymentDialog.getByText('RD$ 82.00')).toBeVisible();

        const invoiceResponse = page.waitForResponse(
            (response) => response.url().includes('/api/v1/invoices/from-order') && response.status() === 201,
        );
        await paymentDialog.getByRole('button', { name: 'Confirmar e Imprimir' }).click();

        const invoicePayload = (await invoiceResponse).json() as Promise<{
            data: { ncf: string; total: string };
        }>;
        await expect.poll(async () => (await invoicePayload).data.ncf).toBe('B0200000001');
        await expect.poll(async () => (await invoicePayload).data.total).toBe('118.00');

        const ticketDialog = page.getByRole('dialog').filter({
            has: page.getByRole('heading', { name: 'Comprobante Emitido' }),
        });
        await expect(ticketDialog).toBeVisible();
        await expect(ticketDialog.getByText('B0200000001', { exact: true })).toBeVisible();
        await expect(
            ticketDialog.getByText('TOTAL:', { exact: true }).locator('..').getByText('RD$ 118.00'),
        ).toBeVisible();
        await ticketDialog.getByRole('button', { name: 'Cerrar' }).click();

        await page.getByRole('button', { name: /Cerrar Caja/ }).click();
        const closeDialog = page.getByRole('dialog').filter({
            has: page.getByRole('heading', { name: 'Cierre de Caja y Arqueo' }),
        });
        await closeDialog
            .getByText('Efectivo Contado Físicamente (DOP)')
            .locator('..')
            .getByRole('spinbutton')
            .fill('318');
        await closeDialog.getByRole('button', { name: 'Confirmar Cierre de Caja' }).click();
        await expect(page.getByText('Caja cerrada y arqueada. Revisa los resultados del arqueo.')).toBeVisible();

        await page.goto('/inventario');
        const stockRow = page.getByRole('row').filter({ hasText: 'Café Santo Domingo 8 oz' });
        await expect(stockRow).toContainText('24.00');
    });

    test('acepta tarjeta DOP y efectivo USD a tasa visible', async ({ page }) => {
        await loginAsDemo(page);
        await page.getByRole('navigation').getByRole('link', { name: 'POS' }).click();

        await page.getByText('Fondo Inicial DOP').locator('..').getByRole('spinbutton').fill('200');
        await page.getByRole('button', { name: 'Abrir Caja e Iniciar Ventas' }).click();
        await page.getByRole('button', { name: /Chocolate Barra 100 g/ }).click();
        await page.getByRole('button', { name: /Completar Venta/ }).click();

        const paymentDialog = page.getByRole('dialog').filter({
            has: page.getByRole('heading', { name: 'Detalles del Cobro' }),
        });
        await paymentDialog.getByLabel('Método de pago 1').selectOption('card');
        await paymentDialog.getByLabel('Monto de pago 1').fill('58');
        await paymentDialog.getByLabel('Referencia de pago 1').fill('E2E-CARD-001');
        await paymentDialog.getByRole('button', { name: /Agregar Pago/ }).click();
        await paymentDialog.getByLabel('Moneda de pago 2').selectOption('USD');
        await paymentDialog.getByLabel('Monto de pago 2').fill('1');

        await expect(paymentDialog.getByText('Tasa: DOP 60')).toBeVisible();
        await expect(
            paymentDialog.getByText('Total entregado (DOP equiv):').locator('..').getByText('RD$ 118.00'),
        ).toBeVisible();

        const orderRequest = page.waitForRequest(
            (request) => request.url().includes('/api/v1/orders') && request.method() === 'POST',
        );
        const invoiceResponse = page.waitForResponse(
            (response) => response.url().includes('/api/v1/invoices/from-order') && response.status() === 201,
        );
        await paymentDialog.getByRole('button', { name: 'Confirmar e Imprimir' }).click();

        const orderPayload = (await orderRequest).postDataJSON() as {
            payments: Array<{
                payment_method_code: string;
                currency_code: string;
                exchange_rate: number;
                amount: number;
                reference?: string;
            }>;
        };
        expect(orderPayload.payments).toEqual([
            {
                payment_method_code: 'card',
                currency_code: 'DOP',
                exchange_rate: 1,
                amount: 58,
                reference: 'E2E-CARD-001',
            },
            { payment_method_code: 'cash', currency_code: 'USD', exchange_rate: 60, amount: 1 },
        ]);

        const invoicePayload = (await invoiceResponse).json() as Promise<{ data: { ncf: string; total: string } }>;
        await expect.poll(async () => (await invoicePayload).data.ncf).toMatch(/^B02\d{8}$/);
        await expect.poll(async () => (await invoicePayload).data.total).toBe('118.00');

        const ticketDialog = page.getByRole('dialog').filter({
            has: page.getByRole('heading', { name: 'Comprobante Emitido' }),
        });
        await expect(
            ticketDialog.getByText('TOTAL:', { exact: true }).locator('..').getByText('RD$ 118.00'),
        ).toBeVisible();
        await ticketDialog.getByRole('button', { name: 'Cerrar' }).click();

        await page.goto('/inventario');
        const stockRow = page.getByRole('row').filter({ hasText: 'Chocolate Barra 100 g' });
        await expect(stockRow).toContainText('24.00');
    });
});
