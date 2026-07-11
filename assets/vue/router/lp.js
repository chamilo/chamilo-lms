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
      name: "LpAiGenerator",
      path: "ai-generator",
      component: () => import("../views/lp/LpAiGenerator.vue"),
      meta: { breadcrumb: "AI generator" },
    },
    {
      name: "LpScormImport",
      path: "import",
      component: () => import("../views/lp/LpScormImport.vue"),
      meta: { breadcrumb: "Import" },
    },
    {
      name: "LpRuntime",
      path: ":lpId(\\d+)/runtime",
      component: () => import("../views/lp/LpRuntime.vue"),
      meta: { breadcrumb: "", emptyLayout: true, hideGlobalUi: true, showBreadcrumb: false },
    },
    {
      name: "LpBuilder",
      path: ":lpId(\\d+)/builder",
      component: () => import("../views/lp/LpBuilder.vue"),
      meta: { breadcrumb: "Edit learnpath" },
    },
    {
      name: "LpReporting",
      path: ":lpId(\\d+)/reporting",
      component: () => import("../views/lp/LpReporting.vue"),
      meta: { breadcrumb: "Reporting" },
    },
    {
      name: "LpScormUpdate",
      path: ":lpId(\\d+)/update-scorm",
      component: () => import("../views/lp/LpScormUpdate.vue"),
      meta: { breadcrumb: "Update SCORM" },
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
      name: "LpCategorySubscriptions",
      path: "categories/:categoryId(\\d+)/subscriptions",
      component: () => import("../views/lp/LpCategorySubscriptions.vue"),
      meta: { breadcrumb: "Subscribe users to category" },
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
