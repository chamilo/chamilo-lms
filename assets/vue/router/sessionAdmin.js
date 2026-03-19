export default {
  path: "/admin-dashboard",
  meta: { requiresAdmin: true, requiresSessionAdmin: true },
  component: () => import("../components/layout/SimpleRouterViewLayout.vue"),
  children: [
    {
      path: "",
      name: "AdminDashboard",
      meta: { breadcrumb: "Dashboard" },
      component: () => import("../views/sessionadmin/AdminDashboard.vue"),
    },
    {
      path: "register/:courseId",
      name: "RegisterStudent",
      meta: { breadcrumb: "Register student" },
      component: () => import("../views/sessionadmin/RegisterStudent.vue"),
      props: true,
    },
    {
      path: "favorites",
      name: "AdminFavoritesCourses",
      meta: { breadcrumb: "Favourite courses" },
      component: () => import("../views/sessionadmin/FavoritesCourses.vue"),
    },
    {
      path: "completed",
      name: "AdminCompletedCourses",
      meta: { breadcrumb: "Completed courses" },
      component: () => import("../views/sessionadmin/CompletedCourses.vue"),
    },
    {
      path: "incomplete",
      name: "AdminIncompleteCourses",
      meta: { breadcrumb: "Incomplete courses" },
      component: () => import("../views/sessionadmin/IncompleteCourses.vue"),
    },
    {
      path: "restart",
      name: "AdminRestartCourses",
      meta: { breadcrumb: "Restart courses" },
      component: () => import("../views/sessionadmin/RestartCourses.vue"),
    },
  ],
}
