import { createRouter, createWebHistory } from "vue-router"
import adminRoutes from "./admin"
import sessionAdminRoutes from "./sessionAdmin"
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
import pageLayoutRoutes from "./pageLayout"
import publicPageRoutes from "./publicPage"
import socialNetworkRoutes from "./social"
import fileManagerRoutes from "./filemanager"
import skillRoutes from "./skill"
import accessUrlRoutes from "./accessurl"
import branchRoutes from "./branch"
import roomRoutes from "./room"

//import courseCategoryRoutes from './coursecategory';
import documents from "./documents"
import assignments from "./assignments"
import links from "./links"
import glossary from "./glossary"
import attendance from "./attendance"
import lpRoutes from "./lp"
import dropboxRoutes from "./dropbox"
import blogRoutes from "./blog"
import blogAdminRoute from "./blogAdmin"
import courseMaintenanceRoute from "./coursemaintenance"
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

/**
 * Applies "page-*" marker classes on both the DOM marker and the <body>.
 * This keeps stable theming hooks during SPA navigation (issue #6047).
 *
 * Note: Twig/PageHelper already sets initial classes on first load. This only syncs on route changes.
 */
function applyPageTypeClasses(classes) {
  const marker = document.querySelector(".page-marker")
  const body = document.body

  const clearPageClasses = (el) => {
    if (!el) return
    ;[...el.classList].forEach((c) => {
      if (c.startsWith("page-")) el.classList.remove(c)
    })
  }

  clearPageClasses(marker)
  clearPageClasses(body)
  ;(classes || []).forEach((c) => {
    if (!c || typeof c !== "string") return
    if (marker) marker.classList.add(c)
    body.classList.add(c)
  })
}

/**
 * Derives stable page marker classes from the current route.
 * We intentionally avoid hardcoding all routes. PageHelper handles legacy PHP pages.
 *
 * @returns {string[]}
 */
function derivePageTypeClasses(to) {
  const p = String(to?.path || "/")

  // Canonical aliases requested by the issue
  if (p === "/" || p.startsWith("/home")) return ["page-home"]
  if (p.startsWith("/courses")) return ["page-my-courses"]
  if (p.startsWith("/catalogue")) return ["page-catalogue"]
  if (p.startsWith("/social")) return ["page-social"]
  if (p.startsWith("/account")) return ["page-account-security"]
  if (p.startsWith("/admin-dashboard")) return ["page-administration", "page-administration-session"]
  if (p.startsWith("/admin")) return ["page-administration", "page-administration-platform"]
  if (p.startsWith("/tracking")) return ["page-tracking"]

  // Vue "resources" module routes -> optional tool markers (documents, lp, attendance, etc.)
  if (p.startsWith("/resources/")) {
    const segs = p.split("/").filter(Boolean) // ["resources", "<tool>", ...]
    const tool = segs[1] || "generic"
    const toolSlug = tool.replace(/[^a-z0-9\-_]+/gi, "-").toLowerCase()
    return ["page-tool", `page-tool-${toolSlug}`]
  }

  // Generic fallback: page-<first segment>
  const seg0 = p.split("/").filter(Boolean)[0] || "generic"
  return [`page-${seg0.replace(/[^a-z0-9\-_]+/gi, "-").toLowerCase()}`]
}

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
      path: '/resources/accessurl/:id/delete',
      name: 'AccessUrlDelete',
      component: () => import('../views/accessurl/DeleteAccessUrl.vue'),
      props: route => ({ id: Number(route.params.id) })
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
    lpRoutes,
    dropboxRoutes,
    blogRoutes,
    blogAdminRoute,
    courseMaintenanceRoute,
    accountRoutes,
    personalFileRoutes,
    messageRoutes,
    userRoutes,
    userGroupRoutes,
    userRelUserRoutes,
    calendarEventRoutes,
    toolIntroRoutes,
    pageRoutes,
    pageLayoutRoutes,
    publicPageRoutes,
    skillRoutes,
    sessionAdminRoutes,
    accessUrlRoutes,
    branchRoutes,
    roomRoutes,
  ],
})

router.beforeEach(async (to, from, next) => {
  document.body.classList.add("cursor-wait")

  const securityStore = useSecurityStore()
  const preservedParams = ["origin", "isStudentView"]
  const mergedQuery = { ...to.query }

  let shouldRedirect = false

  for (const key of preservedParams) {
    if (from.query[key] && !to.query[key]) {
      mergedQuery[key] = from.query[key]
      shouldRedirect = true
    }
  }

  if (shouldRedirect) {
    next({
      ...to,
      query: mergedQuery,
    })
    return
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

  // Determine what the route requires
  const needsAuth = to.matched.some((record) => record.meta?.requiresAuth === true)
  const wantsAdmin = to.matched.some((record) => record.meta?.requiresAdmin === true)
  const wantsSessionAdmin = to.matched.some((record) => record.meta?.requiresSessionAdmin === true)

  const mustBeLogged = needsAuth || wantsAdmin || wantsSessionAdmin

  if (mustBeLogged && !securityStore.isLoading) {
    await securityStore.checkSession()
  }

  // If user must be logged but is not, send to login
  if (mustBeLogged && !securityStore.isAuthenticated) {
    sessionStorage.clear()
    next({
      path: "/login",
      query: { redirect: to.fullPath },
    })
    return
  }

  // Role-based access control: admin / session-admin
  if (wantsAdmin || wantsSessionAdmin) {
    let allowed = true

    if (wantsAdmin && wantsSessionAdmin) {
      // Route can be accessed by platform admins OR session admins
      allowed = !!securityStore.isAdmin || !!securityStore.isSessionAdmin
    } else if (wantsAdmin) {
      // Only platform admins
      allowed = !!securityStore.isAdmin
    } else if (wantsSessionAdmin) {
      // Only session admins
      allowed = !!securityStore.isSessionAdmin
    }

    if (!allowed) {
      // Authenticated but not enough privileges
      next({ name: "Home", replace: true })
      return
    }
  }

  // Public route or user is allowed
  next()
})

router.afterEach((to) => {
  // Always remove the loading cursor.
  document.body.classList.remove("cursor-wait")

  // Keep page marker classes in sync for SPA navigation.
  // This is required because Twig/PageHelper does not run on client-side route changes.
  try {
    applyPageTypeClasses(derivePageTypeClasses(to))
  } catch (e) {
    // Never block navigation because of marker updates.
    console.error("Error applying page marker classes:", e)
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
