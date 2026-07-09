<script setup lang="ts">
const navigation = ['Resumen', 'Ventas', 'Inventario', 'Clientes', 'Configuración'];
</script>

<template>
    <main class="min-h-screen bg-kinetic-surface p-4 text-kinetic-ink md:p-8">
        <div class="mx-auto max-w-7xl">
            <header class="mb-6 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold tracking-wide text-[#3525cd]">OmniPOS · operación en tiempo real</p>
                    <h1 class="mt-1 text-3xl font-bold tracking-tight">Panel de control</h1>
                </div>
                <button
                    class="min-h-11 rounded-lg bg-[#3525cd] px-5 font-semibold text-white shadow-sm transition active:scale-[.98]"
                >
                    Abrir POS
                </button>
            </header>

            <section
                class="mb-6 grid gap-3 rounded-2xl border border-[#c7c4d8] bg-white p-4 shadow-sm md:grid-cols-3"
                aria-label="Estado operativo"
            >
                <div class="flex items-center gap-3 border-b border-[#e4e1ee] pb-3 md:border-r md:border-b-0 md:pb-0">
                    <span class="h-3 w-3 rounded-full bg-[#006c49]" aria-hidden="true" />
                    <div>
                        <p class="text-xs text-[#464555]">Conexión</p>
                        <p class="font-semibold">En línea</p>
                    </div>
                </div>
                <div class="border-b border-[#e4e1ee] pb-3 md:border-r md:border-b-0 md:pb-0">
                    <p class="text-xs text-[#464555]">Sucursal activa</p>
                    <p class="font-semibold">Pendiente de selección</p>
                </div>
                <div>
                    <p class="text-xs text-[#464555]">Caja</p>
                    <p class="font-semibold">Requiere apertura</p>
                </div>
            </section>

            <div class="grid gap-6 lg:grid-cols-[220px_1fr]">
                <nav class="rounded-2xl bg-[#302f39] p-3 text-[#f3effc]" aria-label="Navegación principal">
                    <p class="px-3 py-2 text-xs font-semibold uppercase tracking-wider text-[#c3c0ff]">Módulos</p>
                    <a
                        v-for="item in navigation"
                        :key="item"
                        href="#"
                        class="flex min-h-11 items-center rounded-lg px-3 text-sm font-medium hover:bg-white/10 focus:bg-white/10"
                        >{{ item }}</a
                    >
                </nav>
                <section
                    class="rounded-2xl border border-dashed border-[#c7c4d8] bg-[#f5f2ff] p-8"
                    aria-labelledby="setup-title"
                >
                    <p class="text-sm font-semibold text-[#006c49]">Base técnica lista</p>
                    <h2 id="setup-title" class="mt-2 text-2xl font-semibold">
                        Configura tu empresa para empezar a vender.
                    </h2>
                    <p class="mt-3 max-w-2xl text-[#464555]">
                        El onboarding, los módulos y las métricas aparecerán aquí cuando se complete el núcleo SaaS.
                    </p>
                </section>
            </div>
        </div>
    </main>
</template>
