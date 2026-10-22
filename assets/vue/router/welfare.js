export default {
  path: "/welfare",
  name: "Welfare",
  component: () => import("../components/layout/SimpleRouterViewLayout.vue"),
  redirect: { name: "WelfareList" },
  meta: { requiresAuth: true, showBreadcrumb: true, breadcrumb: "Confidential reporting" },
  children: [
    {
      path: "",
      name: "WelfareList",
      meta: { requiresAuth: true, breadcrumb: "Confidential reporting" },
      component: () => import("../views/welfare/WelfareListView.vue"),
    },
    {
      path: "new",
      name: "WelfareCreate",
      meta: { requiresAuth: true, breadcrumb: "New confidential report" },
      component: () => import("../views/welfare/WelfareCreateView.vue"),
    },
    {
      path: ":reference([a-f0-9]{32})",
      name: "WelfareDetail",
      meta: { requiresAuth: true, breadcrumb: "Confidential report" },
      component: () => import("../views/welfare/WelfareDetailView.vue"),
    },
  ],
}
