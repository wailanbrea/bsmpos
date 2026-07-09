import { createApp } from 'vue';
import { createPinia } from 'pinia';
import { createI18n } from 'vue-i18n';
import App from './App.vue';
import './bootstrap';
import router from './router';

const i18n = createI18n({
    legacy: false,
    locale: 'es-DO',
    fallbackLocale: 'es',
    messages: {
        'es-DO': {},
        es: {},
    },
});

createApp(App).use(createPinia()).use(router).use(i18n).mount('#app');
