export default {
  path: "/resources/branches",
  meta: { requiresAuth: true, requiresAdmin: true },
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
      component: () => import("../views/branch/Create.vue"),
    },
    {
      name: "BranchUpdate",
      path: "edit",
      component: () => import("../views/branch/Update.vue"),
    },
  ],
}
