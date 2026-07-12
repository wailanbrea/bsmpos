<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue';
import { PosService } from '../services';
import type { PosOrderItem, PosPayment, PosOrder } from '../services';
import { IndexedDBService } from '../indexeddb';
import { fetchProducts } from '../../products/services';
import { fetchExchangeRates } from '../../settings/services';
import type { Product } from '../../products/types';
import { api, storageKeys } from '../../../lib/api';

// Clave de contexto (compañía + sucursal) para aislar el carrito persistido:
// evita que el carrito de una empresa aparezca al operar otra.
function cartScope(): string {
    const company = localStorage.getItem(storageKeys.companyId) ?? 'none';
    const branch = localStorage.getItem(storageKeys.branchId) ?? 'none';
    return `${company}:${branch}`;
}

interface LocalCustomer {
    id: string;
    name: string;
    tax_id: string;
}

interface LocalWarehouse {
    id: string;
    name: string;
}

interface LocalTax {
    id: string;
    name: string;
    rate: number;
}

interface ActiveSessionData {
    id: string;
    register_name: string;
    register_code: string;
    opened_by: string;
    opening_amount: string | number;
    expected_amount: string | number;
    counted_amount: string | number | null;
    difference: string | number | null;
    status: string;
}

interface InvoiceLine {
    product_id: string;
    product_name: string;
    quantity: string | number;
    price: string | number;
    total: string | number;
    batch_number: string | null;
}

interface InvoiceResult {
    id: string;
    invoice_number: string;
    ncf: string | null;
    ncf_expires_at: string | null;
    document_type_code: string;
    created_at: string | null;
    customer_name: string | null;
    customer_rnc: string | null;
    branch_name: string | null;
    subtotal: string | number;
    discount_total: string | number;
    tax_total: string | number;
    tip_total: string | number;
    total: string | number;
    items: InvoiceLine[];
}

interface LocalRegister {
    id: string;
    name: string;
    code: string;
}

// Estado del POS y de Caja
const products = ref<Product[]>([]);
const customers = ref<LocalCustomer[]>([]);
const warehouses = ref<LocalWarehouse[]>([]);
const taxes = ref<LocalTax[]>([]);

const searchQuery = ref('');
const loading = ref(false);
const errorMsg = ref('');
const successMsg = ref('');
const isOnline = ref(window.navigator.onLine);

// Variables de Turno de Caja
const activeSession = ref<ActiveSessionData | null>(null);
const registers = ref<LocalRegister[]>([]);
const openRegisterId = ref('');
const openOpeningAmount = ref(0);

// Modales de Caja
const showMovementModal = ref(false);
const movementType = ref<'in' | 'out'>('in');
const movementAmount = ref(0);
const movementConcept = ref('');

const showCloseSessionModal = ref(false);
const closeCountedAmount = ref(0);
const lastClosedSummary = ref<ActiveSessionData | null>(null);

// Carrito de compras
const cart = ref<PosOrderItem[]>([]);

// Selección del Modal de Pago/Cobro
const showPayModal = ref(false);
const selectedCustomerId = ref('');
const selectedWarehouseId = ref('');
const applyTip = ref(false);
const orderNotes = ref('');
const orderStatus = ref<'pending' | 'completed'>('completed');
const documentTypeCode = ref('B02');
const invoiceResult = ref<InvoiceResult | null>(null);
const showInvoicePrintModal = ref(false);
const paperWidth = ref(window.localStorage.getItem('pos_paper_width') || '80mm');

watch(paperWidth, (val) => {
    window.localStorage.setItem('pos_paper_width', val);
});

// Gestión de Pagos Mixtos y Multimoneda
const paymentsList = ref<PosPayment[]>([
    { payment_method_code: 'cash', currency_code: 'DOP', exchange_rate: 1.0, amount: 0 },
]);
const exchangeRates = ref<Record<string, number>>({ DOP: 1 });

// Cola offline
const offlineQueueCount = ref(0);

async function loadData() {
    loading.value = true;
    errorMsg.value = '';
    try {
        // 1. Cargar turno de caja activo
        const sess = (await PosService.getActiveCashSession()) as ActiveSessionData | null;
        if (sess) {
            activeSession.value = sess;
        } else {
            // Cargar cajas físicas para apertura si no hay sesión activa
            const regs = (await PosService.getCashRegisters()) as LocalRegister[];
            registers.value = regs;
            if (regs.length > 0) {
                openRegisterId.value = regs[0].id;
            }
        }

        // Cargar productos. En giros de puros servicios (p. ej. barbería) el
        // módulo Productos puede estar apagado y el endpoint responde 403: eso
        // no debe tumbar el POS, solo deja la grilla vacía.
        try {
            products.value = await fetchProducts('');
        } catch {
            products.value = [];
        }

        // Cargar clientes
        const custRes = await api.get('/customers');
        customers.value = custRes.data.data.map((c: { id: string; name: string; tax_id?: string }) => ({
            id: c.id,
            name: c.name,
            tax_id: c.tax_id || '',
        }));
        if (customers.value.length > 0) {
            selectedCustomerId.value = customers.value[0].id;
        }

        // Cargar almacenes (requiere módulo Inventario; opcional para servicios).
        try {
            const warRes = await api.get('/warehouses');
            warehouses.value = warRes.data.data.map((w: { id: string; name: string }) => ({
                id: w.id,
                name: w.name,
            }));
            if (warehouses.value.length > 0) {
                selectedWarehouseId.value = warehouses.value[0].id;
            }
        } catch {
            warehouses.value = [];
        }

        // Cargar impuestos (el catálogo de lectura vive en /settings/fiscal)
        const taxRes = await api.get('/settings/fiscal');
        taxes.value = taxRes.data.data.taxes.map((t: { id: string; name: string; rate: number | string }) => ({
            id: t.id,
            name: t.name,
            rate: Number(t.rate),
        }));

        const today = new Date().toISOString().slice(0, 10);
        for (const rate of await fetchExchangeRates()) {
            if (rate.effective_date <= today && exchangeRates.value[rate.currency_code] === undefined) {
                exchangeRates.value[rate.currency_code] = Number(rate.rate);
            }
        }

        // Cargar carrito persistido
        cart.value = (await IndexedDBService.getCart(cartScope())) as PosOrderItem[];

        // Verificar cola offline
        await checkOfflineQueue();
    } catch {
        errorMsg.value = 'Error al inicializar el POS. Asegúrate de tener permisos.';
    } finally {
        loading.value = false;
    }
}

