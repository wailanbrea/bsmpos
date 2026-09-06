<script setup lang="ts">
import { computed } from 'vue';
import { RouterView, useRoute } from 'vue-router';
import AppLayout from './layouts/AppLayout.vue';

const route = useRoute();

const isBareRoute = computed(() => {
    const bareRouteNames = ['login', 'register', 'context', 'onboarding'];
    return bareRouteNames.includes(String(route.name));
});
</script>

<template>
    <AppLayout v-if="!isBareRoute">
        <RouterView />
    </AppLayout>
    <RouterView v-else />
</template>
