import Vue from "vue";
import App from "./App";
import router from "./router";
import store from "./store";
import courseCategoryService from './services/coursecategory';
import documentsService from './services/documents';
import courseService from './services/course';
import resourceLinkService from './services/resourcelink';
import makeCrudModule from './store/modules/crud';
import vuetify from './plugins/vuetify' // path to vuetify export
require('@fancyapps/fancybox');
require ('@fancyapps/fancybox/dist/jquery.fancybox.css');
import VueApollo from 'vue-apollo';
import Vuelidate from 'vuelidate';
import i18n from './i18n';
import ApolloClient from 'apollo-boost'
const apolloClient = new ApolloClient({
    // You should use an absolute URL here
    uri: '/api/graphql/'
});

Vue.config.productionTip = false;

Vue.use(Vuelidate);
Vue.use(VueApollo);
Vue.use(require('vue-moment'));

const apolloProvider = new VueApollo({
    defaultClient: apolloClient,
});

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
        vuetify,
        i18n,
        components: {App},
        apolloProvider,
        data: {},
        store,
        router,
        mounted() {
        },
        render: h => h(App)
    }).$mount("#app");
}
