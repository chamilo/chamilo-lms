export default {
  path: "/catalogue",
  component: () => import("../components/layout/SimpleRouterViewLayout.vue"),
  children: [
    {
      path: "courses",
      name: "CatalogueCourses",
      component: () => import("../views/course/CatalogueCourses.vue"),
    },
    {
      path: "sessions",
      name: "CatalogueSessions",
      component: () => import("../views/course/CatalogueSessions.vue"),
    },
  ],
}
