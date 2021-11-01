import { createApp } from 'vue';
import App from './App.vue';
import i18n from './i18n';
import router from './router';
import store from './store';
import axios from 'axios'

// Services.

import courseCategoryService from './services/coursecategory';
import documentsService from './services/documents';
import courseService from './services/course';
import personalFileService from './services/personalfile';
import resourceLinkService from './services/resourcelink';
import resourceNodeService from './services/resourcenode';
import messageService from './services/message';
import messageAttachmentService from './services/messageattachment';
import messageRelUserService from './services/messagereluser';
import userService from './services/user';
import userGroupService from './services/usergroup';
import userRelUserService from './services/userreluser';
import calendarEventService from './services/ccalendarevent';
import toolIntroService from './services/ctoolintro';
import pageService from './services/page';
import pageCategoryService from './services/pagecategory';

import makeCrudModule from './store/modules/crud';
//import vuetify from './plugins/vuetify' // path to vuetify export

//require('@fancyapps/fancybox');
//require('@fancyapps/fancybox/dist/jquery.fancybox.css');

//Vue.use(Vuelidate);

import Toast from 'vue-toastification';
//import 'vue-toastification/dist/index.css';
const toastOptions = {
    transition: 'Vue-Toastification__fade',
    maxToasts: 20,
    newestOnTop: true
};
import VueFlatPickr from 'vue-flatpickr-component';
import 'flatpickr/dist/flatpickr.css';

// @todo move in a file:
store.registerModule(
    'course',
    makeCrudModule({
        service: courseService
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

store.registerModule(
    'ccalendarevent',
    makeCrudModule({
        service: calendarEventService
    })
);

store.registerModule(
  'ctoolintro',
  makeCrudModule({
    service: toolIntroService
  })
);

store.registerModule(
    'page',
    makeCrudModule({
        service: pageService
    })
);

store.registerModule(
    'pagecategory',
    makeCrudModule({
        service: pageCategoryService
    })
);


store.registerModule(
    'personalfile',
    makeCrudModule({
        service: personalFileService
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
    'message',
    makeCrudModule({
        service: messageService
    })
);

store.registerModule(
    'messageattachment',
    makeCrudModule({
        service: messageAttachmentService
    })
);

store.registerModule(
    'messagereluser',
    makeCrudModule({
        service: messageRelUserService
    })
);

store.registerModule(
    'userreluser',
    makeCrudModule({
        service: userRelUserService
    })
);

store.registerModule(
    'user',
    makeCrudModule({
        service: userService
    })
);

store.registerModule(
    'usergroup',
    makeCrudModule({
        service: userGroupService
    })
);

// Vuetify.
import '@mdi/font/css/materialdesignicons.css';
//import 'vuetify/lib/styles/main.sass';
import 'vuetify/styles'
import { createVuetify } from 'vuetify';
//import * as components from 'vuetify/lib/components';
//import * as directives from 'vuetify/lib/directives';
import { aliases, mdi } from 'vuetify/lib/iconsets/mdi'

const options = {
//    components,
//    directives,
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
const vuetify = createVuetify(options);

import DashboardLayout from './components/layout/DashboardLayout.vue'
import EmptyLayout from './components/layout/EmptyLayout.vue'

// Vue setup.
const app = createApp(App);

// Quasar
import { Quasar } from 'quasar'
import quasarUserOptions from './quasar-user-options'

// Tinymce
import Editor from './components/Editor';

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
app.component('DashboardLayout', DashboardLayout);
app.component('EmptyLayout', EmptyLayout);
app.component('TinyEditor', Editor);

app.config.globalProperties.axios = axios;
const prettyBytes = require('pretty-bytes');
const { DateTime } = require("luxon");

app.config.globalProperties.$filters = {
    /**
     * @param {string} datetime
     * @returns {string}
     */
    abbreviatedDatetime(datetime) {
        return DateTime.fromISO(datetime).toLocaleString({ ...DateTime.DATETIME_MED, month: 'long' });
    },
    /**
     * @param {string} datetime
     * @returns {string}
     */
    relativeDatetime(datetime) {
        return DateTime.fromISO(datetime).toRelative();
    },
    prettyBytes,
}

import Alpine from 'alpinejs'
window.Alpine = Alpine
Alpine.start();

app
    .use(PrimeVue, {ripple: true})
    .use(Quasar, quasarUserOptions)
    .use(VueFlatPickr)
    //.use(VuelidatePlugin)
    .use(vuetify)
    .use(router)
    .use(store)
    .use(i18n)
    .use(Toast, toastOptions)
;

app.mount('#app');
