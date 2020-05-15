import Vue from "vue";
import VueRouter from "vue-router";

Vue.use(VueRouter);

import courseRoutes from './course';
import coursecategoryRoutes from './coursecategory';
import sessionRoutes from './../../quasar/router/session';

export default new VueRouter({
    mode: "history",
    routes: [
        courseRoutes,
        ...sessionRoutes,
        coursecategoryRoutes,
        // { path: "*", redirect: "/home" }
    ]
});
