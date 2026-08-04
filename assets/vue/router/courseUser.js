export default {
  path: "/resources/course-users/:node/",
  meta: {
    requiresAuth: true,
    requiresCourseContext: true,
    showBreadcrumb: true,
    tool: "member",
    breadcrumb: "Users",
  },
  name: "CourseUser",
  component: () => import("../components/layout/SimpleRouterViewLayout.vue"),
  redirect: { name: "CourseUserList" },
  children: [
    {
      name: "CourseUserList",
      path: "",
      meta: { requiresAuth: true, breadcrumb: "Users" },
      component: () => import("../views/courseUser/CourseUserListView.vue"),
    },
    {
      name: "CourseUserSubscribe",
      path: "subscribe",
      meta: { requiresAuth: true, breadcrumb: "Add users" },
      component: () => import("../views/courseUser/CourseUserSubscribeView.vue"),
    },
    {
      name: "CourseUserImport",
      path: "import",
      meta: { requiresAuth: true, breadcrumb: "Import users list" },
      component: () => import("../views/courseUser/CourseUserImportView.vue"),
    },
    {
      name: "CourseUserClasses",
      path: "classes",
      meta: { requiresAuth: true, breadcrumb: "Classes" },
      component: () => import("../views/courseUser/CourseClassListView.vue"),
    },
  ],
}
