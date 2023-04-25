import { createApp } from 'vue';
import { createPinia } from 'pinia';
import AppInstaller from './AppInstaller';
import PrimeVue from 'primevue/config';
import i18n from './i18n';

const app = createApp(AppInstaller);

const pinia = createPinia();

app
    .use(PrimeVue, { ripple: false })
    .use(i18n)
    .use(pinia)

    .mount('#app');
