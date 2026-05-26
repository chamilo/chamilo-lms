export default {
  path: "/catalogue",
  component: () => import("../components/layout/SimpleRouterViewLayout.vue"),
  children: [
    {
      path: "courses",
      name: "CatalogueCourses",
      meta: { breadcrumb: "Courses" },
      component: () => import("../views/course/CatalogueCourses.vue"),
    },
    {
      path: "sessions",
      name: "CatalogueSessions",
      meta: { breadcrumb: "Sessions" },
      component: () => import("../views/course/CatalogueSessions.vue"),
    },
  ],
}
