import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import DashboardPage from './DashboardPage.vue';

describe('DashboardPage', () => {
    it('shows the operational status rail', () => {
        const wrapper = mount(DashboardPage);

        expect(wrapper.get('[aria-label="Estado operativo"]').text()).toContain('Conexión');
        expect(wrapper.text()).toContain('Configura tu empresa para empezar a vender.');
    });
});