// Apertura de caja
async function handleOpenSession() {
    if (!openRegisterId.value) return;
    loading.value = true;
    errorMsg.value = '';
    try {
        const sess = (await PosService.openCashSession(
            openRegisterId.value,
            openOpeningAmount.value,
        )) as ActiveSessionData;
        activeSession.value = sess;
        successMsg.value = 'Turno de caja abierto correctamente.';
    } catch (err: unknown) {
        const error = err as { response?: { data?: { error?: { message?: string } } } };
        errorMsg.value = error.response?.data?.error?.message || 'Error al abrir la caja.';
    } finally {
        loading.value = false;
    }
}

// Movimiento de efectivo manual
async function handleCreateMovement() {
    if (movementAmount.value <= 0 || !movementConcept.value) return;
    loading.value = true;
    errorMsg.value = '';
    try {
        await PosService.createCashMovement(movementType.value, movementAmount.value, movementConcept.value);
        successMsg.value = 'Movimiento registrado con éxito.';

        // Recargar sesión activa para ver el balance esperado actualizado
        const sess = (await PosService.getActiveCashSession()) as ActiveSessionData | null;
        if (sess) activeSession.value = sess;

        showMovementModal.value = false;
        movementAmount.value = 0;
        movementConcept.value = '';
    } catch (err: unknown) {
        const error = err as { response?: { data?: { error?: { message?: string } } } };
        errorMsg.value = error.response?.data?.error?.message || 'Error al registrar el movimiento.';
    } finally {
        loading.value = false;
    }
}

// Cierre y arqueo de caja
async function handleCloseSession() {
    loading.value = true;
    errorMsg.value = '';
    try {
        const closed = (await PosService.closeCashSession(closeCountedAmount.value)) as ActiveSessionData;
        lastClosedSummary.value = closed;
        activeSession.value = null;
        showCloseSessionModal.value = false;

        // Recargar cajas para la próxima apertura
        const regs = (await PosService.getCashRegisters()) as LocalRegister[];
        registers.value = regs;
        if (regs.length > 0) {
            openRegisterId.value = regs[0].id;
        }

        successMsg.value = 'Caja cerrada y arqueada. Revisa los resultados del arqueo.';
    } catch (err: unknown) {
        const error = err as { response?: { data?: { error?: { message?: string } } } };
        errorMsg.value = error.response?.data?.error?.message || 'Error al cerrar la caja.';
    } finally {
        loading.value = false;
    }
}

async function printTicket() {
    if (!invoiceResult.value) return;
    const format = paperWidth.value === 'A4' ? 'A4' : 'ticket';

    try {
        const response = await api.get(`/invoices/${invoiceResult.value.id}/print/html`, {
            params: { format },
        });
        const html = response.data;

        /* eslint-disable-next-line @typescript-eslint/no-explicit-any */
        let iframe: any = window.document.getElementById('print-iframe');
        if (!iframe) {
            iframe = window.document.createElement('iframe');
            iframe.id = 'print-iframe';
            iframe.style.position = 'fixed';
            iframe.style.right = '0';
            iframe.style.bottom = '0';
            iframe.style.width = '0';
            iframe.style.height = '0';
            iframe.style.border = '0';
            window.document.body.appendChild(iframe);
        }

        const doc = iframe.contentDocument || iframe.contentWindow?.document;
        if (doc) {
            doc.open();
            doc.write(html);
            doc.close();

            window.setTimeout(() => {
                iframe?.contentWindow?.focus();
                iframe?.contentWindow?.print();
            }, 100);
        }
    } catch {
        errorMsg.value = 'Error al generar la vista de impresión en el cliente.';
    }
}

async function checkOfflineQueue() {
    const list = await IndexedDBService.getOfflineOrders();
    offlineQueueCount.value = list.length;
}

// Sincronizar cola offline
async function syncOfflineOrders() {
    if (!window.navigator.onLine) {
        errorMsg.value = 'No hay conexión a internet para sincronizar.';
        return;
    }
    const list = await IndexedDBService.getOfflineOrders();
    if (list.length === 0) return;

    loading.value = true;
    let syncCount = 0;
    try {
        for (const order of list) {
            await PosService.createOrder(order.payload as PosOrder, order.id);
            await IndexedDBService.deleteOfflineOrder(order.id);
            syncCount++;
        }
        successMsg.value = `Sincronizadas ${syncCount} órdenes offline con éxito.`;
        await checkOfflineQueue();
    } catch {
        errorMsg.value = 'Algunas órdenes no pudieron sincronizarse. Se reintentará luego.';
    } finally {
        loading.value = false;
    }
}

onMounted(() => {
    loadData();

    // Eventos de red
    window.addEventListener('online', () => {
        isOnline.value = true;
        void syncOfflineOrders();
    });
    window.addEventListener('offline', () => {
        isOnline.value = false;
    });
});

// Guardar carrito en IndexedDB reactivamente
watch(
    cart,
    async (newCart) => {
        await IndexedDBService.saveCart(JSON.parse(JSON.stringify(newCart)), cartScope());
    },
    { deep: true },
);

// Agregar item al carrito
function addToCart(prod: Product) {
    const existing = cart.value.find((item) => item.product_id === prod.id);
    const tax = taxes.value.find((t) => t.id === prod.tax_id) || null;

    if (existing) {
        existing.quantity += 1;
    } else {
        cart.value.push({
            product_id: prod.id,
            product_name: prod.name,
            quantity: 1,
            price: Number(prod.price) || 0,
            discount: 0,
            tax_id: prod.tax_id || null,
            tax_rate: tax ? tax.rate : 0,
        });
    }
}

function updateQty(idx: number, delta: number) {
    const item = cart.value[idx];
    item.quantity += delta;
    if (item.quantity <= 0) {
        cart.value.splice(idx, 1);
    }
}

function updateDiscount(idx: number, discountStr: string) {
    const val = Number(discountStr) || 0;
    cart.value[idx].discount = val;
}

// Totales reactivos
const totals = computed(() => {
    let subtotal = 0;
    let discount = 0;
    let taxAmount = 0;

    cart.value.forEach((item) => {
        const itemSub = item.price * item.quantity;
        const itemDisc = item.discount;
        const net = itemSub - itemDisc;
        const itemTax = net * ((item.tax_rate || 0) / 100);

        subtotal += itemSub;
        discount += itemDisc;
        taxAmount += itemTax;
    });

    const net = subtotal - discount;
    const tip = applyTip.value ? net * 0.1 : 0;
    const total = net + taxAmount + tip;

    return {
        subtotal,
        discount,
        taxAmount,
        tip,
        total,
    };
});

