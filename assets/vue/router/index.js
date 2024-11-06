import { createRouter, createWebHistory } from "vue-router"
import adminRoutes from "./admin"
import courseRoutes from "./course"
import accountRoutes from "./account"
import personalFileRoutes from "./personalfile"
import messageRoutes from "./message"
import userRoutes from "./user"
import userGroupRoutes from "./usergroup"
import userRelUserRoutes from "./userreluser"
import calendarEventRoutes from "./ccalendarevent"
import toolIntroRoutes from "./ctoolintro"
import pageRoutes from "./page"
import publicPageRoutes from "./publicPage"
import socialNetworkRoutes from "./social"
import termsRoutes from "./terms"
import fileManagerRoutes from "./filemanager"

//import courseCategoryRoutes from './coursecategory';
import documents from "./documents"
import assignments from "./assignments"
import links from "./links"
import glossary from "./glossary"
import { useSecurityStore } from "../store/securityStore"
import securityService from "../services/securityService"
import MyCourseList from "../views/user/courses/List.vue"
import MySessionList from "../views/user/sessions/SessionsCurrent.vue"
import MySessionListPast from "../views/user/sessions/SessionsPast.vue"
import MySessionListUpcoming from "../views/user/sessions/SessionsUpcoming.vue"

import MyCoursesLayout from "../layouts/MyCourses.vue"

import CourseHome from "../views/course/CourseHome.vue"

import AppIndex from "../pages/AppIndex.vue"
import CustomAppIndex from "../../../var/vue_templates/pages/AppIndex.vue"
import Home from "../pages/Home.vue"
import Login from "../pages/Login.vue"
import Faq from "../pages/Faq.vue"
import Demo from "../pages/Demo.vue"

import { useCidReqStore } from "../store/cidReq"
import courseService from "../services/courseService"

import catalogueCourses from "./cataloguecourses"
import catalogueSessions from "./cataloguesessions"
import { customVueTemplateEnabled } from "../config/env"

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: "/",
      name: "Index",
      component: customVueTemplateEnabled ? CustomAppIndex : AppIndex,
      meta: {
        requiresAuth: false,
        showBreadcrumb: false,
      },
    },
    {
      path: "/home",
      name: "Home",
      component: Home,
      meta: {
        requiresAuth: true,
      },
    },
    {
      path: "/login",
      name: "Login",
      component: Login,
      meta: {
        layout: "Empty",
        showBreadcrumb: false,
      },
    },
    {
      path: "/faq",
      name: "Faq",
      component: Faq,
      meta: {
        requiresAuth: false,
        showBreadcrumb: false,
      },
    },
    {
      path: "/demo",
      name: "Demo",
      component: Demo,
      meta: {
        requiresAuth: false,
        showBreadcrumb: false,
      },
    },
    {
      path: "/course/:id/home",
      name: "CourseHome",
      component: CourseHome,
      beforeEnter: async (to) => {
        try {
          const check = await courseService.checkLegal(to.params.id, to.query?.sid)

          if (check.redirect) {
            window.location.href = check.url

            return false
          }
        } catch (e) {
          return true
        }
      },
    },
    {
      path: "/courses",
      component: MyCoursesLayout,
      children: [
        {
          path: "",
          name: "MyCourses",
          component: MyCourseList,
          meta: { requiresAuth: true },
        },
      ],
    },
    {
      path: "/sessions",
      //redirect: '/sessions/now',
      component: MySessionList,
      children: [
        {
          path: "/sessions",
          name: "MySessions",
          component: MySessionList,
          meta: { requiresAuth: true },
        },
      ],
    },
    {
      path: "/sessions/past",
      name: "MySessionsPast",
      component: MySessionListPast,
      meta: { requiresAuth: true },
    },
    {
      path: "/sessions/upcoming",
      name: "MySessionsUpcoming",
      component: MySessionListUpcoming,
      meta: { requiresAuth: true },
    },
    fileManagerRoutes,
    termsRoutes,
    socialNetworkRoutes,
    catalogueCourses,
    catalogueSessions,
    adminRoutes,
    courseRoutes,
    //courseCategoryRoutes,
    documents,
    assignments,
    links,
    glossary,
    accountRoutes,
    personalFileRoutes,
    messageRoutes,
    userRoutes,
    userGroupRoutes,
    userRelUserRoutes,
    calendarEventRoutes,
    toolIntroRoutes,
    pageRoutes,
    publicPageRoutes,
  ],
})

router.beforeEach(async (to, from, next) => {
  const securityStore = useSecurityStore()

  if (to.matched.some(record => record.meta.requiresAuth)) {

    if (!securityStore.isLoading) {
      await securityStore.checkSession()
    }

    if (securityStore.isAuthenticated) {
      next();
    } else {
      next({
        path: "/login",
        query: { redirect: to.fullPath },
      })
    }
  } else {
    next()
  }
})

router.beforeResolve(async (to) => {
  const cidReqStore = useCidReqStore()

  let cid = parseInt(to.query?.cid ?? 0)
  const sid = parseInt(to.query?.sid ?? 0)

  if ("CourseHome" === to.name) {
    cid = parseInt(to.params?.id ?? 0)
  }

  if (cid) {
    await cidReqStore.setCourseAndSessionById(cid, sid)
  } else {
    cidReqStore.resetCid()
  }
})

export default router
