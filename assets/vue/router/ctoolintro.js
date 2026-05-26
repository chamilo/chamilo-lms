export default {
  path: "/resources/ctoolintro/",
  meta: { requiresAuth: true, showBreadcrumb: true },
  name: "ctoolintro",
  component: () => import("../components/ctoolintro/Layout.vue"),
  redirect: { name: "ToolIntroList" },
  children: [
    {
      name: "ToolIntroCreate",
      path: "new/:courseTool",
      meta: { breadcrumb: "Create" },
      component: () => import("../views/ctoolintro/Create.vue"),
    },
    {
      name: "ToolIntroUpdate",
      path: "edit",
      meta: { breadcrumb: "Edit" },
      component: () => import("../views/ctoolintro/Update.vue"),
    },
    {
      name: "ToolIntroShow",
      path: "",
      meta: { breadcrumb: "Tool introduction" },
      component: () => import("../views/ctoolintro/Show.vue"),
    },
  ],
}
