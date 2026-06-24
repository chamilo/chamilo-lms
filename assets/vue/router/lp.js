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
      name: "LpCreate",
      path: "create",
      component: () => import("../views/lp/LpForm.vue"),
      meta: { breadcrumb: "Create new learning path" },
    },
    {
      name: "LpSettings",
      path: ":lpId(\\d+)/settings",
      alias: ":lpId(\\d+)/edit",
      component: () => import("../views/lp/LpForm.vue"),
      meta: { breadcrumb: "Settings" },
    },
    {
      name: "LpCategoryCreate",
      path: "categories/create",
      component: () => import("../views/lp/LpCategoryForm.vue"),
      meta: { breadcrumb: "Add category" },
    },
    {
      name: "LpCategoryEdit",
      path: "categories/:categoryId(\\d+)/edit",
      component: () => import("../views/lp/LpCategoryForm.vue"),
      meta: { breadcrumb: "Edit category" },
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
