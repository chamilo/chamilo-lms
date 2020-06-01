import Vue from "vue";
import VueRouter from "vue-router";

Vue.use(VueRouter);

import courseRoutes from './course';
import courseCategoryRoutes from './coursecategory';
import documents from './documents';
import store from "../store";
import Login from "../views/Login";
//import Legacy from "../views/Legacy";
import Home from "../views/Home";
import MyCourseList from "../views/user/courses/List";
import MySessionList from "../views/user/sessions/List";

let router = new VueRouter({
    mode: "history",
    routes: [
        { path: "/", name: "Home", component: Home },
        { path: "/login", component: Login },
        { path: "/courses", name: "MyCourses", component: MyCourseList },
        { path: "/sessions", name: "MySessions", component: MySessionList },
        courseRoutes,
        courseCategoryRoutes,
        documents
    ]
});

router.beforeEach((to, from, next) => {
    if (to.matched.some(record => record.meta.requiresAuth)) {
        // this route requires auth, check if logged in
        // if not, redirect to login page.
        if (store.getters["security/isAuthenticated"]) {
            next();
        } else {
            next({
                path: "/login",
                query: { redirect: to.fullPath },
            });
        }
    } else {
        next(); // make sure to always call next()!
    }
});

export default router;
