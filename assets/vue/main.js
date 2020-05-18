import Vue from "vue";
import App from "./App";
import router from "./router";
import store from "./store";
import courseCategoryService from './services/coursecategory';
import documentsService from './services/documents';
import courseService from './services/course';
import makeCrudModule from './store/modules/crud';

// import '@mdi/font/css/materialdesignicons.css'

/*router.beforeEach((to, from, next) => {
    // hack to allow for forward slashes in path ids
    if (to.fullPath.includes('%2F')) {
        next(to.fullPath.replace('%2F', '/'));
    }
    next();
});*/

import vuetify from './plugins/vuetify' // path to vuetify export

import ApolloClient from 'apollo-boost'
const apolloClient = new ApolloClient({
    // You should use an absolute URL here
    uri: '/api/graphql/'
})

import VueApollo from 'vue-apollo';
Vue.use(VueApollo);

import Vuelidate from 'vuelidate';
import i18n from './i18n';
Vue.config.productionTip = false;
Vue.use(Vuelidate);

const apolloProvider = new VueApollo({
    defaultClient: apolloClient,
});

//import './quasar'

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

Vue.config.productionTip = false;
if (document.getElementById('app')) {
    new Vue({
        vuetify,
        i18n,
        components: {App},
        apolloProvider,
        data: {},
        store,
        router,
        render: h => h(App)
    }).$mount("#app");
}
