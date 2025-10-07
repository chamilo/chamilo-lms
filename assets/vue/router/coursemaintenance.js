export default {
  path: "/resources/course_maintenance/:node(\\d+)",
  meta: { requiresAuth: true, showBreadcrumb: true },
  name: "course_maintenance",
  component: () => import("../components/coursemaintenance/CourseMaintenanceLayout.vue"),
  redirect: (to) => ({ name: "CMImportBackup", params: to.params, query: to.query }),
  children: [
    {
      name: "CMImportBackup",
      path: "import",
      component: () => import("../views/coursemaintenance/ImportBackup.vue"),
      meta: { breadcrumb: "Importar backup" },
    },
    {
      name: "CMCreateBackup",
      path: "create",
      component: () => import("../views/coursemaintenance/CreateBackup.vue"),
      meta: { breadcrumb: "Crear backup" },
    },
    {
      name: "CMCopyCourse",
      path: "copy",
      component: () => import("../views/coursemaintenance/CopyCourse.vue"),
      meta: { breadcrumb: "Copiar curso" },
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
      meta: { breadcrumb: "Reciclar curso" },
    },
    {
      name: "CMDelete",
      path: "delete",
      component: () => import("../views/coursemaintenance/DeleteCourse.vue"),
      meta: { breadcrumb: "Eliminar curso" },
    },
  ],
}
