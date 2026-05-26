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
      component: () => import("../views/coursemaintenance/ImportBackup.vue"),
      meta: { breadcrumb: "Import backup" },
    },
    {
      name: "CMCreateBackup",
      path: "create",
      component: () => import("../views/coursemaintenance/CreateBackup.vue"),
      meta: { breadcrumb: "Create backup" },
    },
    {
      name: "CMCopyCourse",
      path: "copy",
      component: () => import("../views/coursemaintenance/CopyCourse.vue"),
      meta: { breadcrumb: "Copy course" },
    },
    {
      name: "CMCc13",
      path: "cc13",
      component: () => import("../views/coursemaintenance/Cc13.vue"),
      meta: { breadcrumb: "IMS CC 1.3" },
    },
    {
      name: "CMRecycle",
      path: "recycle",
      component: () => import("../views/coursemaintenance/RecycleCourse.vue"),
      meta: { breadcrumb: "Recycle course" },
    },
    {
      name: "CMDelete",
      path: "delete",
      component: () => import("../views/coursemaintenance/DeleteCourse.vue"),
      meta: { breadcrumb: "Delete course" },
    },
  ],
}