// Gestión de pagos
function addPaymentLine() {
    paymentsList.value.push({ payment_method_code: 'cash', currency_code: 'DOP', exchange_rate: 1.0, amount: 0 });
}

function removePaymentLine(idx: number) {
    paymentsList.value.splice(idx, 1);
}

function onCurrencyChange(idx: number) {
    const p = paymentsList.value[idx];
    const rate = exchangeRates.value[p.currency_code || 'DOP'];
    if (rate === undefined) {
        p.exchange_rate = 0;
        errorMsg.value = `No hay una tasa vigente configurada para ${p.currency_code}.`;

        return;
    }

    p.exchange_rate = rate;
}

// Billetes rápidos dominicanos para autocompletar pago
function quickCashAmount(idx: number, amount: number) {
    paymentsList.value[idx].amount = amount;
    paymentsList.value[idx].currency_code = 'DOP';
    paymentsList.value[idx].exchange_rate = 1.0;
}

const totalPaidDop = computed(() => {
    return paymentsList.value.reduce((acc, p) => acc + p.amount * (p.exchange_rate || 1.0), 0);
});

const changeDueDop = computed(() => {
    const diff = totalPaidDop.value - totals.value.total;
    return diff > 0 ? diff : 0;
});

const selectedCustomerHasRnc = computed(() => {
    const cust = customers.value.find((c) => c.id === selectedCustomerId.value);
    return !!(cust && cust.tax_id);
});

// Registrar la venta/orden
async function submitOrder() {
    if (!selectedCustomerId.value || !selectedWarehouseId.value) {
        errorMsg.value = 'Completa el cliente y el almacén antes de cobrar.';
        return;
    }

    if (orderStatus.value === 'completed' && documentTypeCode.value === 'B01' && !selectedCustomerHasRnc.value) {
        errorMsg.value =
            'El cliente seleccionado debe tener un RNC registrado para emitir una factura de Crédito Fiscal (B01).';
        return;
    }

    // Si es completada, validar que los pagos alcancen el total
    if (orderStatus.value === 'completed' && totalPaidDop.value < totals.value.total) {
        errorMsg.value = 'El total de los cobros debe cubrir el total de la orden.';
        return;
    }

    loading.value = true;
    errorMsg.value = '';
    successMsg.value = '';

    const idempotencyKey = 'pos-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
    const orderNumber = 'POS-' + Date.now().toString().slice(-6);

    const payload: PosOrder = {
        customer_id: selectedCustomerId.value,
        warehouse_id: selectedWarehouseId.value,
        order_number: orderNumber,
        status: orderStatus.value,
        apply_tip: applyTip.value,
        notes: orderNotes.value || undefined,
        items: cart.value.map((item) => ({
            product_id: item.product_id,
            quantity: item.quantity,
            price: item.price,
            discount: item.discount,
            tax_id: item.tax_id ?? null,
            batch_number: item.batch_number || undefined,
        })),
        payments:
            orderStatus.value === 'completed'
                ? paymentsList.value.map((p) => ({
                      payment_method_code: p.payment_method_code,
                      currency_code: p.currency_code,
                      exchange_rate: p.exchange_rate,
                      amount: p.amount,
                      reference: p.reference || undefined,
                  }))
                : undefined,
    };

    if (window.navigator.onLine) {
        try {
            const orderData = (await PosService.createOrder(payload, idempotencyKey)) as { id: string };

            if (orderStatus.value === 'completed') {
                // Emitir factura tradicional
                try {
                    const invRes = await api.post('/invoices/from-order', {
                        order_id: orderData.id,
                        document_type_code: documentTypeCode.value,
                    });
                    invoiceResult.value = invRes.data.data;
                    showInvoicePrintModal.value = true;
                } catch (invErr: unknown) {
                    const err = invErr as { response?: { data?: { error?: { message?: string } } } };
                    errorMsg.value =
                        'Venta cobrada, pero falló la emisión de comprobante fiscal: ' +
                        (err.response?.data?.error?.message || 'Error de red.');
                }
            }

            successMsg.value = `Orden ${orderNumber} registrada y facturada con éxito.`;
            cart.value = [];
            showPayModal.value = false;

            // Reiniciar estado de pagos
            paymentsList.value = [{ payment_method_code: 'cash', currency_code: 'DOP', exchange_rate: 1.0, amount: 0 }];

            // Recargar saldo esperado de caja
            const sess = (await PosService.getActiveCashSession()) as ActiveSessionData | null;
            if (sess) activeSession.value = sess;
        } catch (err: unknown) {
            const error = err as { response?: { data?: { error?: { message?: string } } } };
            errorMsg.value = error.response?.data?.error?.message || 'Error al procesar la venta en el servidor.';
        } finally {
            loading.value = false;
        }
    } else {
        // Guardado offline en IndexedDB
        try {
            await IndexedDBService.saveOfflineOrder({
                id: idempotencyKey,
                payload,
                created_at: Date.now(),
            });
            successMsg.value = `Sin conexión. Orden ${orderNumber} guardada localmente para sincronización posterior.`;
            cart.value = [];
            await checkOfflineQueue();
            showPayModal.value = false;
        } catch {
            errorMsg.value = 'Error al guardar la orden de forma local.';
        } finally {
            loading.value = false;
        }
    }
}

const filteredProducts = computed(() => {
    if (!searchQuery.value) return products.value;
    return products.value.filter(
        (p) =>
            p.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            (p.sku && p.sku.toLowerCase().includes(searchQuery.value.toLowerCase())),
    );
});
</script>

