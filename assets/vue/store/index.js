import Vue from "vue";
import Vuex from "vuex";

import notifications from './modules/notifications';
//import session from './../../quasar/store/modules/session/';

Vue.use(Vuex);

export default new Vuex.Store({
    modules: {
        notifications,
        //session,
    }
});