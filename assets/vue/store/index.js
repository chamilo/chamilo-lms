import Vue from "vue";
import Vuex from "vuex";
import notifications from './modules/notifications';
import SecurityModule from "./security";

Vue.use(Vuex);

export default new Vuex.Store({
    modules: {
        notifications,
        security: SecurityModule,
        //session,
    }
});