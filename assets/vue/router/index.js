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
import skillRoutes from "./skill"

//import courseCategoryRoutes from './coursecategory';
import documents from "./documents"
import assignments from "./assignments"
import links from "./links"
import glossary from "./glossary"
import attendance from "./attendance"
import catalogue from "./catalogue"
import { useSecurityStore } from "../store/securityStore"
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

import { customVueTemplateEnabled } from "../config/env"
import { useCourseSettings } from "../store/courseSettingStore"
import { checkIsAllowedToEdit, useUserSessionSubscription } from "../composables/userPermissions"

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
        const courseId = to.params.id
        const sessionId = to.query?.sid
        const autoLaunchKey = `course_autolaunch_${courseId}`
        const hasAutoLaunched = sessionStorage.getItem(autoLaunchKey)

        if (hasAutoLaunched === "true") {
          return true
        }

        try {
          const check = await courseService.checkLegal(courseId, sessionId)
          if (check.redirect) {
            window.location.href = check.url

            return false
          }

          const course = await courseService.findById(courseId, { sid: sessionId })
          if (!course) {
            return false
          }

          const isAllowedToEdit = await checkIsAllowedToEdit(true, true, true)
          if (isAllowedToEdit) {
            return true
          }

          const courseSettingsStore = useCourseSettings()
          await courseSettingsStore.loadCourseSettings(courseId, sessionId)

          // Document auto-launch
          const documentAutoLaunch = parseInt(courseSettingsStore.getSetting("enable_document_auto_launch"), 10) || 0
          if (documentAutoLaunch === 1 && course.resourceNode?.id) {
            sessionStorage.setItem(autoLaunchKey, "true")
            window.location.href =
              `/resources/document/${course.resourceNode.id}/?cid=${courseId}` + (sessionId ? `&sid=${sessionId}` : "")
            return false
          }

          // Exercise auto-launch
          const exerciseAutoLaunch = parseInt(courseSettingsStore.getSetting("enable_exercise_auto_launch"), 10) || 0
          if (exerciseAutoLaunch === 2) {
            sessionStorage.setItem(autoLaunchKey, "true")
            window.location.href =
              `/main/exercise/exercise.php?cid=${courseId}` + (sessionId ? `&sid=${sessionId}` : "")
            return false
          } else if (exerciseAutoLaunch === 1) {
            const exerciseId = await courseService.getAutoLaunchExerciseId(courseId, sessionId)
            if (exerciseId) {
              sessionStorage.setItem(autoLaunchKey, "true")
              window.location.href =
                `/main/exercise/overview.php?exerciseId=${exerciseId}&cid=${courseId}` +
                (sessionId ? `&sid=${sessionId}` : "")
              return false
            }
          }

          // Learning path auto-launch
          const lpAutoLaunch = parseInt(courseSettingsStore.getSetting("enable_lp_auto_launch"), 10) || 0
          if (lpAutoLaunch === 2) {
            sessionStorage.setItem(autoLaunchKey, "true")
            window.location.href = `/main/lp/lp_controller.php?cid=${courseId}` + (sessionId ? `&sid=${sessionId}` : "")
            return false
          } else if (lpAutoLaunch === 1) {
            const lpId = await courseService.getAutoLaunchLPId(courseId, sessionId)
            if (lpId) {
              sessionStorage.setItem(autoLaunchKey, "true")
              window.location.href =
                `/main/lp/lp_controller.php?lp_id=${lpId}&cid=${courseId}&action=view&isStudentView=true` +
                (sessionId ? `&sid=${sessionId}` : "")
              return false
            }
          }

          // Forum auto-launch
          const forumAutoLaunch = parseInt(courseSettingsStore.getSetting("enable_forum_auto_launch"), 10) || 0
          if (forumAutoLaunch === 1) {
            sessionStorage.setItem(autoLaunchKey, "true")
            window.location.href = `/main/forum/index.php?cid=${courseId}` + (sessionId ? `&sid=${sessionId}` : "")
            return false
          }
        } catch (error) {
          console.error("Error during CourseHome route guard:", error)
        }

        return true
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
    catalogue,
    adminRoutes,
    courseRoutes,
    //courseCategoryRoutes,
    documents,
    assignments,
    links,
    glossary,
    attendance,
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
    skillRoutes,
  ],
})

router.beforeEach(async (to, from, next) => {
  const securityStore = useSecurityStore()

  if (!securityStore.isAuthenticated) {
    sessionStorage.clear()
  }

  let cid = parseInt(to.query?.cid ?? 0)

  if ("CourseHome" === to.name) {
    cid = parseInt(to.params?.id ?? 0)
  }

  if (!cid) {
    for (const key in sessionStorage) {
      if (key.startsWith("course_autolaunch_")) {
        sessionStorage.removeItem(key)
      }
    }
  }

  if (to.matched.some((record) => record.meta.requiresAuth)) {
    if (!securityStore.isLoading) {
      await securityStore.checkSession()
    }

    if (securityStore.isAuthenticated) {
      next()
    } else {
      sessionStorage.clear()
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
  const securityStore = useSecurityStore()

  let cid = parseInt(to.query?.cid ?? 0)
  const sid = parseInt(to.query?.sid ?? 0)

  if ("CourseHome" === to.name) {
    cid = parseInt(to.params?.id ?? 0)
  }

  if (cid) {
    await cidReqStore.setCourseAndSessionById(cid, sid)

    if (cidReqStore.session) {
      const { isGeneralCoach, isCourseCoach } = useUserSessionSubscription()

      securityStore.removeRole("ROLE_CURRENT_COURSE_SESSION_TEACHER")
      securityStore.removeRole("ROLE_CURRENT_COURSE_SESSION_STUDENT")

      if (isGeneralCoach.value || isCourseCoach.value) {
        securityStore.user.roles.push("ROLE_CURRENT_COURSE_SESSION_TEACHER")
      } else {
        securityStore.user.roles.push("ROLE_CURRENT_COURSE_SESSION_STUDENT")
      }
    } else {
      const isTeacher = cidReqStore.course.teachers.some((userSubscription) => {
        return 0 === userSubscription.relationType && userSubscription.user["@id"] === securityStore.user["@id"]
      })

      if (isTeacher) {
        securityStore.user.roles.push("ROLE_CURRENT_COURSE_TEACHER")
      } else {
        securityStore.user.roles.push("ROLE_CURRENT_COURSE_STUDENT")
      }
    }
  } else {
    cidReqStore.resetCid()
  }
})

export default router
