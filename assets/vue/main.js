import Vue from 'vue';
import App from './App';
import i18n from './i18n';
import router from './router';
import store from './store';
import courseCategoryService from './services/coursecategory';
import documentsService from './services/documents';
import courseService from './services/course';
import resourceLinkService from './services/resourcelink';
import resourceNodeService from './services/resourcenode';
import makeCrudModule from './store/modules/crud';

require('@fancyapps/fancybox');
require('@fancyapps/fancybox/dist/jquery.fancybox.css');
import Vuelidate from 'vuelidate';


/*
import VueApollo from 'vue-apollo';
import ApolloClient from 'apollo-boost';
const apolloClient = new ApolloClient({
    // You should use an absolute URL here
    uri: '/api/graphql/'
});*/

import { BootstrapVue } from 'bootstrap-vue';
// Install BootstrapVue
Vue.use(BootstrapVue);
// Optionally install the BootstrapVue icon components plugin
//Vue.use(IconsPlugin)

import { library } from '@fortawesome/fontawesome-svg-core';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { fas } from '@fortawesome/free-solid-svg-icons';
library.add(fas);
Vue.component('font-awesome-icon', FontAwesomeIcon);

Vue.config.productionTip = true;
Vue.use(Vuelidate);
//Vue.use(VueApollo);
Vue.use(require('vue-moment'));

import Toast from 'vue-toastification';
import 'vue-toastification/dist/index.css';

Vue.use(Toast, {
    transition: 'Vue-Toastification__bounce',
    maxToasts: 20,
    newestOnTop: true
});

/*const apolloProvider = new VueApollo({
    defaultClient: apolloClient,
});*/

const prettyBytes = require('pretty-bytes');
Vue.filter('prettyBytes', function (num) {
    return prettyBytes(num);
});

import flatPickr from 'vue-flatpickr-component';
import 'flatpickr/dist/flatpickr.css';
Vue.component('flat-pickr', flatPickr);

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

if (document.getElementById('app')) {
    new Vue({
        i18n,
        components: {App},
        //apolloProvider,
        data: {},
        store,
        router,
        mounted() {
        },
        render: h => h(App)
    }).$mount('#app');
}
