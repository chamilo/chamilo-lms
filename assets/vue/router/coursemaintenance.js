import { useCidReqStore } from "../store/cidReq"
import { checkIsAllowedToEdit } from "../composables/userPermissions"

/**
 * Course Maintenance is a teacher-only course tool (admin category): students
 * and teachers of other courses must not reach any of its pages.
 *
 * Runs as beforeEnter (before beforeResolve), so the course context is loaded
 * here first, then edit permission is resolved for the current cid/sid. Mirrors
 * the backend gate (ROLE_CURRENT_COURSE_TEACHER / platform admin), so course
 * teachers and admins pass while everyone else is redirected away.
 *
 * @param {import('vue-router').RouteLocationNormalized} to
 * @returns {Promise<boolean|import('vue-router').RouteLocationRaw>}
 */
async function courseMaintenanceBeforeEnter(to) {
  const courseId = parseInt(to.query?.cid ?? 0)
  const sessionId = parseInt(to.query?.sid ?? 0)

  if (!courseId) {
    return { name: "Home", replace: true }
  }

  const cidReqStore = useCidReqStore()
  await cidReqStore.setCourseAndSessionById(courseId, sessionId)

  if (!cidReqStore.course) {
    return { name: "Home", replace: true }
  }

  const isAllowedToEdit = await checkIsAllowedToEdit()

  if (!isAllowedToEdit) {
    return { name: "CourseHome", params: { id: courseId }, query: sessionId ? { sid: sessionId } : {} }
  }

  return true
}

export default {
  path: "/resources/course_maintenance/:node(\\d+)",
  meta: { requiresAuth: true, showBreadcrumb: true, breadcrumb: "Course maintenance" },
  name: "course_maintenance",
  component: () => import("../components/coursemaintenance/CourseMaintenanceLayout.vue"),
  redirect: (to) => ({ name: "CMImportBackup", params: to.params, query: to.query }),
  children: [
    {
      name: "CMImportBackup",
      path: "import",
      beforeEnter: courseMaintenanceBeforeEnter,
      component: () => import("../views/coursemaintenance/ImportBackup.vue"),
      meta: { breadcrumb: "Import backup" },
    },
    {
      name: "CMCreateBackup",
      path: "create",
      beforeEnter: courseMaintenanceBeforeEnter,
      component: () => import("../views/coursemaintenance/CreateBackup.vue"),
      meta: { breadcrumb: "Create backup" },
    },
    {
      name: "CMCopyCourse",
      path: "copy",
      beforeEnter: courseMaintenanceBeforeEnter,
      component: () => import("../views/coursemaintenance/CopyCourse.vue"),
      meta: { breadcrumb: "Copy course" },
    },
    {
      name: "CMCc13",
      path: "cc13",
      beforeEnter: courseMaintenanceBeforeEnter,
      component: () => import("../views/coursemaintenance/Cc13.vue"),
      meta: { breadcrumb: "IMS CC 1.3" },
    },
    {
      name: "CMRecycle",
      path: "recycle",
      beforeEnter: courseMaintenanceBeforeEnter,
      component: () => import("../views/coursemaintenance/RecycleCourse.vue"),
      meta: { breadcrumb: "Recycle course" },
    },
    {
      name: "CMDelete",
      path: "delete",
      beforeEnter: courseMaintenanceBeforeEnter,
      component: () => import("../views/coursemaintenance/DeleteCourse.vue"),
      meta: { breadcrumb: "Delete course" },
    },
  ],
}
