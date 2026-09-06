import { createI18n } from 'vue-i18n';
import es from './locales/es';
import en from './locales/en';

export type AppLocale = 'es' | 'en';

const defaultLocale: AppLocale = (localStorage.getItem('omnipos_locale') as AppLocale) || 'es';

export const i18n = createI18n({
    legacy: false,
    locale: defaultLocale,
    fallbackLocale: 'es',
    messages: {
        es,
        en,
    },
});

export function setLanguage(lang: AppLocale): void {
    if (i18n.global.locale) {
        // En vue-i18n v11 (legacy: false), locale es un Ref reactivo
        (i18n.global.locale as unknown as { value: string }).value = lang;
        localStorage.setItem('omnipos_locale', lang);
        document.documentElement.lang = lang;
    }
}

export function getCurrentLanguage(): AppLocale {
    const loc = (i18n.global.locale as unknown as { value: string }).value;
    return (loc === 'en' ? 'en' : 'es');
}

export default i18n;
