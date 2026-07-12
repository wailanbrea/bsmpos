<script setup lang="ts">
import axios from 'axios';
import { computed, onMounted, ref, watch } from 'vue';
import { RouterLink } from 'vue-router';
import { createProduct, fetchCategories, fetchProducts, updateProduct } from '../services';
import type { Category, Product, ProductForm } from '../types';

const products = ref<Product[]>([]);
const categories = ref<Category[]>([]);
const search = ref('');
const loading = ref(true);
const saving = ref(false);
const error = ref<string | null>(null);
const selected = ref<Product | null>(null);
const form = ref<ProductForm>(emptyForm());
const editing = computed(() => selected.value !== null);

let searchTimer: ReturnType<typeof window.setTimeout> | undefined;

function emptyForm(): ProductForm {
    return {
        name: '',
        sku: '',
        barcode: '',
        category_id: null,
        price: 0,
        cost: 0,
        track_inventory: false,
        available_pos: true,
        variants: [],
        modifiers: [],
        combos: [],
    };
}

function select(product: Product | null): void {
    selected.value = product;
    error.value = null;
    form.value = product
        ? {
              name: product.name,
              sku: product.sku ?? '',
              barcode: product.barcode ?? '',
              category_id: product.category_id,
              price: Number(product.price),
              cost: Number(product.cost),
              track_inventory: product.track_inventory,
              available_pos: product.available_pos,
              variants: product.variants ? JSON.parse(JSON.stringify(product.variants)) : [],
              modifiers: product.modifiers ? JSON.parse(JSON.stringify(product.modifiers)) : [],
              combos: product.combos ? JSON.parse(JSON.stringify(product.combos)) : [],
          }
        : emptyForm();
}

function addVariant(): void {
    if (!form.value.variants) form.value.variants = [];
    form.value.variants.push({
        name: '',
        sku: '',
        barcode: '',
        price: null,
        cost: null,
        is_active: true,
    });
}

function removeVariant(index: number): void {
    form.value.variants?.splice(index, 1);
}

function addModifier(): void {
    if (!form.value.modifiers) form.value.modifiers = [];
    form.value.modifiers.push({
        name: '',
        required: false,
        multiselect: false,
        min_options: 0,
        max_options: 0,
        options: [],
    });
}

function removeModifier(index: number): void {
    form.value.modifiers?.splice(index, 1);
}

function addOption(modifierIndex: number): void {
    const modifier = form.value.modifiers?.[modifierIndex];
    if (modifier) {
        modifier.options.push({
            name: '',
            price: 0,
            cost: null,
            is_active: true,
        });
    }
}

function removeOption(modifierIndex: number, optionIndex: number): void {
    form.value.modifiers?.[modifierIndex]?.options.splice(optionIndex, 1);
}

function addComboItem(): void {
    if (!form.value.combos) form.value.combos = [];
    // Evitamos agregarse a sí mismo seleccionando el primer producto no propio
    const firstOther = products.value.find((p) => p.id !== selected.value?.id);
    form.value.combos.push({
        child_product_id: firstOther?.id || '',
        quantity: 1,
        extra_price: 0,
    });
}

function removeComboItem(index: number): void {
    form.value.combos?.splice(index, 1);
}

function message(exception: unknown): string {
    if (axios.isAxiosError(exception)) {
        const details = exception.response?.data?.error?.details;
        if (details && typeof details === 'object') {
            const first = Object.values(details)[0];
            if (Array.isArray(first) && first.length > 0) {
                return String(first[0]);
            }
        }
        return exception.response?.data?.error?.message ?? 'No se pudo completar la operación.';
    }
    return 'No se pudo completar la operación.';
}

async function load(): Promise<void> {
    loading.value = true;
    error.value = null;
    try {
        products.value = await fetchProducts(search.value.trim());
        if (categories.value.length === 0) {
            categories.value = await fetchCategories();
        }
    } catch (exception) {
        error.value = message(exception);
    } finally {
        loading.value = false;
    }
}

async function save(): Promise<void> {
    saving.value = true;
    error.value = null;
    try {
        if (selected.value) {
            await updateProduct(selected.value.id, form.value);
        } else {
            await createProduct(form.value);
        }
        await load();
        select(null);
    } catch (exception) {
        error.value = message(exception);
    } finally {
        saving.value = false;
    }
}

