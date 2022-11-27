import { createApp } from 'vue';
import AppInstaller from './AppInstaller';
import PrimeVue from 'primevue/config';
import i18n from './i18n';

const app = createApp(AppInstaller);

app
    .use(PrimeVue, { ripple: false })
    .use(i18n)

    .mount('#app');
