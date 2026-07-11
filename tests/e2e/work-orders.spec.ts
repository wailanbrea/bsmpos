import { expect, test } from '@playwright/test';
import { loginAsDemo } from './support/session';

/**
 * Vertical Taller mecánico: registra un vehículo, crea una orden de trabajo
 * con un servicio + mano de obra (300 + 18% + 500 = 854) y avanza su estado.
 */
test.describe('Taller — vehículos y órdenes de trabajo', () => {
    test('registra un vehículo, crea una orden y avanza su estado', async ({ page }) => {
        await loginAsDemo(page);

        // 1. Registrar un vehículo para el cliente genérico.
        await page.getByRole('navigation').getByRole('link', { name: /Vehículos/ }).click();
        await expect(page.getByRole('heading', { name: 'Vehículos' })).toBeVisible();

        const vehicleForm = page.locator('aside');
        await vehicleForm.getByLabel('Cliente').selectOption({ label: 'Consumidor Final' });
        await vehicleForm.getByLabel('Marca').fill('Toyota');
        await vehicleForm.getByLabel('Modelo').fill('Corolla');
        await vehicleForm.getByLabel('Placa').fill('A123456');
        await vehicleForm.getByRole('button', { name: 'Guardar' }).click();

        await expect(page.getByRole('listitem').filter({ hasText: 'Toyota Corolla' })).toBeVisible();

        // 2. Crear una orden de trabajo para ese vehículo.
        await page.goto('/ordenes-trabajo');
        await expect(page.getByRole('heading', { name: 'Órdenes de trabajo' })).toBeVisible();

        const orderForm = page.locator('aside');
        await orderForm.getByLabel('Vehículo').selectOption({ index: 1 });
        await orderForm.getByLabel('Diagnóstico').fill('Cambio de aceite y filtro');
        await orderForm.getByRole('checkbox', { name: /Servicio general/ }).check();
        await orderForm.getByLabel('Mano de obra (RD$)').fill('500');
        await orderForm.getByRole('button', { name: 'Crear orden' }).click();

        // servicios 300 + 18% = 354 ; mano de obra 500 => 854.00
        const order = page.getByRole('listitem').filter({ hasText: 'Toyota Corolla' });
        await expect(order).toContainText('RD$ 854.00');
        await expect(order).toContainText('Recibida');

        // Transición de estado recibida → diagnosticando.
        await order.getByRole('button', { name: 'Diagnosticando' }).click();
        await expect(order).toContainText('Diagnosticando');
    });
});
