export default {
  path: "/resources/wiki/:node/",
  meta: {
    requiresAuth: true,
    requiresCourseContext: true,
    showBreadcrumb: true,
    tool: "wiki",
    breadcrumb: "Wiki",
  },
  name: "wiki",
  component: () => import("../components/layout/SimpleRouterViewLayout.vue"),
  redirect: { name: "WikiPage" },
  children: [
    {
      name: "WikiPage",
      path: "",
      meta: { requiresAuth: true, breadcrumb: "Wiki" },
      component: () => import("../views/wiki/WikiPageView.vue"),
    },
  ],
}