<template>
    <main class="min-h-screen bg-kinetic-surface text-kinetic-ink">
        <!-- 1. FORMULARIO DE APERTURA DE CAJA (Si no hay turno activo) -->
        <div v-if="!activeSession" class="flex flex-col items-center justify-center min-h-screen p-4 bg-[#f2eff9]">
            <div class="bg-white rounded-3xl p-8 max-w-md w-full shadow-lg border border-[#c7c4d8] space-y-6">
                <div class="text-center space-y-2">
                    <span class="text-4xl">🔑</span>
                    <h2 class="text-2xl font-bold tracking-tight text-[#302f39]">Apertura de Caja</h2>
                    <p class="text-base text-[#464555]">Registra la caja física y el fondo para iniciar turno.</p>
                </div>

                <div v-if="errorMsg" class="p-4 bg-[#ffdad6] text-[#93000a] text-base rounded-xl font-semibold">
                    {{ errorMsg }}
                </div>
                <div v-if="successMsg" class="p-4 bg-[#e7f6ee] text-[#006c49] text-base rounded-xl font-semibold">
                    {{ successMsg }}
                </div>

                <!-- Historial de último arqueo cerrado -->
                <div
                    v-if="lastClosedSummary"
                    class="p-4 bg-gray-50 rounded-2xl border border-gray-200 text-base space-y-1"
                >
                    <p class="font-bold text-gray-700">Resultado del Arqueo Anterior:</p>
                    <p>
                        Esperado:
                        <span class="font-semibold"
                            >RD$ {{ Number(lastClosedSummary.expected_amount).toFixed(2) }}</span
                        >
                    </p>
                    <p>
                        Contado:
                        <span class="font-semibold">RD$ {{ Number(lastClosedSummary.counted_amount).toFixed(2) }}</span>
                    </p>
                    <p>
                        Diferencia:
                        <span
                            :class="[
                                'font-bold',
                                Number(lastClosedSummary.difference) < 0 ? 'text-red-600' : 'text-green-700',
                            ]"
                        >
                            RD$ {{ Number(lastClosedSummary.difference).toFixed(2) }}
                        </span>
                    </p>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-[#464555] mb-1.5">Caja Registradora</label>
                        <select
                            v-model="openRegisterId"
                            class="w-full min-h-14 rounded-xl border border-[#c7c4d8] px-4 text-base focus:ring-[#3525cd]"
                        >
                            <option v-for="r in registers" :key="r.id" :value="r.id">
                                {{ r.name }} ({{ r.code }})
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-[#464555] mb-1.5">Fondo Inicial DOP</label>
                        <input
                            v-model="openOpeningAmount"
                            type="number"
                            class="w-full min-h-14 rounded-xl border border-[#c7c4d8] px-4 text-lg font-bold focus:ring-[#3525cd]"
                            min="0"
                        />
                    </div>

                    <button
                        :disabled="loading"
                        class="w-full min-h-14 rounded-xl bg-[#3525cd] hover:bg-[#271aa3] text-white text-lg font-bold transition active:scale-[.99] disabled:bg-gray-300"
                        @click="handleOpenSession"
                    >
                        Abrir Caja e Iniciar Ventas
                    </button>
                </div>
            </div>
        </div>

        <!-- 2. TERMINAL POS INTERACTIVA (Si hay turno activo) -->
        <div v-else>
            <!-- Barra de estado superior -->
            <header class="bg-[#302f39] text-[#f3effc] p-4 flex flex-wrap justify-between items-center gap-3">
                <div class="flex items-center gap-3">
                    <span class="font-bold tracking-wider text-base">OMNIPOS · {{ activeSession.register_name }}</span>
                    <span
                        :class="[
                            'px-2.5 py-1 rounded-full text-xs font-bold uppercase',
                            isOnline ? 'bg-green-600 text-white' : 'bg-orange-600 text-white',
                        ]"
                    >
                        {{ isOnline ? 'En línea' : 'Sin conexión' }}
                    </span>
                    <span class="text-sm text-gray-300">| Cajero: {{ activeSession.opened_by }}</span>
                    <span class="text-sm font-bold text-green-400">
                        Caja DOP: {{ Number(activeSession.expected_amount).toFixed(2) }}
                    </span>
                </div>
                <div class="flex items-center gap-2">
                    <select
                        v-model="paperWidth"
                        class="text-sm min-h-11 bg-white/10 text-[#f3effc] border-0 rounded-lg px-3 focus:ring-0 cursor-pointer font-semibold outline-none"
                    >
                        <option class="text-black" value="80mm">🖨️ Ticket 80mm</option>
                        <option class="text-black" value="58mm">🖨️ Ticket 58mm</option>
                        <option class="text-black" value="A4">📄 Factura A4 (Carta)</option>
                    </select>
                    <button
                        class="text-sm min-h-11 bg-white/10 hover:bg-white/20 px-4 rounded-lg transition font-semibold"
                        @click="showMovementModal = true"
                    >
                        💸 Movimiento Caja
                    </button>
                    <button
                        class="text-sm min-h-11 bg-red-600/80 hover:bg-red-600 px-4 rounded-lg transition font-semibold"
                        @click="showCloseSessionModal = true"
                    >
                        🔒 Cerrar Caja
                    </button>
                </div>
            </header>

            <div class="grid lg:grid-cols-[450px_1fr] h-[calc(100vh-65px)] overflow-hidden">
                <!-- PANEL IZQUIERDO: CARRITO DE COMPRA -->
                <section
                    class="flex flex-col border-r border-[#c7c4d8] bg-white h-full overflow-hidden"
                    aria-label="Carrito de compra"
                >
                    <!-- Listado de items del carrito -->
                    <div class="flex-1 overflow-y-auto p-4 space-y-3">
                        <div
                            v-if="cart.length === 0"
                            class="h-full flex flex-col items-center justify-center text-[#464555] p-8"
                        >
                            <span class="text-5xl mb-3">🛒</span>
                            <p class="text-lg font-semibold">El carrito está vacío.</p>
                            <p class="text-base text-gray-400 mt-1">Presiona un producto para agregarlo.</p>
                        </div>

                        <div
                            v-for="(item, idx) in cart"
                            :key="item.product_id"
                            class="border border-[#e4e1ee] rounded-xl p-3 bg-[#fcfbfe] flex flex-col gap-2"
                        >
                            <div class="flex justify-between items-start gap-2">
                                <span class="font-semibold text-base">{{ item.product_name }}</span>
                                <span class="font-bold text-base whitespace-nowrap"
                                    >RD$ {{ Number(item.price * item.quantity - item.discount).toFixed(2) }}</span
                                >
                            </div>
                            <div class="flex items-center justify-between mt-1 gap-2">
                                <!-- Botones cantidad -->
                                <div
                                    class="flex items-center border border-[#c7c4d8] rounded-lg overflow-hidden bg-white"
                                >
                                    <button
                                        class="min-w-11 min-h-11 flex items-center justify-center text-xl font-bold text-[#464555] hover:bg-gray-100 active:bg-gray-200"
                                        :aria-label="`Restar uno a ${item.product_name}`"
                                        @click="updateQty(idx, -1)"
                                    >
                                        −
                                    </button>
                                    <span class="px-3 font-bold text-lg min-w-10 text-center">{{ item.quantity }}</span>
                                    <button
                                        class="min-w-11 min-h-11 flex items-center justify-center text-xl font-bold text-[#464555] hover:bg-gray-100 active:bg-gray-200"
                                        :aria-label="`Sumar uno a ${item.product_name}`"
                                        @click="updateQty(idx, 1)"
                                    >
                                        +
                                    </button>
                                </div>
                                <!-- Input Descuento -->
                                <div class="flex items-center gap-2">
                                    <label class="text-sm font-semibold text-[#464555]">Desc. RD$</label>
                                    <input
                                        type="number"
                                        :value="item.discount"
                                        class="w-24 min-h-11 rounded-lg border border-[#c7c4d8] px-2 text-base text-right"
                                        min="0"
                                        @input="updateDiscount(idx, ($event.target as HTMLInputElement).value)"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Resumen de montos -->
                    <div class="border-t border-[#e4e1ee] bg-[#fcfbfe] p-4 space-y-2">
                        <div class="flex justify-between text-base text-[#464555]">
                            <span>Subtotal:</span>
                            <span>RD$ {{ totals.subtotal.toFixed(2) }}</span>
                        </div>
                        <div v-if="totals.discount > 0" class="flex justify-between text-base text-[#ba1a1a]">
                            <span>Descuento:</span>
                            <span>- RD$ {{ totals.discount.toFixed(2) }}</span>
                        </div>
                        <div class="flex justify-between text-base text-[#464555]">
                            <span>ITBIS total:</span>
                            <span>RD$ {{ totals.taxAmount.toFixed(2) }}</span>
                        </div>
                        <div v-if="totals.tip > 0" class="flex justify-between text-base text-[#006c49]">
                            <span>Propina legal (10%):</span>
                            <span>RD$ {{ totals.tip.toFixed(2) }}</span>
                        </div>
                        <div class="flex justify-between items-center border-t border-[#e4e1ee] pt-2 text-[#302f39]">
                            <span class="text-lg font-bold">Total General:</span>
                            <span class="text-3xl font-bold tracking-wide">RD$ {{ totals.total.toFixed(2) }}</span>
                        </div>

                        <!-- Botón de Cobro -->
                        <button
                            :disabled="cart.length === 0"
                            class="w-full min-h-14 mt-2 rounded-xl bg-[#006c49] disabled:bg-gray-300 px-5 font-bold text-white text-lg shadow-sm hover:bg-[#005236] transition active:scale-[.98] flex items-center justify-center gap-3"
                            @click="showPayModal = true"
                        >
                            <span>Completar Venta</span>
                            <span class="text-base font-semibold opacity-90">RD$ {{ totals.total.toFixed(2) }}</span>
                        </button>
                    </div>
                </section>

                <!-- PANEL DERECHO: CATÁLOGO DE PRODUCTOS -->
                <section class="flex flex-col bg-[#fcfbfe] h-full overflow-hidden p-4">
                    <!-- Buscador -->
                    <div class="mb-4">
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Busca productos por nombre, SKU o código..."
                            class="w-full min-h-13 rounded-xl border border-[#c7c4d8] bg-white px-4 text-base focus:border-[#3525cd] focus:ring-1 focus:ring-[#3525cd]"
                        />
                    </div>

                    <!-- Grid de productos -->
                    <div class="flex-1 overflow-y-auto">
                        <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3">
                            <button
                                v-for="p in filteredProducts"
                                :key="p.id"
                                class="bg-white border border-[#e4e1ee] hover:border-[#3525cd] hover:shadow-md rounded-2xl p-4 flex flex-col justify-between items-start text-left min-h-[132px] transition active:scale-[0.98]"
                                @click="addToCart(p)"
                            >
                                <span class="font-semibold text-base text-[#302f39] line-clamp-2 leading-snug">{{
                                    p.name
                                }}</span>
                                <div class="mt-2 w-full flex justify-between items-end gap-2">
                                    <span class="text-xs text-gray-500 font-mono">SKU: {{ p.sku || 'N/A' }}</span>
                                    <span class="font-bold text-lg text-[#3525cd] whitespace-nowrap"
                                        >RD$ {{ Number(p.price).toFixed(2) }}</span
                                    >
                                </div>
                            </button>
                        </div>

                        <div v-if="filteredProducts.length === 0" class="text-center text-sm text-[#464555] py-12">
                            No se encontraron productos coincidentes.
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <!-- MODAL DE COBRO / CONFIRMACIÓN Y PAGOS MIXTOS -->
        <div
            v-if="showPayModal"
            class="fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50 animate-fade-in"
            aria-modal="true"
            role="dialog"
        >
            <div
                class="bg-white rounded-2xl max-w-2xl w-full flex flex-col overflow-hidden shadow-xl border border-[#c7c4d8] max-h-[92vh]"
            >
                <header class="px-6 py-5 border-b border-[#e4e1ee] bg-[#fcfbfe] flex justify-between items-center">
                    <h2 class="text-2xl font-bold tracking-tight text-[#302f39]">Detalles del Cobro</h2>
                    <button
                        class="min-h-11 min-w-11 hover:bg-gray-100 rounded-lg flex items-center justify-center text-lg text-[#464555]"
                        aria-label="Cerrar"
                        @click="showPayModal = false"
                    >
                        ✕
                    </button>
                </header>

                <div class="p-6 space-y-5 overflow-y-auto flex-1">
                    <!-- Error del cobro (p. ej. stock insuficiente o fallo fiscal) -->
                    <div
                        v-if="errorMsg"
                        class="p-4 bg-[#ffdad6] text-[#93000a] text-base rounded-xl font-semibold"
                        role="alert"
                    >
                        {{ errorMsg }}
                    </div>

                    <!-- Cliente y Almacén -->
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-[#464555] mb-1.5">Cliente *</label>
                            <select
                                v-model="selectedCustomerId"
                                class="w-full min-h-12 rounded-lg border border-[#c7c4d8] px-4 text-base"
                            >
                                <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }}</option>
                            </select>
                            <p
                                v-if="
                                    orderStatus === 'completed' && documentTypeCode === 'B01' && !selectedCustomerHasRnc
                                "
                                class="text-sm text-[#ba1a1a] font-semibold mt-1.5"
                            >
                                ⚠️ El cliente no posee RNC registrado.
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-[#464555] mb-1.5">Almacén *</label>
                            <select
                                v-model="selectedWarehouseId"
                                class="w-full min-h-12 rounded-lg border border-[#c7c4d8] px-4 text-base"
                            >
                                <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
                            </select>
                        </div>
                    </div>

                    <!-- Tipo y Propina -->
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-[#464555] mb-1.5">Tipo de venta</label>
                            <select
                                v-model="orderStatus"
                                class="w-full min-h-12 rounded-lg border border-[#c7c4d8] px-4 text-base"
                            >
                                <option value="completed">Venta Directa (Completada)</option>
                                <option value="pending">Orden Pendiente (Mesa)</option>
                            </select>
                        </div>
                        <div v-if="orderStatus === 'completed'">
                            <label class="block text-sm font-semibold text-[#464555] mb-1.5">Comprobante</label>
                            <select
                                v-model="documentTypeCode"
                                class="w-full min-h-12 rounded-lg border border-[#c7c4d8] px-4 text-base bg-[#f4f2ff] font-semibold text-[#3525cd]"
                            >
                                <option value="B02">B02 - Consumidor Final</option>
                                <option value="B01">B01 - Crédito Fiscal</option>
                            </select>
                        </div>
                    </div>
                    <label
                        for="pay-tip-checkbox"
                        class="flex items-center gap-3 min-h-12 rounded-xl border border-[#e4e1ee] bg-[#fcfbfe] px-4 cursor-pointer select-none"
                    >
                        <input
                            id="pay-tip-checkbox"
                            v-model="applyTip"
                            type="checkbox"
                            class="h-6 w-6 rounded border-[#c7c4d8] text-[#3525cd]"
                        />
                        <span class="text-base font-semibold text-[#464555]">Aplicar 10% Propina de Ley</span>
                    </label>

                    <!-- SECCIÓN DE PAGOS MIXTOS (Solo si es completada) -->
                    <div v-if="orderStatus === 'completed'" class="border-t border-[#e4e1ee] pt-4 space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-bold text-[#464555] uppercase tracking-wide">
                                Detalle de Cobros
                            </span>
                            <button
                                class="min-h-11 text-sm bg-white hover:bg-[#f0ecf9] border border-[#c7c4d8] rounded-lg px-4 font-bold text-[#3525cd]"
                                @click="addPaymentLine"
                            >
                                ＋ Agregar Pago
                            </button>
                        </div>

                        <div
                            v-for="(pay, pIdx) in paymentsList"
                            :key="pIdx"
                            class="p-4 bg-[#fcfbfe] border border-[#e4e1ee] rounded-xl space-y-3 relative"
                        >
                            <button
                                v-if="paymentsList.length > 1"
                                class="absolute top-2 right-2 min-h-9 min-w-9 rounded-lg flex items-center justify-center text-[#ba1a1a] hover:bg-[#ffdad6]"
                                :aria-label="`Quitar pago ${pIdx + 1}`"
                                @click="removePaymentLine(pIdx)"
                            >
                                ✕
                            </button>

                            <div class="grid sm:grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-sm font-semibold text-[#464555] mb-1.5">Método</label>
                                    <select
                                        v-model="pay.payment_method_code"
                                        :aria-label="`Método de pago ${pIdx + 1}`"
                                        class="w-full min-h-12 rounded-lg border border-[#c7c4d8] px-3 text-base"
                                    >
                                        <option value="cash">Efectivo</option>
                                        <option value="card">Tarjeta</option>
                                        <option value="transfer">Transferencia</option>
                                        <option value="credit">Crédito</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-[#464555] mb-1.5">Moneda</label>
                                    <select
                                        v-model="pay.currency_code"
                                        :aria-label="`Moneda de pago ${pIdx + 1}`"
                                        class="w-full min-h-12 rounded-lg border border-[#c7c4d8] px-3 text-base"
                                        @change="onCurrencyChange(pIdx)"
                                    >
                                        <option value="DOP">Pesos DOP</option>
                                        <option value="USD">Dólares USD</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-[#464555] mb-1.5">
                                        Monto Entregado
                                    </label>
                                    <input
                                        v-model="pay.amount"
                                        :aria-label="`Monto de pago ${pIdx + 1}`"
                                        type="number"
                                        class="w-full min-h-12 rounded-lg border border-[#c7c4d8] px-3 text-lg font-bold text-right"
                                        min="0"
                                    />
                                </div>
                            </div>

                            <!-- Inputs adicionales para tarjetas o USD -->
                            <div class="space-y-2 text-sm">
                                <span v-if="pay.currency_code === 'USD'" class="block text-[#464555]">
                                    Tasa: DOP {{ pay.exchange_rate }} (Total: DOP
                                    {{ Number(pay.amount * (pay.exchange_rate || 1.0)).toFixed(2) }})
                                </span>
                                <input
                                    v-if="pay.payment_method_code === 'card' || pay.payment_method_code === 'transfer'"
                                    v-model="pay.reference"
                                    :aria-label="`Referencia de pago ${pIdx + 1}`"
                                    type="text"
                                    placeholder="Nº trans/tarjeta ref..."
                                    class="w-full min-h-11 rounded-lg border border-[#c7c4d8] px-3 text-base"
                                />
                            </div>

                            <!-- Botones rápidos de billetes DOP en efectivo -->
                            <div
                                v-if="pay.payment_method_code === 'cash' && pay.currency_code === 'DOP'"
                                class="grid grid-cols-4 gap-2 pt-1"
                            >
                                <button
                                    v-for="bills in [200, 500, 1000, 2000]"
                                    :key="bills"
                                    class="min-h-12 text-base bg-white border border-[#c7c4d8] hover:border-[#3525cd] hover:bg-[#f4f2ff] rounded-lg font-bold text-[#302f39] transition active:scale-[.97]"
                                    @click="quickCashAmount(pIdx, bills)"
                                >
                                    RD$ {{ bills }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Resumen del Cobro -->
                    <div class="border-t border-[#e4e1ee] pt-4 space-y-2">
                        <div class="flex justify-between items-center text-lg">
                            <span class="text-[#464555]">Total a pagar:</span>
                            <span class="font-bold text-2xl tracking-wide text-[#302f39]">
                                RD$ {{ totals.total.toFixed(2) }}
                            </span>
                        </div>
                        <div
                            v-if="orderStatus === 'completed'"
                            class="flex justify-between items-center text-base text-[#1d4ed8]"
                        >
                            <span>Total entregado (DOP equiv):</span>
                            <span class="font-bold text-lg">RD$ {{ totalPaidDop.toFixed(2) }}</span>
                        </div>
                        <div
                            v-if="orderStatus === 'completed' && changeDueDop > 0"
                            class="flex justify-between items-center rounded-xl bg-[#e7f6ee] px-4 py-3 text-[#006c49] font-bold"
                        >
                            <span class="text-lg">Cambio / Devuelta:</span>
                            <span class="text-2xl tracking-wide">RD$ {{ changeDueDop.toFixed(2) }}</span>
                        </div>
                    </div>
                </div>

                <footer class="px-6 py-4 border-t border-[#e4e1ee] bg-[#fcfbfe] flex gap-3">
                    <button
                        class="min-h-14 rounded-xl border border-[#c7c4d8] bg-white px-6 font-semibold text-[#302f39] text-base"
                        @click="showPayModal = false"
                    >
                        Cancelar
                    </button>
                    <button
                        :disabled="loading || (orderStatus === 'completed' && totalPaidDop < totals.total)"
                        class="min-h-14 flex-1 rounded-xl bg-[#006c49] px-6 font-bold text-white text-lg shadow-sm hover:bg-[#005236] transition active:scale-[.99] disabled:bg-gray-300"
                        @click="submitOrder"
                    >
                        Confirmar e Imprimir
                    </button>
                </footer>
            </div>
        </div>

        <!-- MODAL DE INGRESO / EGRESO DE CAJA -->
        <div
            v-if="showMovementModal"
            class="fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50 animate-fade-in"
            aria-modal="true"
            role="dialog"
        >
            <div class="bg-white rounded-2xl max-w-md w-full shadow-xl border border-[#c7c4d8]">
                <header class="p-5 border-b border-[#e4e1ee] bg-[#fcfbfe] flex justify-between items-center">
                    <h3 class="text-xl font-bold text-[#302f39]">Movimiento de Caja Física</h3>
                    <button
                        class="min-h-11 min-w-11 rounded-lg hover:bg-gray-100 flex items-center justify-center text-lg text-[#464555]"
                        aria-label="Cerrar"
                        @click="showMovementModal = false"
                    >
                        ✕
                    </button>
                </header>
                <div class="p-5 space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-[#464555] mb-1.5">Tipo de Movimiento</label>
                        <div class="grid grid-cols-2 gap-2">
                            <button
                                :class="[
                                    'min-h-12 rounded-lg border text-base font-bold transition active:scale-[.98]',
                                    movementType === 'in'
                                        ? 'bg-[#006c49] border-[#006c49] text-white'
                                        : 'border-[#c7c4d8] text-[#464555]',
                                ]"
                                @click="movementType = 'in'"
                            >
                                Ingreso (Fondo Extra)
                            </button>
                            <button
                                :class="[
                                    'min-h-12 rounded-lg border text-base font-bold transition active:scale-[.98]',
                                    movementType === 'out'
                                        ? 'bg-[#ba1a1a] border-[#ba1a1a] text-white'
                                        : 'border-[#c7c4d8] text-[#464555]',
                                ]"
                                @click="movementType = 'out'"
                            >
                                Egreso (Gasto/Remesa)
                            </button>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-[#464555] mb-1.5">Monto DOP</label>
                        <input
                            v-model="movementAmount"
                            type="number"
                            class="w-full min-h-12 rounded-lg border border-[#c7c4d8] px-4 text-lg font-bold"
                            min="0.01"
                            step="0.01"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-[#464555] mb-1.5">Concepto / Indicación</label>
                        <input
                            v-model="movementConcept"
                            type="text"
                            placeholder="Ej. Pago de delivery..."
                            class="w-full min-h-12 rounded-lg border border-[#c7c4d8] px-4 text-base"
                        />
                    </div>
                </div>
                <footer class="p-4 border-t border-[#e4e1ee] bg-[#fcfbfe] flex justify-end gap-3">
                    <button
                        class="min-h-12 rounded-lg border border-[#c7c4d8] bg-white px-5 text-base font-semibold text-[#302f39]"
                        @click="showMovementModal = false"
                    >
                        Cancelar
                    </button>
                    <button
                        :disabled="loading || movementAmount <= 0 || !movementConcept"
                        class="min-h-12 rounded-lg bg-[#3525cd] text-white px-5 text-base font-bold hover:bg-[#271aa3] disabled:bg-gray-300"
                        @click="handleCreateMovement"
                    >
                        Confirmar Movimiento
                    </button>
                </footer>
            </div>
        </div>

        <!-- MODAL DE CIERRE Y ARQUEO DE CAJA -->
        <div
            v-if="showCloseSessionModal"
            class="fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50 animate-fade-in"
            aria-modal="true"
            role="dialog"
        >
            <div class="bg-white rounded-2xl max-w-md w-full shadow-xl border border-[#c7c4d8]">
                <header class="p-5 border-b border-[#e4e1ee] bg-[#fcfbfe] flex justify-between items-center">
                    <h3 class="text-xl font-bold text-[#302f39]">Cierre de Caja y Arqueo</h3>
                    <button
                        class="min-h-11 min-w-11 rounded-lg hover:bg-gray-100 flex items-center justify-center text-lg text-[#464555]"
                        aria-label="Cerrar"
                        @click="showCloseSessionModal = false"
                    >
                        ✕
                    </button>
                </header>
                <div class="p-5 space-y-4">
                    <div class="p-4 bg-blue-50 border border-blue-200 rounded-xl text-base text-blue-900 space-y-1">
                        <p class="font-bold">Información de Sistema:</p>
                        <p>
                            Turno: <span class="font-mono font-bold">{{ activeSession?.register_name }}</span>
                        </p>
                        <p>
                            Apertura:
                            <span class="font-mono">RD$ {{ Number(activeSession?.opening_amount).toFixed(2) }}</span>
                        </p>
                        <p>
                            Esperado en Caja:
                            <span class="font-mono font-bold"
                                >RD$ {{ Number(activeSession?.expected_amount).toFixed(2) }}</span
                            >
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-[#464555] mb-1.5"
                            >Efectivo Contado Físicamente (DOP)</label
                        >
                        <input
                            v-model="closeCountedAmount"
                            type="number"
                            class="w-full min-h-12 rounded-lg border border-[#c7c4d8] px-4 text-lg font-bold"
                            min="0"
                            step="0.01"
                        />
                    </div>
                </div>
                <footer class="p-4 border-t border-[#e4e1ee] bg-[#fcfbfe] flex justify-end gap-3">
                    <button
                        class="min-h-12 rounded-lg border border-[#c7c4d8] bg-white px-5 text-base font-semibold text-[#302f39]"
                        @click="showCloseSessionModal = false"
                    >
                        Cancelar
                    </button>
                    <button
                        :disabled="loading"
                        class="min-h-12 rounded-lg bg-[#ba1a1a] text-white px-5 text-base font-bold hover:bg-[#93000a]"
                        @click="handleCloseSession"
                    >
                        Confirmar Cierre de Caja
                    </button>
                </footer>
            </div>
        </div>

        <!-- MODAL DE TICKET DE FACTURA (IMPRESIÓN) -->
        <div
            v-if="showInvoicePrintModal"
            class="fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50 animate-fade-in"
            aria-modal="true"
            role="dialog"
        >
            <div
                class="bg-white rounded-2xl max-w-sm w-full shadow-xl border border-[#c7c4d8] overflow-hidden flex flex-col max-h-[90vh]"
            >
                <header class="p-4 border-b border-[#e4e1ee] bg-[#fcfbfe] flex justify-between items-center">
                    <h3 class="text-xl font-bold text-[#302f39]">Comprobante Emitido</h3>
                    <button
                        class="min-h-11 min-w-11 rounded-lg hover:bg-gray-100 flex items-center justify-center text-lg text-[#464555]"
                        aria-label="Cerrar"
                        @click="showInvoicePrintModal = false"
                    >
                        ✕
                    </button>
                </header>

                <!-- Ticket Térmico de 80mm -->
                <div
                    id="printable-ticket"
                    class="p-6 overflow-y-auto flex-1 font-mono text-[13px] text-gray-800 space-y-4"
                >
                    <div class="text-center space-y-1">
                        <p class="font-bold text-sm uppercase">
                            {{ invoiceResult?.branch_name || 'Mi Sucursal SaaS' }}
                        </p>
                        <p>RNC: {{ invoiceResult?.customer_rnc || 'NO FISCAL' }}</p>
                        <p class="border-b border-dashed border-gray-400 pb-2">Tel: 809-555-0199</p>
                    </div>

                    <div class="space-y-1">
                        <p>
                            Factura: <span class="font-bold">{{ invoiceResult?.invoice_number }}</span>
                        </p>
                        <p>
                            NCF: <span class="font-bold text-xs">{{ invoiceResult?.ncf || 'B0200000000' }}</span>
                        </p>
                        <p v-if="invoiceResult?.ncf_expires_at">Vence: {{ invoiceResult?.ncf_expires_at }}</p>
                        <p>
                            Tipo:
                            {{
                                invoiceResult?.document_type_code === 'B01'
                                    ? 'Crédito Fiscal (B01)'
                                    : 'Consumidor Final (B02)'
                            }}
                        </p>
                        <p>
                            Fecha:
                            {{
                                invoiceResult?.created_at
                                    ? new Date(invoiceResult.created_at).toLocaleString('es-DO')
                                    : ''
                            }}
                        </p>
                        <p>Cliente: {{ invoiceResult?.customer_name }}</p>
                        <p class="border-b border-dashed border-gray-400 pb-2"></p>
                    </div>

                    <!-- Items -->
                    <table class="w-full text-left">
                        <thead>
                            <tr class="border-b border-dashed border-gray-400">
                                <th class="pb-1">Cant/Desc</th>
                                <th class="text-right pb-1">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in invoiceResult?.items" :key="item.product_id">
                                <td class="py-1">
                                    {{ Number(item.quantity).toFixed(0) }} x {{ item.product_name }} @{{
                                        Number(item.price).toFixed(2)
                                    }}
                                    <span v-if="item.batch_number" class="block text-[9px] text-gray-500"
                                        >Lote: {{ item.batch_number }}</span
                                    >
                                </td>
                                <td class="text-right py-1">RD$ {{ Number(item.total).toFixed(2) }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="border-t border-dashed border-gray-400 pt-2 space-y-1">
                        <div class="flex justify-between">
                            <span>Subtotal:</span>
                            <span>RD$ {{ Number(invoiceResult?.subtotal).toFixed(2) }}</span>
                        </div>
                        <div v-if="Number(invoiceResult?.discount_total) > 0" class="flex justify-between text-red-600">
                            <span>Descuento:</span>
                            <span>-RD$ {{ Number(invoiceResult?.discount_total).toFixed(2) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>ITBIS (18%):</span>
                            <span>RD$ {{ Number(invoiceResult?.tax_total).toFixed(2) }}</span>
                        </div>
                        <div v-if="Number(invoiceResult?.tip_total) > 0" class="flex justify-between">
                            <span>Propina Legal (10%):</span>
                            <span>RD$ {{ Number(invoiceResult?.tip_total).toFixed(2) }}</span>
                        </div>
                        <div class="flex justify-between font-bold text-xs pt-1 border-t border-dashed border-gray-400">
                            <span>TOTAL:</span>
                            <span>RD$ {{ Number(invoiceResult?.total).toFixed(2) }}</span>
                        </div>
                    </div>

                    <div class="text-center pt-4 space-y-1">
                        <p class="font-bold">¡GRACIAS POR SU COMPRA!</p>
                        <p>OmniPOS Modular SaaS</p>
                    </div>
                </div>

                <footer class="p-4 border-t border-[#e4e1ee] bg-[#fcfbfe] flex justify-end gap-3">
                    <button
                        class="min-h-12 rounded-lg border border-[#c7c4d8] bg-white px-5 text-base font-semibold text-[#302f39]"
                        @click="showInvoicePrintModal = false"
                    >
                        Cerrar
                    </button>
                    <button
                        class="min-h-12 rounded-lg bg-[#3525cd] text-white px-5 text-base font-bold hover:bg-[#271aa3]"
                        @click="printTicket"
                    >
                        🖨️ Imprimir Ticket
                    </button>
                </footer>
            </div>
        </div>
    </main>
</template>
