import Vue from "vue";
import VueRouter from "vue-router";

Vue.use(VueRouter);

import courseRoutes from './course';
import courseCategoryRoutes from './coursecategory';
import documents from './documents';

export default new VueRouter({
    mode: "history",
    routes: [
        courseRoutes,
        courseCategoryRoutes,
        documents
    ]
});
