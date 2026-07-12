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
    {
      name: "WikiPageCreate",
      path: "new",
      meta: { requiresAuth: true, breadcrumb: "Add new page" },
      component: () => import("../views/wiki/WikiPageFormView.vue"),
    },
    {
      name: "WikiPageEdit",
      path: "edit/:pageId(\\d+)",
      meta: { requiresAuth: true, breadcrumb: "Edit page" },
      component: () => import("../views/wiki/WikiPageFormView.vue"),
    },
    {
      name: "WikiReports",
      path: "reports",
      meta: { requiresAuth: true, breadcrumb: "Wiki reports" },
      component: () => import("../views/wiki/WikiReportView.vue"),
    },
    {
      name: "WikiPageHistory",
      path: "history/:pageId(\\d+)",
      meta: { requiresAuth: true, breadcrumb: "History" },
      component: () => import("../views/wiki/WikiPageHistoryView.vue"),
    },
  ],
}
