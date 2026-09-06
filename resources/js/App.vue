<script setup lang="ts">
import { computed } from 'vue';
import { RouterView, useRoute } from 'vue-router';
import AppLayout from './layouts/AppLayout.vue';
import { useSessionStore } from './modules/auth/stores/session';

const route = useRoute();
const session = useSessionStore();

const isBareRoute = computed(() => {
    const bareRouteNames = ['login', 'register', 'context', 'onboarding'];
    return !route.name || bareRouteNames.includes(String(route.name));
});

const showAppLayout = computed(() => {
    return session.isAuthenticated && session.hasContext && !isBareRoute.value;
});
</script>

<template>
    <AppLayout v-if="showAppLayout">
        <RouterView />
    </AppLayout>
    <RouterView v-else />
</template>
