import { flushPromises, mount } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { ref } from 'vue';
import PosPage from './PosPage.vue';

const mocks = vi.hoisted(() => ({ get: vi.fn(), print: vi.fn() }));
vi.mock('../../../lib/api', () => ({ api: { get: mocks.get }, storageKeys: {} }));
vi.mock('../../../composables/useAgentStatus', () => ({
    useAgentStatus: () => ({ state: ref('connected'), version: ref('test'), checkNow: vi.fn() }),
}));
vi.mock('../../../composables/useAgentPrinter', () => ({
    printWithAgent: mocks.print,
    getAgentPrinters: async () => [],
    openDrawerWithAgent: vi.fn(),
}));
vi.mock('../services', () => ({
    PosService: { getActiveCashSession: async () => null, getCashRegisters: async () => [] },
    consumeExternalOrderBridge: () => null,
}));
vi.mock('../indexeddb', () => ({
    IndexedDBService: { getCart: async () => [], getOfflineOrders: async () => [], saveCart: vi.fn() },
}));
vi.mock('../../products/services', () => ({ fetchProducts: async () => [] }));
vi.mock('../../settings/services', () => ({ fetchExchangeRates: async () => [] }));

describe('POS printing route', () => {
    let wrapper: ReturnType<typeof mount>;
    beforeEach(async () => {
        localStorage.clear();
        localStorage.setItem('pos_selected_printer', '2C-P58-C (COM4)');
        mocks.get.mockReset().mockResolvedValue({ data: { data: [] } });
        mocks.print.mockReset().mockResolvedValue({ ok: false, status: 'printer_error', message: 'Puerto ocupado' });
        wrapper = mount(PosPage);
        await flushPromises();
        const state = (wrapper.vm.$ as unknown as { setupState: Record<string, unknown> }).setupState;
        state.invoiceResult = { id: 'test-invoice', items: [], total: '0.00' };
        state.showInvoicePrintModal = true;
        mocks.get.mockClear().mockResolvedValue({ data: { data: { content: 'TICKET DE PRUEBA' } } });
        await flushPromises();
    });
    afterEach(() => wrapper.unmount());

    it('shows the agent failure in the invoice dialog without requesting browser HTML', async () => {
        await wrapper
            .findAll('button')
            .find((button) => button.text().includes('Imprimir Ticket'))!
            .trigger('click');
        await flushPromises();
        expect(wrapper.get('[role="dialog"] [role="alert"]').text()).toContain('Puerto ocupado');
        expect(mocks.print).toHaveBeenCalledWith('TICKET DE PRUEBA', '2C-P58-C (COM4)');
        expect(mocks.get).toHaveBeenCalledTimes(1);
        expect(document.getElementById('print-iframe')).toBeNull();
    });

    it('keeps the selected printer route when the status monitor is disconnected', async () => {
        (wrapper.vm.$ as unknown as { setupState: Record<string, unknown> }).setupState.agentState = 'disconnected';
        await wrapper
            .findAll('button')
            .find((button) => button.text().includes('Imprimir Ticket'))!
            .trigger('click');
        await flushPromises();
        expect(mocks.print).toHaveBeenCalledOnce();
        expect(mocks.get).toHaveBeenCalledTimes(1);
    });

    it('reports ticket generation failures without opening browser printing', async () => {
        mocks.get.mockRejectedValue({ response: { data: { error: { message: 'Sin permiso para imprimir' } } } });
        await wrapper
            .findAll('button')
            .find((button) => button.text().includes('Imprimir Ticket'))!
            .trigger('click');
        await flushPromises();
        expect(wrapper.get('[role="dialog"] [role="alert"]').text()).toContain('Sin permiso');
        expect(mocks.print).not.toHaveBeenCalled();
        expect(mocks.get).toHaveBeenCalledTimes(1);
    });

    it('sends a successful ticket exactly once while a previous click is pending', async () => {
        let finish!: (value: { ok: boolean }) => void;
        mocks.print.mockImplementation(
            () =>
                new Promise((resolve) => {
                    finish = resolve;
                }),
        );
        const button = wrapper.findAll('button').find((candidate) => candidate.text().includes('Imprimir Ticket'))!;
        await button.trigger('click');
        await flushPromises();
        expect(button.attributes('disabled')).toBeDefined();
        await button.trigger('click');
        expect(mocks.print).toHaveBeenCalledOnce();
        finish({ ok: true });
        await flushPromises();
        expect(wrapper.find('[role="dialog"] [role="alert"]').exists()).toBe(false);
        expect(button.attributes('disabled')).toBeUndefined();
        expect(mocks.get).toHaveBeenCalledTimes(1);
    });
});
