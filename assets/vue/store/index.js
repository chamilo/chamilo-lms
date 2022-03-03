import { createStore, createLogger } from "vuex";
import notifications from './modules/notifications';
import SecurityModule from "./security";

export default createStore({
    plugins: [
        //createLogger(),
    ],
    modules: {
        notifications,
        security: SecurityModule,
    }
});