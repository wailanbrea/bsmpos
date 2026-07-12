<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { InventoryService } from '../services';
import type { Supplier, Warehouse, StockItem, KardexMovement, Purchase, PurchaseItem } from '../services';
import { fetchProducts } from '../../products/services';
import type { Product } from '../../products/types';

type Tab = 'stock' | 'warehouses' | 'suppliers' | 'purchases';

const activeTab = ref<Tab>('stock');
const loading = ref(false);
const errorMsg = ref('');
const successMsg = ref('');

// Listados reactivos
const suppliers = ref<Supplier[]>([]);
const warehouses = ref<Warehouse[]>([]);
const stocks = ref<StockItem[]>([]);
const purchases = ref<Purchase[]>([]);
const availableProducts = ref<Product[]>([]);

// Selección de almacén para filtrado de existencias
const selectedWarehouseFilter = ref('');

// Modales y visualización de Kardex
const showKardexModal = ref(false);
const kardexMovements = ref<KardexMovement[]>([]);
const kardexProductName = ref('');

// Formularios
const newSupplier = ref({
    name: '',
    tax_id: '',
    phone: '',
    email: '',
    address: '',
});

const newWarehouse = ref({
    name: '',
    code: '',
    is_default: false,
});

const newPurchase = ref({
    supplier_id: '',
    warehouse_id: '',
    purchase_number: '',
    purchase_date: new Date().toISOString().split('T')[0],
    notes: '',
    items: [] as PurchaseItem[],
});

// Búsqueda y agregado de items de compra
const searchProductQuery = ref('');
const showProductDropdown = ref(false);

function getErrorMessage(err: unknown, defaultMsg: string): string {
    const error = err as { response?: { data?: { error?: { message?: string } } } };
    return error.response?.data?.error?.message || defaultMsg;
}

async function loadData() {
    loading.value = true;
    errorMsg.value = '';
    try {
        suppliers.value = await InventoryService.getSuppliers();
        warehouses.value = await InventoryService.getWarehouses();
        purchases.value = await InventoryService.getPurchases();

        // Cargar stock según filtro de almacén
        stocks.value = await InventoryService.getStock(selectedWarehouseFilter.value || undefined);

        // Cargar productos para compras
        availableProducts.value = await fetchProducts('');
    } catch (err: unknown) {
        errorMsg.value = getErrorMessage(err, 'Error al cargar los datos del inventario.');
    } finally {
        loading.value = false;
    }
}

onMounted(() => {
    loadData();
});

// Filtrado de stock reactivo
async function handleWarehouseFilterChange() {
    loading.value = true;
    try {
        stocks.value = await InventoryService.getStock(selectedWarehouseFilter.value || undefined);
    } catch (err: unknown) {
        errorMsg.value = getErrorMessage(err, 'Error al filtrar existencias.');
    } finally {
        loading.value = false;
    }
}

// Crear Proveedor
async function handleCreateSupplier() {
    if (!newSupplier.value.name) return;
    loading.value = true;
    errorMsg.value = '';
    successMsg.value = '';
    try {
        await InventoryService.createSupplier(newSupplier.value);
        newSupplier.value = { name: '', tax_id: '', phone: '', email: '', address: '' };
        successMsg.value = 'Proveedor registrado correctamente.';
        suppliers.value = await InventoryService.getSuppliers();
    } catch (err: unknown) {
        errorMsg.value = getErrorMessage(err, 'Error al registrar el proveedor. Valida el RNC/Cédula.');
    } finally {
        loading.value = false;
    }
}

// Crear Almacén
async function handleCreateWarehouse() {
    if (!newWarehouse.value.name || !newWarehouse.value.code) return;
    loading.value = true;
    errorMsg.value = '';
    successMsg.value = '';
    try {
        await InventoryService.createWarehouse(newWarehouse.value);
        newWarehouse.value = { name: '', code: '', is_default: false };
        successMsg.value = 'Almacén registrado correctamente.';
        warehouses.value = await InventoryService.getWarehouses();
    } catch (err: unknown) {
        errorMsg.value = getErrorMessage(err, 'Error al registrar el almacén. Código duplicado.');
    } finally {
        loading.value = false;
    }
}

