export default {
  path: "/resources/lp/:node(\\d+)",
  name: "lp",
  meta: {
    requiresAuth: true,
    showBreadcrumb: true,
    breadcrumb: "Learning paths",
  },
  component: () => import("../components/lp/LpLayout.vue"),
  redirect: { name: "LpList" },
  children: [
    {
      name: "LpList",
      path: "",
      component: () => import("../views/lp/LpList.vue"),
      meta: {
        breadcrumb: "",
      },
    },
    {
      name: "LpAdvancedAccess",
      path: "advanced-access",
      component: () => import("../views/lp/LpAdvancedAccess.vue"),
      meta: {
        breadcrumb: "Advanced access",
      },
    },

  ],
}