watch(search, () => {
    window.clearTimeout(searchTimer);
    searchTimer = window.setTimeout(() => void load(), 300);
});

onMounted(() => {
    void load();
});
</script>

<template>
    <main class="min-h-screen bg-kinetic-surface p-4 text-kinetic-ink md:p-8">
        <div class="mx-auto max-w-7xl">
            <RouterLink to="/" class="inline-flex min-h-11 items-center text-sm font-semibold text-[#3525cd]"
                >← Volver al panel</RouterLink
            >
            <header class="mt-3 border-b border-[#c7c4d8] pb-6">
                <p class="text-xs font-bold uppercase tracking-[.14em] text-[#3525cd]">Catálogo</p>
                <h1 class="mt-2 text-3xl font-bold tracking-tight">Productos</h1>
                <p class="mt-2 text-sm text-[#464555]">
                    Precios y costos en DECIMAL, control de inventario opcional y disponibilidad en POS.
                </p>
            </header>

            <div v-if="error" class="mt-6 rounded-xl bg-[#ffdad6] p-4 text-sm text-[#93000a]" role="alert">
                {{ error }}
            </div>

            <div class="mt-6 grid gap-5 lg:grid-cols-[1.3fr_.8fr]">
                <section class="rounded-2xl border border-[#c7c4d8] bg-white p-3">
                    <div class="flex flex-wrap items-center justify-between gap-3 px-2 pb-3">
                        <input
                            v-model="search"
                            type="search"
                            placeholder="Buscar por nombre, SKU o código…"
                            class="min-h-12 flex-1 rounded-lg border border-[#c7c4d8] px-3 text-base"
                        />
                        <button
                            class="min-h-11 rounded-lg px-3 text-base font-bold text-[#3525cd] hover:bg-[#f0ecf9]"
                            @click="select(null)"
                        >
                            Nuevo producto
                        </button>
                    </div>

                    <div v-if="loading" class="space-y-2 p-2">
                        <div v-for="row in 5" :key="row" class="h-16 animate-pulse rounded-xl bg-[#e4e1ee]" />
                    </div>
                    <p v-else-if="products.length === 0" class="p-5 text-sm text-[#464555]">
                        No se encontraron productos.
                    </p>
                    <button
                        v-for="product in products"
                        v-else
                        :key="product.id"
                        class="mb-2 flex w-full items-center justify-between gap-3 rounded-xl border p-4 text-left transition hover:border-[#4f46e5]"
                        :class="selected?.id === product.id ? 'border-[#3525cd] bg-[#f5f2ff]' : 'border-[#e4e1ee]'"
                        @click="select(product)"
                    >
                        <span>
                            <span class="block font-bold">{{ product.name }}</span>
                            <span class="mt-1 block text-sm text-[#464555]">
                                <span v-if="product.sku" class="font-mono">{{ product.sku }}</span>
                                <span v-if="product.category"> · {{ product.category }}</span>
                            </span>
                        </span>
                        <span class="font-numeric text-lg font-bold text-[#3525cd]">RD$ {{ product.price }}</span>
                    </button>
                </section>

                <form class="h-fit rounded-2xl border border-[#c7c4d8] bg-white p-5 shadow-sm" @submit.prevent="save">
                    <p class="text-xs font-bold uppercase tracking-[.12em] text-[#006c49]">
                        {{ editing ? 'Editar producto' : 'Nuevo producto' }}
                    </p>
                    <div class="mt-4 grid gap-4">
                        <label class="grid gap-2 text-sm font-semibold"
                            >Nombre
                            <input
                                v-model.trim="form.name"
                                required
                                maxlength="150"
                                class="min-h-12 rounded-lg border border-[#c7c4d8] px-3 text-base font-normal"
                            />
                        </label>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <label class="grid gap-2 text-sm font-semibold"
                                >SKU
                                <input
                                    v-model.trim="form.sku"
                                    maxlength="60"
                                    class="min-h-12 rounded-lg border border-[#c7c4d8] px-3 font-mono text-base font-normal"
                                />
                            </label>
                            <label class="grid gap-2 text-sm font-semibold"
                                >Código de barras
                                <input
                                    v-model.trim="form.barcode"
                                    maxlength="60"
                                    class="min-h-12 rounded-lg border border-[#c7c4d8] px-3 font-mono text-base font-normal"
                                />
                            </label>
                        </div>
                        <label class="grid gap-2 text-sm font-semibold"
                            >Categoría
                            <select
                                v-model="form.category_id"
                                class="min-h-12 rounded-lg border border-[#c7c4d8] px-3 text-base font-normal"
                            >
                                <option :value="null">Sin categoría</option>
                                <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                            </select>
                        </label>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <label class="grid gap-2 text-sm font-semibold"
                                >Precio (RD$)
                                <input
                                    v-model="form.price"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="min-h-12 rounded-lg border border-[#c7c4d8] px-3 text-base font-normal"
                                />
                            </label>
                            <label class="grid gap-2 text-sm font-semibold"
                                >Costo (RD$)
                                <input
                                    v-model="form.cost"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="min-h-12 rounded-lg border border-[#c7c4d8] px-3 text-base font-normal"
                                />
                            </label>
                        </div>
                        <label class="flex min-h-11 items-center gap-3 text-sm font-semibold">
                            <input v-model="form.track_inventory" type="checkbox" class="h-5 w-5" />
                            Controlar inventario
                        </label>
                        <label class="flex min-h-11 items-center gap-3 text-sm font-semibold">
                            <input v-model="form.available_pos" type="checkbox" class="h-5 w-5" />
                            Disponible en POS
                        </label>

                        <!-- Sección de Variantes -->
                        <div class="mt-4 border-t border-[#e4e1ee] pt-4">
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-bold text-[#3525cd]">Variantes (Talla, Color, etc.)</h3>
                                <button
                                    type="button"
                                    class="text-xs font-bold text-[#3525cd] hover:underline"
                                    @click="addVariant"
                                >
                                    + Añadir Variante
                                </button>
                            </div>
                            <div v-if="form.variants && form.variants.length > 0" class="mt-2 space-y-3">
                                <div
                                    v-for="(variant, vIdx) in form.variants"
                                    :key="vIdx"
                                    class="rounded-lg border border-[#e4e1ee] p-3 space-y-2 bg-[#fdfdfd]"
                                >
                                    <div class="flex items-center justify-between gap-2">
                                        <input
                                            v-model="variant.name"
                                            placeholder="Nombre (ej: Rojo / L)"
                                            required
                                            class="min-h-9 w-full rounded border border-[#c7c4d8] px-2 text-xs"
                                        />
                                        <button
                                            type="button"
                                            class="text-xs font-bold text-[#93000a] hover:underline"
                                            @click="removeVariant(vIdx)"
                                        >
                                            Quitar
                                        </button>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <input
                                            v-model="variant.sku"
                                            placeholder="SKU"
                                            class="min-h-9 rounded border border-[#c7c4d8] px-2 text-xs font-mono"
                                        />
                                        <input
                                            v-model="variant.barcode"
                                            placeholder="Barras"
                                            class="min-h-9 rounded border border-[#c7c4d8] px-2 text-xs font-mono"
                                        />
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <input
                                            v-model.number="variant.price"
                                            type="number"
                                            step="0.01"
                                            placeholder="Precio (opcional)"
                                            class="min-h-9 rounded border border-[#c7c4d8] px-2 text-xs"
                                        />
                                        <input
                                            v-model.number="variant.cost"
                                            type="number"
                                            step="0.01"
                                            placeholder="Costo (opcional)"
                                            class="min-h-9 rounded border border-[#c7c4d8] px-2 text-xs"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sección de Modificadores -->
                        <div class="mt-4 border-t border-[#e4e1ee] pt-4">
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-bold text-[#006c49]">Modificadores (Extras)</h3>
                                <button
                                    type="button"
                                    class="text-xs font-bold text-[#006c49] hover:underline"
                                    @click="addModifier"
                                >
                                    + Añadir Modificador
                                </button>
                            </div>
                            <div v-if="form.modifiers && form.modifiers.length > 0" class="mt-2 space-y-4">
                                <div
                                    v-for="(mod, mIdx) in form.modifiers"
                                    :key="mIdx"
                                    class="rounded-lg border border-[#e4e1ee] bg-[#fdfdfd] p-3 space-y-2"
                                >
                                    <div class="flex items-center justify-between gap-2">
                                        <input
                                            v-model="mod.name"
                                            placeholder="Ej: Extras de Hamburguesa"
                                            required
                                            class="min-h-9 w-full rounded border border-[#c7c4d8] px-2 text-xs font-bold"
                                        />
                                        <button
                                            type="button"
                                            class="text-xs font-bold text-[#93000a] hover:underline"
                                            @click="removeModifier(mIdx)"
                                        >
                                            Quitar
                                        </button>
                                    </div>
                                    <div class="flex gap-4 text-xs">
                                        <label class="flex items-center gap-1">
                                            <input v-model="mod.required" type="checkbox" /> Obligatorio
                                        </label>
                                        <label class="flex items-center gap-1">
                                            <input v-model="mod.multiselect" type="checkbox" /> Multiselección
                                        </label>
                                    </div>
                                    <!-- Opciones del Modificador -->
                                    <div class="mt-2 pl-4 border-l-2 border-[#006c49] space-y-2">
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs font-bold text-[#464555]">Opciones</span>
                                            <button
                                                type="button"
                                                class="text-xs font-semibold text-[#006c49] hover:underline"
                                                @click="addOption(mIdx)"
                                            >
                                                + Añadir Opción
                                            </button>
                                        </div>
                                        <div
                                            v-for="(opt, oIdx) in mod.options"
                                            :key="oIdx"
                                            class="grid grid-cols-[1.5fr_1fr_auto] gap-2 items-center"
                                        >
                                            <input
                                                v-model="opt.name"
                                                placeholder="Ej: Queso"
                                                required
                                                class="min-h-9 rounded border border-[#c7c4d8] px-2 text-xs"
                                            />
                                            <input
                                                v-model.number="opt.price"
                                                type="number"
                                                step="0.01"
                                                placeholder="Precio extra"
                                                class="min-h-9 rounded border border-[#c7c4d8] px-2 text-xs"
                                            />
                                            <button
                                                type="button"
                                                class="text-[#93000a] text-xs font-bold hover:underline"
                                                @click="removeOption(mIdx, oIdx)"
                                            >
                                                x
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sección de Combos -->
                        <div class="mt-4 border-t border-[#e4e1ee] pt-4">
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-bold text-[#495f54]">Composición del Combo</h3>
                                <button
                                    type="button"
                                    class="text-xs font-bold text-[#495f54] hover:underline"
                                    @click="addComboItem"
                                >
                                    + Añadir Componente
                                </button>
                            </div>
                            <div v-if="form.combos && form.combos.length > 0" class="mt-2 space-y-2">
                                <div
                                    v-for="(combo, cIdx) in form.combos"
                                    :key="cIdx"
                                    class="grid grid-cols-[1.5fr_1fr_1fr_auto] gap-2 items-center rounded-lg border border-[#e4e1ee] p-2 bg-[#fafafa]"
                                >
                                    <select
                                        v-model="combo.child_product_id"
                                        required
                                        class="min-h-9 rounded border border-[#c7c4d8] px-1 text-xs"
                                    >
                                        <option
                                            v-for="prod in products.filter((p) => p.id !== selected?.id)"
                                            :key="prod.id"
                                            :value="prod.id"
                                        >
                                            {{ prod.name }}
                                        </option>
                                    </select>
                                    <input
                                        v-model.number="combo.quantity"
                                        type="number"
                                        step="1"
                                        min="1"
                                        placeholder="Cant"
                                        required
                                        class="min-h-9 rounded border border-[#c7c4d8] px-2 text-xs"
                                    />
                                    <input
                                        v-model.number="combo.extra_price"
                                        type="number"
                                        step="0.01"
                                        placeholder="Precio extra"
                                        class="min-h-9 rounded border border-[#c7c4d8] px-2 text-xs"
                                    />
                                    <button
                                        type="button"
                                        class="text-[#93000a] text-xs font-bold hover:underline"
                                        @click="removeComboItem(cIdx)"
                                    >
                                        x
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <button
                        class="mt-6 min-h-12 rounded-lg bg-[#3525cd] px-5 text-base font-bold text-white shadow-sm disabled:opacity-60"
                        :disabled="saving"
                        type="submit"
                    >
                        {{ saving ? 'Guardando…' : editing ? 'Guardar cambios' : 'Crear producto' }}
                    </button>
                </form>
            </div>
        </div>
    </main>
</template>
