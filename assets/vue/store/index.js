import { createStore, createLogger } from "vuex";
import notifications from './modules/notifications';
import SecurityModule from "./security";
import createPersistedState from "vuex-persistedstate";

export default createStore({
    plugins: [
        createPersistedState(),
        //createLogger(),
    ],
    modules: {
        notifications,
        security: SecurityModule,
    }
});