// Ver Kardex
async function viewKardex(productPublicId: string, productName: string) {
    loading.value = true;
    errorMsg.value = '';
    kardexProductName.value = productName;
    try {
        kardexMovements.value = await InventoryService.getKardex(productPublicId);
        showKardexModal.value = true;
    } catch (err: unknown) {
        errorMsg.value = getErrorMessage(err, 'Error al obtener movimientos del producto.');
    } finally {
        loading.value = false;
    }
}

// Agregar item a compra
function addProductToPurchase(prod: Product) {
    const exists = newPurchase.value.items.some((item) => item.product_id === prod.id);
    if (!exists) {
        newPurchase.value.items.push({
            product_id: prod.id,
            product_name: prod.name,
            quantity: 1,
            cost: Number(prod.cost) || 0,
            batch_number: '',
            expires_at: '',
        });
    }
    searchProductQuery.value = '';
    showProductDropdown.value = false;
}

function removeProductFromPurchase(index: number) {
    newPurchase.value.items.splice(index, 1);
}

// Registrar Compra (Borrador)
async function handleCreatePurchase() {
    if (
        !newPurchase.value.supplier_id ||
        !newPurchase.value.warehouse_id ||
        !newPurchase.value.purchase_number ||
        newPurchase.value.items.length === 0
    ) {
        errorMsg.value = 'Por favor, completa todos los campos requeridos y añade al menos un producto.';
        return;
    }

    loading.value = true;
    errorMsg.value = '';
    successMsg.value = '';
    try {
        await InventoryService.createPurchase({
            supplier_id: newPurchase.value.supplier_id,
            warehouse_id: newPurchase.value.warehouse_id,
            purchase_number: newPurchase.value.purchase_number,
            purchase_date: newPurchase.value.purchase_date,
            notes: newPurchase.value.notes,
            items: newPurchase.value.items.map((item) => ({
                product_id: item.product_id,
                quantity: Number(item.quantity),
                cost: Number(item.cost),
                batch_number: item.batch_number || undefined,
                expires_at: item.expires_at || undefined,
            })),
        });

        // Reset
        newPurchase.value = {
            supplier_id: '',
            warehouse_id: '',
            purchase_number: '',
            purchase_date: new Date().toISOString().split('T')[0],
            notes: '',
            items: [],
        };

        successMsg.value = 'Compra registrada en borrador exitosamente.';
        purchases.value = await InventoryService.getPurchases();
    } catch (err: unknown) {
        errorMsg.value = getErrorMessage(err, 'Error al guardar la compra.');
    } finally {
        loading.value = false;
    }
}

// Confirmar Compra (Ingresar existencias)
async function confirmPurchase(publicId: string) {
    if (
        !window.confirm(
            '¿Seguro que deseas confirmar esta compra? Esto cargará la mercancía al inventario y ya no podrá modificarse.',
        )
    ) {
        return;
    }
    loading.value = true;
    errorMsg.value = '';
    successMsg.value = '';
    try {
        await InventoryService.confirmPurchase(publicId);
        successMsg.value = 'Compra confirmada. Existencias agregadas al inventario.';
        purchases.value = await InventoryService.getPurchases();
        stocks.value = await InventoryService.getStock(selectedWarehouseFilter.value || undefined);
    } catch (err: unknown) {
        errorMsg.value = getErrorMessage(err, 'Error al confirmar la compra.');
    } finally {
        loading.value = false;
    }
}

