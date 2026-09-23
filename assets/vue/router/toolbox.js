export default {
  path: "/resources/toolbox/:node(\\d+)",
  name: "Toolbox",
  meta: {
    requiresAuth: true,
    requiresCourseContext: true,
    showBreadcrumb: true,
    tool: "toolbox",
    breadcrumb: "Toolbox",
  },
  component: () => import("../components/layout/SimpleRouterViewLayout.vue"),
  redirect: { name: "ToolboxList" },
  children: [
    {
      name: "ToolboxList",
      path: "",
      component: () => import("../views/toolbox/ToolboxList.vue"),
      meta: { breadcrumb: "" },
    },
    {
      name: "ToolboxCreate",
      path: "create",
      component: () => import("../views/toolbox/ToolboxForm.vue"),
      meta: { breadcrumb: "Create" },
    },
    {
      name: "ToolboxDetail",
      path: ":itemId(\\d+)",
      component: () => import("../views/toolbox/ToolboxDetail.vue"),
      meta: { breadcrumb: "Details" },
    },
  ],
}
