import { expect, test } from '@playwright/test';
import { loginAsDemo } from './support/session';

/**
 * Vertical Barbería / Salón: agenda una cita con un empleado y un servicio,
 * verifica el total con ITBIS (300 + 18% = 354) y avanza su estado.
 */
test.describe('Barbería — agenda de citas', () => {
    test('agenda una cita con total con ITBIS y avanza su estado', async ({ page }) => {
        await loginAsDemo(page);
        await page.getByRole('navigation').getByRole('link', { name: /Agenda/ }).click();
        await expect(page.getByRole('heading', { name: 'Agenda de citas' })).toBeVisible();

        const panel = page.locator('aside');
        await panel.getByLabel('Empleado').selectOption({ label: 'Pedro Técnico' });
        await panel.getByRole('checkbox', { name: /Servicio general/ }).check();
        await expect(panel.getByText('Subtotal servicios: RD$ 300.00')).toBeVisible();

        await panel.getByRole('button', { name: 'Agendar cita' }).click();

        const item = page.getByRole('listitem').filter({ hasText: 'Servicio general' });
        await expect(item).toContainText('Pedro Técnico');
        await expect(item).toContainText('RD$ 354.00');
        await expect(item).toContainText('Pendiente');

        // Transición de estado pendiente → confirmada.
        await item.getByRole('button', { name: 'Confirmada' }).click();
        await expect(item).toContainText('Confirmada');
    });
});
