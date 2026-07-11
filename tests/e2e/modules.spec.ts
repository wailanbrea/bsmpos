import { expect, test } from '@playwright/test';
import { loginAsDemo } from './support/session';

/**
 * Premisa central del SaaS: los módulos se activan/desactivan por empresa sin
 * borrar datos, y el núcleo no se puede desactivar. Se togglea "Código de
 * barras" (activo en el giro minimarket y sin dependientes) y se restablece,
 * dejando el estado del demo intacto.
 */
test.describe('Activación de módulos', () => {
    test.beforeEach(async ({ page }) => {
        await loginAsDemo(page);
        await page.getByRole('navigation').getByRole('link', { name: 'Módulos' }).click();
        await expect(page.getByRole('heading', { name: 'Módulos del sistema' })).toBeVisible();
    });

    test('desactiva y reactiva un módulo opcional', async ({ page }) => {
        const toggle = page.getByRole('switch', { name: 'Desactivar Código de barras' });
        await expect(toggle).toBeVisible();
        await expect(toggle).toHaveAttribute('aria-checked', 'true');

        // Desactivar: el switch pasa a "no marcado" y su etiqueta cambia a Activar.
        await toggle.click();
        const reactivate = page.getByRole('switch', { name: 'Activar Código de barras' });
        await expect(reactivate).toHaveAttribute('aria-checked', 'false');

        // Reactivar para restablecer el estado del demo.
        await reactivate.click();
        await expect(page.getByRole('switch', { name: 'Desactivar Código de barras' })).toHaveAttribute(
            'aria-checked',
            'true',
        );
    });

    test('un módulo del núcleo no se puede desactivar', async ({ page }) => {
        const coreItem = page.getByRole('listitem').filter({ hasText: 'Núcleo' }).first();
        await expect(coreItem.getByRole('switch')).toBeDisabled();
    });
});
