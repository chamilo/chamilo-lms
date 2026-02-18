export default {
  path: "/resources/branches",
  meta: { requiresAuth: true, requiresAdmin: true, showBreadcrumb: true },
  name: "branches",
  component: () => import("../components/branch/Layout.vue"),
  redirect: { name: "BranchList" },
  children: [
    {
      name: "BranchList",
      path: "",
      component: () => import("../views/branch/List.vue"),
    },
    {
      name: "BranchCreate",
      path: "new",
      meta: { breadcrumb: "Create" },
      component: () => import("../views/branch/Create.vue"),
    },
    {
      name: "BranchUpdate",
      path: "edit",
      meta: { breadcrumb: "Edit" },
      component: () => import("../views/branch/Update.vue"),
    },
  ],
}
