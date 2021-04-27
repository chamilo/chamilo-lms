import { createApp } from 'vue';
import App from './App.vue';
import i18n from './i18n';
import router from './router';
import store from './store';
import axios from 'axios'
import courseCategoryService from './services/coursecategory';
import documentsService from './services/documents';
import courseService from './services/course';
import resourceLinkService from './services/resourcelink';
import resourceNodeService from './services/resourcenode';
import makeCrudModule from './store/modules/crud';
//import vuetify from './plugins/vuetify' // path to vuetify export

//require('@fancyapps/fancybox');
//require('@fancyapps/fancybox/dist/jquery.fancybox.css');

/*
import VueApollo from 'vue-apollo';
import ApolloClient from 'apollo-boost';
const apolloClient = new ApolloClient({
    // You should use an absolute URL here
    uri: '/api/graphql/'
});*/

import { library } from '@fortawesome/fontawesome-svg-core';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { fas } from '@fortawesome/free-solid-svg-icons';
library.add(fas);
//Vue.use(Vuelidate);
//Vue.use(VueApollo);

import Toast from 'vue-toastification';
//import 'vue-toastification/dist/index.css';
const toastOptions = {
    transition: 'Vue-Toastification__fade',
    maxToasts: 20,
    newestOnTop: true
};
/*const apolloProvider = new VueApollo({
    defaultClient: apolloClient,
});*/


import VueFlatPickr from 'vue-flatpickr-component';
//import 'flatpickr/dist/flatpickr.css';

store.registerModule(
    'course',
    makeCrudModule({
        service: courseService
    })
);

store.registerModule(
    'resourcelink',
    makeCrudModule({
        service: resourceLinkService
    })
);

store.registerModule(
    'resourcenode',
    makeCrudModule({
        service: resourceNodeService
    })
);

store.registerModule(
    'coursecategory',
    makeCrudModule({
        service: courseCategoryService
    })
);

store.registerModule(
    'documents',
    makeCrudModule({
        service: documentsService
    })
);

// Vuetify.
/*import '@mdi/font/css/materialdesignicons.css';
import { createVuetify } from 'vuetify';
import 'vuetify/lib/styles/main.sass';
import * as components from 'vuetify/lib/components';
import * as directives from 'vuetify/lib/directives';
import { aliases, mdi } from 'vuetify/lib/iconsets/mdi'

const options = {
    components,
    directives,
    defaults: {
        global: {
            ripple: false,
        },
        VSheet: {
            elevation: 4,
        },
    },
    icons: {
        defaultSet: 'mdi',
        aliases,
        sets: {
            mdi,
        }
    },
    theme: {
        defaultTheme: 'light'
    },
}
const vuetify = createVuetify(options);*/

import DashboardLayout from './components/layout/DashboardLayout.vue'
import EmptyLayout from './components/layout/EmptyLayout.vue'

// Vue setup.
const app = createApp(App);

// Quasar
import { Quasar } from 'quasar'
import quasarUserOptions from './quasar-user-options'

// Prime
import PrimeVue from 'primevue/config'
import DataView from 'primevue/dataview';
import DataTable from 'primevue/datatable';
import Dropdown from 'primevue/dropdown';
import Toolbar from 'primevue/toolbar';
import DataViewLayoutOptions from 'primevue/dataviewlayoutoptions';

import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import Column from 'primevue/column';
import ColumnGroup from 'primevue/columngroup';

import 'primevue/resources/themes/mdc-light-indigo/theme.css';
import 'primevue/resources/primevue.min.css';
// import 'primeflex/primeflex.css';
import "primeicons/primeicons.css";

app.component('Dialog', Dialog);
app.component('DataView', DataView);
app.component('DataTable', DataTable);
app.component('Dropdown', Dropdown);
app.component('DataViewLayoutOptions', DataViewLayoutOptions);
app.component('InputText', InputText);
app.component('Button', Button);
app.component('Column', Column);
app.component('ColumnGroup', ColumnGroup);
app.component('Toolbar', Toolbar);
app.component('font-awesome-icon', FontAwesomeIcon);
app.component('DashboardLayout', DashboardLayout)
app.component('EmptyLayout', EmptyLayout)

app.config.globalProperties.axios = axios;

const prettyBytes = require('pretty-bytes');

const { DateTime } = require("luxon");
app.config.globalProperties.$luxonDateTime = DateTime;

app.config.globalProperties.$filters = {
    prettyBytes(num) {
        return prettyBytes(num);
    },
}

app
    .use(PrimeVue, {ripple: true})
    .use(Quasar, quasarUserOptions)
    .use(VueFlatPickr)
    //.use(VuelidatePlugin)
    //.use(vuetify)
    .use(router)
    .use(store)
    .use(i18n)
    .use(Toast, toastOptions)
;

app.mount('#app');

/*
new Vue({
    vuetify,
    i18n,
    components: {App},
    //apolloProvider,
    data: {},
    store,
    router,
    mounted() {
    },
    render: h => h(App)
}).$mount('#app');*/

