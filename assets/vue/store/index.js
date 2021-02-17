import Vue from "vue";
import Vuex from "vuex";
import notifications from './modules/notifications';
import SecurityModule from "./security";
import createPersistedState from "vuex-persistedstate";

Vue.use(Vuex);

export default new Vuex.Store({
    plugins: [createPersistedState()],
    modules: {
        notifications,
        security: SecurityModule,
    }
});