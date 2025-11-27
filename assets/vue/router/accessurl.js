export default {
  meta: { requiresAdmin: true },
  path: "/access-url",
  component: () => import("../components/layout/SimpleRouterViewLayout.vue"),
  children: [
    {
      path: "auth-sources",
      name: "AccessUrlAuthSourcesAssign",
      meta: { requiresAuth: true },
      component: () => import("../views/accessurl/AccessUrlAuthSourcesAssign.vue"),
    },
  ],
}
