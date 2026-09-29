// This tool lives under Administration. Labels are translation keys.
const adminCrumb = { label: "Administration", route: { name: "AdminIndex" } }
const branchCrumbs = [adminCrumb, { label: "Branches", route: { name: "BranchList" } }]

export default {
  path: "/resources/branches",
  meta: { requiresAuth: true, requiresAdmin: true, showBreadcrumb: true, breadcrumb: "Branches" },
  name: "branches",
  component: () => import("../components/branch/Layout.vue"),
  redirect: { name: "BranchList" },
  children: [
    {
      name: "BranchList",
      path: "",
      meta: { breadcrumb: "Branches", breadcrumbParents: [adminCrumb] },
      component: () => import("../views/branch/List.vue"),
    },
    {
      name: "BranchCreate",
      path: "new",
      meta: { breadcrumb: "Create", breadcrumbParents: branchCrumbs },
      component: () => import("../views/branch/Create.vue"),
    },
    {
      name: "BranchUpdate",
      path: "edit",
      meta: { breadcrumb: "Edit", breadcrumbParents: branchCrumbs },
      component: () => import("../views/branch/Update.vue"),
    },
  ],
}
