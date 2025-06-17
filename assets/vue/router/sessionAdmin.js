export default {
  path: "/admin-dashboard",
  meta: { requiresAdmin: true, requiresSessionAdmin: true },
  component: () => import("../components/layout/SimpleRouterViewLayout.vue"),
  children: [
    {
      path: "",
      name: "AdminDashboard",
      component: () => import("../views/sessionadmin/AdminDashboard.vue"),
    },
    {
      path: "register/:courseId",
      name: "RegisterStudent",
      component: () => import("../views/sessionadmin/RegisterStudent.vue"),
      props: true,
    },
    {
      path: "favorites",
      name: "AdminFavoritesCourses",
      component: () => import("../views/sessionadmin/FavoritesCourses.vue"),
    },
    {
      path: "completed",
      name: "AdminCompletedCourses",
      component: () => import("../views/sessionadmin/CompletedCourses.vue"),
    },
    {
      path: "incomplete",
      name: "AdminIncompleteCourses",
      component: () => import("../views/sessionadmin/IncompleteCourses.vue"),
    },
    {
      path: "restart",
      name: "AdminRestartCourses",
      component: () => import("../views/sessionadmin/RestartCourses.vue"),
    },
  ],
}