// Filtrar productos del dropdown de compras
const filteredProductsDropdown = computed(() => {
    if (!searchProductQuery.value) return availableProducts.value;
    return availableProducts.value.filter(
        (p) =>
            p.name.toLowerCase().includes(searchProductQuery.value.toLowerCase()) ||
            (p.sku && p.sku.toLowerCase().includes(searchProductQuery.value.toLowerCase())),
    );
});
</script>

<template>
    <main class="min-h-screen bg-kinetic-surface p-4 text-kinetic-ink md:p-8">
        <div class="mx-auto max-w-7xl">
            <!-- Header -->
            <header class="mb-6 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold tracking-wide text-[#3525cd]">Gestión de Stock y Almacenes</p>
                    <h1 class="mt-1 text-3xl font-bold tracking-tight">Inventario Avanzado</h1>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        class="min-h-12 rounded-lg border border-[#c7c4d8] bg-white px-4 text-base font-semibold text-[#302f39] shadow-sm hover:bg-[#f5f2ff] transition"
                        @click="loadData"
                    >
                        Refrescar
                    </button>
                </div>
            </header>

            <!-- Alerts -->
            <div v-if="errorMsg" class="mb-4 rounded-lg bg-red-50 border border-red-200 p-4 text-sm text-red-700">
                {{ errorMsg }}
            </div>
            <div
                v-if="successMsg"
                class="mb-4 rounded-lg bg-green-50 border border-green-200 p-4 text-sm text-green-700"
            >
                {{ successMsg }}
            </div>

            <!-- Tabs Navigation -->
            <nav class="mb-6 flex gap-2 border-b border-[#e4e1ee] pb-px" aria-label="Secciones del inventario">
                <button
                    v-for="tab in [
                        { id: 'stock', label: 'Existencias y Kardex' },
                        { id: 'purchases', label: 'Compras a Proveedores' },
                        { id: 'warehouses', label: 'Almacenes' },
                        { id: 'suppliers', label: 'Proveedores' },
                    ]"
                    :key="tab.id"
                    :class="[
                        'px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors min-h-11',
                        activeTab === tab.id
                            ? 'border-[#3525cd] text-[#3525cd] font-semibold'
                            : 'border-transparent text-[#464555] hover:text-[#302f39] hover:border-[#c7c4d8]',
                    ]"
                    @click="activeTab = tab.id as Tab"
                >
                    {{ tab.label }}
                </button>
            </nav>

            <!-- Tab Content: STOCK / EXISTENCIAS -->
            <div v-if="activeTab === 'stock'" class="grid gap-6">
                <!-- Filtro de almacén -->
                <div
                    class="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-[#c7c4d8] bg-white p-4"
                >
                    <div class="flex items-center gap-2">
                        <label for="warehouse-filter" class="text-sm font-medium text-[#464555]"
                            >Filtrar por Almacén:</label
                        >
                        <select
                            id="warehouse-filter"
                            v-model="selectedWarehouseFilter"
                            class="min-h-12 rounded-lg border border-[#c7c4d8] px-3 text-base focus:border-[#3525cd] focus:ring-1 focus:ring-[#3525cd]"
                            @change="handleWarehouseFilterChange"
                        >
                            <option value="">Todos los Almacenes</option>
                            <option v-for="w in warehouses" :key="w.id" :value="w.id">
                                {{ w.name }} ({{ w.code }})
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Tabla de Stock -->
                <div class="overflow-x-auto rounded-xl border border-[#c7c4d8] bg-white shadow-sm">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr
                                class="border-b border-[#e4e1ee] bg-[#fcfbfe] text-xs font-semibold uppercase tracking-wider text-[#464555]"
                            >
                                <th class="p-4">Producto</th>
                                <th class="p-4">Almacén</th>
                                <th class="p-4 text-right">Existencia</th>
                                <th class="p-4 text-right">Costo Promedio</th>
                                <th class="p-4 text-right">Último Costo</th>
                                <th class="p-4 text-center">Historial</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#e4e1ee]">
                            <tr
                                v-for="item in stocks"
                                :key="item.product_id + '-' + item.warehouse_id"
                                class="text-sm hover:bg-[#fcfbfe] transition-colors"
                            >
                                <td class="p-4 font-medium">{{ item.product_name }}</td>
                                <td class="p-4">{{ item.warehouse_name }}</td>
                                <td
                                    class="p-4 text-right font-bold"
                                    :class="Number(item.quantity) <= 0 ? 'text-red-600' : 'text-[#006c49]'"
                                >
                                    {{ Number(item.quantity).toFixed(2) }}
                                </td>
                                <td class="p-4 text-right">RD$ {{ Number(item.avg_cost).toFixed(2) }}</td>
                                <td class="p-4 text-right text-gray-500">
                                    RD$ {{ Number(item.last_cost).toFixed(2) }}
                                </td>
                                <td class="p-4 text-center">
                                    <button
                                        class="min-h-9 px-3 rounded bg-[#f5f2ff] hover:bg-[#e4dfff] text-[#3525cd] font-semibold text-xs transition"
                                        @click="viewKardex(item.product_id, item.product_name)"
                                    >
                                        Ver Kardex
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="stocks.length === 0">
                                <td colspan="6" class="p-8 text-center text-sm text-[#464555]">
                                    No hay existencias registradas.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab Content: COMPRAS -->
            <div v-if="activeTab === 'purchases'" class="grid gap-6 lg:grid-cols-[1fr_400px]">
                <!-- Registrar Compra -->
                <div class="rounded-2xl border border-[#c7c4d8] bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-bold mb-4">Nueva Compra a Proveedor</h2>

                    <div class="grid gap-4 md:grid-cols-2 mb-4">
                        <div>
                            <label class="block text-xs font-semibold text-[#464555] mb-1">Proveedor *</label>
                            <select
                                v-model="newPurchase.supplier_id"
                                class="w-full min-h-12 rounded-lg border border-[#c7c4d8] px-3 text-base focus:ring-[#3525cd]"
                            >
                                <option value="">Selecciona un Proveedor</option>
                                <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-[#464555] mb-1">Almacén Destino *</label>
                            <select
                                v-model="newPurchase.warehouse_id"
                                class="w-full min-h-12 rounded-lg border border-[#c7c4d8] px-3 text-base focus:ring-[#3525cd]"
                            >
                                <option value="">Selecciona un Almacén</option>
                                <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2 mb-4">
                        <div>
                            <label class="block text-xs font-semibold text-[#464555] mb-1"
                                >Nº Factura de Compra *</label
                            >
                            <input
                                v-model="newPurchase.purchase_number"
                                type="text"
                                placeholder="Ej: FAC-4091"
                                class="w-full min-h-12 rounded-lg border border-[#c7c4d8] px-3 text-base focus:ring-[#3525cd]"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-[#464555] mb-1">Fecha de Compra *</label>
                            <input
                                v-model="newPurchase.purchase_date"
                                type="date"
                                class="w-full min-h-12 rounded-lg border border-[#c7c4d8] px-3 text-base focus:ring-[#3525cd]"
                            />
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-xs font-semibold text-[#464555] mb-1">Notas u Observaciones</label>
                        <textarea
                            v-model="newPurchase.notes"
                            placeholder="Comentarios sobre la recepción o factura..."
                            class="w-full rounded-lg border border-[#c7c4d8] p-3 text-sm focus:ring-[#3525cd] min-h-[70px]"
                        ></textarea>
                    </div>

                    <!-- Buscador de productos para la compra -->
                    <div class="relative mb-6">
                        <label class="block text-xs font-semibold text-[#464555] mb-1"
                            >Añadir Productos a la Compra</label
                        >
                        <input
                            v-model="searchProductQuery"
                            type="text"
                            placeholder="Busca por nombre o SKU de producto..."
                            class="w-full min-h-12 rounded-lg border border-[#c7c4d8] px-3 text-base focus:ring-[#3525cd]"
                            @focus="showProductDropdown = true"
                        />
                        <div
                            v-if="showProductDropdown && filteredProductsDropdown.length > 0"
                            class="absolute left-0 right-0 mt-1 max-h-60 overflow-y-auto rounded-lg border border-[#c7c4d8] bg-white shadow-lg z-50 divide-y divide-gray-100"
                        >
                            <button
                                v-for="prod in filteredProductsDropdown"
                                :key="prod.id"
                                class="w-full text-left px-4 py-3 hover:bg-[#f5f2ff] text-sm transition"
                                @click="addProductToPurchase(prod)"
                            >
                                <span class="font-medium">{{ prod.name }}</span>
                                <span class="text-xs text-gray-500 block"
                                    >SKU: {{ prod.sku || 'N/A' }} · Precio sugerido: RD$ {{ prod.price }}</span
                                >
                            </button>
                        </div>
                    </div>

                    <!-- Listado de ítems agregados -->
                    <div class="mb-6">
                        <h3 class="text-sm font-bold mb-2">Artículos a Comprar</h3>
                        <div class="space-y-3">
                            <div
                                v-for="(item, idx) in newPurchase.items"
                                :key="item.product_id"
                                class="border border-[#e4e1ee] rounded-xl p-4 bg-[#fcfbfe] flex flex-col gap-3"
                            >
                                <div class="flex items-center justify-between">
                                    <span class="font-semibold text-sm">{{ item.product_name }}</span>
                                    <button
                                        class="text-red-500 hover:text-red-700 text-xs font-semibold"
                                        @click="removeProductFromPurchase(idx)"
                                    >
                                        Quitar
                                    </button>
                                </div>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                    <div>
                                        <label class="block text-[10px] uppercase font-bold text-gray-500">Cant.</label>
                                        <input
                                            v-model.number="item.quantity"
                                            type="number"
                                            min="1"
                                            class="w-full min-h-9 rounded border border-[#c7c4d8] px-2 text-sm"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-[10px] uppercase font-bold text-gray-500"
                                            >Costo Unit.</label
                                        >
                                        <input
                                            v-model.number="item.cost"
                                            type="number"
                                            min="0"
                                            class="w-full min-h-9 rounded border border-[#c7c4d8] px-2 text-sm"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-[10px] uppercase font-bold text-gray-500">Lote</label>
                                        <input
                                            v-model="item.batch_number"
                                            type="text"
                                            placeholder="Nº Lote"
                                            class="w-full min-h-9 rounded border border-[#c7c4d8] px-2 text-sm"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-[10px] uppercase font-bold text-gray-500">Vence</label>
                                        <input
                                            v-model="item.expires_at"
                                            type="date"
                                            class="w-full min-h-9 rounded border border-[#c7c4d8] px-2 text-sm"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <button
                        class="w-full min-h-12 rounded-lg bg-[#3525cd] px-5 font-semibold text-white shadow-sm hover:bg-[#271aa3] transition active:scale-[.98]"
                        @click="handleCreatePurchase"
                    >
                        Guardar Compra en Borrador
                    </button>
                </div>

                <!-- Historial de compras / Acciones -->
                <div class="space-y-4">
                    <div class="rounded-2xl border border-[#c7c4d8] bg-white p-6 shadow-sm">
                        <h2 class="text-md font-bold mb-3">Historial de Compras</h2>
                        <div class="space-y-3 max-h-[500px] overflow-y-auto">
                            <div
                                v-for="p in purchases"
                                :key="p.id"
                                class="border border-[#e4e1ee] rounded-xl p-3 bg-[#fcfbfe] flex flex-col gap-2"
                            >
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-sm">{{ p.purchase_number }}</span>
                                    <span
                                        :class="[
                                            'px-2 py-0.5 rounded text-[10px] font-bold uppercase',
                                            p.status === 'confirmed'
                                                ? 'bg-green-100 text-green-800'
                                                : 'bg-yellow-100 text-yellow-800',
                                        ]"
                                    >
                                        {{ p.status === 'confirmed' ? 'Confirmada' : 'Borrador' }}
                                    </span>
                                </div>
                                <div class="text-xs text-gray-600 space-y-1">
                                    <p><strong>Proveedor:</strong> {{ p.supplier_name }}</p>
                                    <p><strong>Almacén:</strong> {{ p.warehouse_name }}</p>
                                    <p><strong>Fecha:</strong> {{ p.purchase_date }}</p>
                                    <p class="text-sm font-bold text-[#302f39]">
                                        Total: RD$ {{ Number(p.total).toFixed(2) }}
                                    </p>
                                </div>
                                <button
                                    v-if="p.status === 'draft'"
                                    class="mt-2 w-full min-h-9 rounded bg-[#006c49] hover:bg-[#004f35] text-white font-semibold text-xs shadow-sm transition"
                                    @click="confirmPurchase(p.id)"
                                >
                                    Confirmar y Cargar Stock
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab Content: ALMACENES -->
            <div v-if="activeTab === 'warehouses'" class="grid gap-6 md:grid-cols-[350px_1fr]">
                <!-- Registrar Almacén -->
                <div class="rounded-2xl border border-[#c7c4d8] bg-white p-6 shadow-sm h-fit">
                    <h2 class="text-md font-bold mb-4">Registrar Almacén</h2>
                    <form class="space-y-4" @submit.prevent="handleCreateWarehouse">
                        <div>
                            <label class="block text-xs font-semibold text-[#464555] mb-1">Nombre *</label>
                            <input
                                v-model="newWarehouse.name"
                                type="text"
                                placeholder="Ej: Depósito Central"
                                required
                                class="w-full min-h-12 rounded-lg border border-[#c7c4d8] px-3 text-base focus:ring-[#3525cd]"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-[#464555] mb-1">Código Único *</label>
                            <input
                                v-model="newWarehouse.code"
                                type="text"
                                placeholder="Ej: ALM-CEN"
                                required
                                class="w-full min-h-12 rounded-lg border border-[#c7c4d8] px-3 text-base focus:ring-[#3525cd]"
                            />
                        </div>
                        <div class="flex items-center gap-2 py-2">
                            <input
                                id="is-default-warehouse"
                                v-model="newWarehouse.is_default"
                                type="checkbox"
                                class="h-5 w-5 rounded border-[#c7c4d8] text-[#3525cd] focus:ring-[#3525cd]"
                            />
                            <label for="is-default-warehouse" class="text-sm font-medium text-[#464555]">
                                Establecer como predeterminado
                            </label>
                        </div>
                        <button
                            type="submit"
                            class="w-full min-h-12 rounded-lg bg-[#3525cd] px-5 font-semibold text-white shadow-sm hover:bg-[#271aa3] transition"
                        >
                            Guardar Almacén
                        </button>
                    </form>
                </div>

                <!-- Listado de Almacenes -->
                <div class="overflow-x-auto rounded-xl border border-[#c7c4d8] bg-white shadow-sm">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr
                                class="border-b border-[#e4e1ee] bg-[#fcfbfe] text-xs font-semibold uppercase tracking-wider text-[#464555]"
                            >
                                <th class="p-4">Código</th>
                                <th class="p-4">Nombre</th>
                                <th class="p-4 text-center">Predeterminado</th>
                                <th class="p-4 text-center">Estado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#e4e1ee]">
                            <tr v-for="w in warehouses" :key="w.id" class="text-sm hover:bg-[#fcfbfe]">
                                <td class="p-4 font-mono font-semibold">{{ w.code }}</td>
                                <td class="p-4 font-medium">{{ w.name }}</td>
                                <td class="p-4 text-center">
                                    <span
                                        v-if="w.is_default"
                                        class="inline-block px-2 py-0.5 rounded bg-green-100 text-green-800 text-[10px] font-bold"
                                    >
                                        Sí
                                    </span>
                                    <span v-else class="text-gray-400 text-xs">-</span>
                                </td>
                                <td class="p-4 text-center">
                                    <span
                                        :class="[
                                            'inline-block w-2.5 h-2.5 rounded-full',
                                            w.is_active ? 'bg-green-600' : 'bg-gray-400',
                                        ]"
                                    />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab Content: PROVEEDORES -->
            <div v-if="activeTab === 'suppliers'" class="grid gap-6 md:grid-cols-[350px_1fr]">
                <!-- Registrar Proveedor -->
                <div class="rounded-2xl border border-[#c7c4d8] bg-white p-6 shadow-sm h-fit">
                    <h2 class="text-md font-bold mb-4">Registrar Proveedor</h2>
                    <form class="space-y-4" @submit.prevent="handleCreateSupplier">
                        <div>
                            <label class="block text-xs font-semibold text-[#464555] mb-1">Nombre Comercial *</label>
                            <input
                                v-model="newSupplier.name"
                                type="text"
                                placeholder="Ej: Distribuidora Nacional SRL"
                                required
                                class="w-full min-h-12 rounded-lg border border-[#c7c4d8] px-3 text-base focus:ring-[#3525cd]"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-[#464555] mb-1">RNC o Cédula (DGII) *</label>
                            <input
                                v-model="newSupplier.tax_id"
                                type="text"
                                placeholder="Ej: 131793916"
                                class="w-full min-h-12 rounded-lg border border-[#c7c4d8] px-3 text-base focus:ring-[#3525cd]"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-[#464555] mb-1">Teléfono</label>
                            <input
                                v-model="newSupplier.phone"
                                type="text"
                                placeholder="Ej: 809-555-0199"
                                class="w-full min-h-12 rounded-lg border border-[#c7c4d8] px-3 text-base focus:ring-[#3525cd]"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-[#464555] mb-1">Email</label>
                            <input
                                v-model="newSupplier.email"
                                type="email"
                                placeholder="proveedor@empresa.com.do"
                                class="w-full min-h-12 rounded-lg border border-[#c7c4d8] px-3 text-base focus:ring-[#3525cd]"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-[#464555] mb-1">Dirección Física</label>
                            <textarea
                                v-model="newSupplier.address"
                                placeholder="Santo Domingo, R.D."
                                class="w-full rounded-lg border border-[#c7c4d8] p-3 text-sm focus:ring-[#3525cd] min-h-[60px]"
                            ></textarea>
                        </div>
                        <button
                            type="submit"
                            class="w-full min-h-12 rounded-lg bg-[#3525cd] px-5 font-semibold text-white shadow-sm hover:bg-[#271aa3] transition"
                        >
                            Guardar Proveedor
                        </button>
                    </form>
                </div>

                <!-- Listado de Proveedores -->
                <div class="overflow-x-auto rounded-xl border border-[#c7c4d8] bg-white shadow-sm">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr
                                class="border-b border-[#e4e1ee] bg-[#fcfbfe] text-xs font-semibold uppercase tracking-wider text-[#464555]"
                            >
                                <th class="p-4">Nombre</th>
                                <th class="p-4">RNC/Cédula</th>
                                <th class="p-4">Contacto</th>
                                <th class="p-4">Dirección</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#e4e1ee]">
                            <tr v-for="s in suppliers" :key="s.id" class="text-sm hover:bg-[#fcfbfe]">
                                <td class="p-4 font-medium">{{ s.name }}</td>
                                <td class="p-4 font-mono">{{ s.tax_id || '-' }}</td>
                                <td class="p-4 text-xs">
                                    <p v-if="s.phone">{{ s.phone }}</p>
                                    <p v-if="s.email" class="text-gray-500">{{ s.email }}</p>
                                </td>
                                <td class="p-4 text-xs text-gray-600 max-w-[200px] truncate">{{ s.address || '-' }}</td>
                            </tr>
                            <tr v-if="suppliers.length === 0">
                                <td colspan="4" class="p-8 text-center text-sm text-[#464555]">
                                    No hay proveedores registrados.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- MODAL KARDEX MOVIMIENTOS -->
        <div
            v-if="showKardexModal"
            class="fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50"
            aria-modal="true"
            role="dialog"
        >
            <div
                class="bg-white rounded-2xl max-w-3xl w-full max-h-[85vh] flex flex-col overflow-hidden shadow-xl border border-[#c7c4d8]"
            >
                <!-- Header Modal -->
                <header class="p-6 border-b border-[#e4e1ee] flex items-center justify-between bg-[#fcfbfe]">
                    <div>
                        <h2 class="text-lg font-bold">Kardex de Movimientos</h2>
                        <p class="text-sm text-gray-500">{{ kardexProductName }}</p>
                    </div>
                    <button
                        class="min-h-9 min-w-9 flex items-center justify-center rounded-lg hover:bg-gray-100 text-gray-600 transition"
                        @click="showKardexModal = false"
                    >
                        ✕
                    </button>
                </header>

                <!-- Body Modal -->
                <div class="p-6 overflow-y-auto flex-1">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr
                                class="border-b border-[#e4e1ee] text-xs font-semibold uppercase tracking-wider text-gray-500 bg-gray-50"
                            >
                                <th class="py-3 px-2">Fecha/Hora</th>
                                <th class="py-3 px-2">Almacén</th>
                                <th class="py-3 px-2">Tipo</th>
                                <th class="py-3 px-2">Lote</th>
                                <th class="py-3 px-2 text-right">Cant.</th>
                                <th class="py-3 px-2 text-right">Costo</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#e4e1ee]">
                            <tr v-for="m in kardexMovements" :key="m.id" class="text-xs hover:bg-gray-50">
                                <td class="py-3 px-2 text-gray-500">{{ m.created_at }}</td>
                                <td class="py-3 px-2">{{ m.warehouse_name }}</td>
                                <td class="py-3 px-2">
                                    <span
                                        :class="[
                                            'px-1.5 py-0.5 rounded font-bold text-[9px] uppercase',
                                            m.type.endsWith('_in')
                                                ? 'bg-green-100 text-green-800'
                                                : 'bg-red-100 text-red-800',
                                        ]"
                                    >
                                        {{ m.type }}
                                    </span>
                                </td>
                                <td class="py-3 px-2 font-mono text-gray-500">{{ m.batch_number || '-' }}</td>
                                <td
                                    class="py-3 px-2 text-right font-bold"
                                    :class="m.type.endsWith('_in') ? 'text-[#006c49]' : 'text-red-600'"
                                >
                                    {{ m.type.endsWith('_in') ? '+' : '-' }}{{ Number(m.quantity).toFixed(2) }}
                                </td>
                                <td class="py-3 px-2 text-right text-gray-500">RD$ {{ Number(m.cost).toFixed(2) }}</td>
                            </tr>
                            <tr v-if="kardexMovements.length === 0">
                                <td colspan="6" class="p-8 text-center text-sm text-[#464555]">
                                    No hay movimientos registrados para este producto.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Footer Modal -->
                <footer class="p-4 border-t border-[#e4e1ee] flex justify-end bg-gray-50">
                    <button
                        class="min-h-12 rounded-lg bg-[#302f39] px-5 font-semibold text-white transition hover:bg-[#201f26]"
                        @click="showKardexModal = false"
                    >
                        Cerrar
                    </button>
                </footer>
            </div>
        </div>
    </main>
</template>
