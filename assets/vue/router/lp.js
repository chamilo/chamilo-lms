export default {
  path: "/resources/lp/:node(\\d+)",
  meta: { requiresAuth: true, showBreadcrumb: true },
  name: "lp",
  component: () => import("../components/lp/LpLayout.vue"),
  redirect: { name: "LpList" },
  children: [
    { name: "LpList", path: "", component: () => import("../views/lp/LpList.vue") },
  ],
}
