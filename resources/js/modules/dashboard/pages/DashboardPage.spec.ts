import { mount } from '@vue/test-utils';
import { createPinia } from 'pinia';
import { describe, expect, it } from 'vitest';
import DashboardPage from './DashboardPage.vue';

describe('DashboardPage', () => {
    it('shows the operational status rail', () => {
        const wrapper = mount(DashboardPage, {
            global: {
                plugins: [createPinia()],
                stubs: { RouterLink: true },
            },
        });

        expect(wrapper.get('[aria-label="Estado operativo"]').text()).toContain('Conexión');
        expect(wrapper.text()).toContain('Configura tu empresa para empezar a vender.');
    });
});
