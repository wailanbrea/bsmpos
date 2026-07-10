import { flushPromises, mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import BranchManagementPage from './BranchManagementPage.vue';

const apiMock = vi.hoisted(() => ({
    get: vi.fn(),
    post: vi.fn(),
    patch: vi.fn(),
}));

vi.mock('../../../lib/api', () => ({
    api: apiMock,
}));

describe('BranchManagementPage', () => {
    it('renders the main branch as active and prevents its deactivation in the form', async () => {
        apiMock.get.mockResolvedValue({
            data: {
                data: [
                    {
                        id: '01JBRANCH000000000000000',
                        name: 'Principal',
                        code: 'PRINCIPAL',
                        phone: null,
                        address: null,
                        is_main: true,
                        is_active: true,
                    },
                ],
            },
        });

        const wrapper = mount(BranchManagementPage, { global: { stubs: { RouterLink: true } } });
        await flushPromises();

        expect(wrapper.text()).toContain('Sucursal principal');
        const mainBranch = wrapper.findAll('section button').find((button) => button.text().includes('Principal'));
        await mainBranch?.trigger('click');
        expect(wrapper.get('input[type="checkbox"]').attributes('disabled')).toBeDefined();
    });
});
