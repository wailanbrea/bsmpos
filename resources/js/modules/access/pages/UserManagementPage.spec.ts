import { flushPromises, mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import UserManagementPage from './UserManagementPage.vue';

const apiMock = vi.hoisted(() => ({
    get: vi.fn(),
    post: vi.fn(),
    patch: vi.fn(),
}));

vi.mock('../../../lib/api', () => ({
    api: apiMock,
}));

describe('UserManagementPage', () => {
    it('loads user access and requires a branch before assigning access', async () => {
        apiMock.get.mockImplementation((url: string) => {
            const responses: Record<string, unknown> = {
                '/users': [
                    {
                        id: '01JUSER000000000000000000',
                        name: 'Cajero Uno',
                        email: 'cajero@example.test',
                        phone: null,
                        is_active: true,
                        is_owner: false,
                        default_branch_id: '01JBRANCH000000000000000',
                        branches: [],
                        roles: [],
                    },
                ],
                '/branches': [
                    {
                        id: '01JBRANCH000000000000000',
                        name: 'Principal',
                        code: 'PRINCIPAL',
                        is_active: true,
                    },
                ],
                '/roles': [],
            };

            return Promise.resolve({ data: { data: responses[url] } });
        });

        const wrapper = mount(UserManagementPage, { global: { stubs: { RouterLink: true } } });
        await flushPromises();

        expect(wrapper.text()).toContain('Cajero Uno');
        await wrapper.get('form').trigger('submit');
        expect(wrapper.text()).toContain('Asigna al menos una sucursal');
        expect(apiMock.post).not.toHaveBeenCalled();
    });
});
