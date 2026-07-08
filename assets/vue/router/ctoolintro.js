export default {
  path: "/resources/ctoolintro/",
  meta: { requiresAuth: true, showBreadcrumb: true },
  name: "ctoolintro",
  component: () => import("../components/ctoolintro/Layout.vue"),
  children: [
    {
      name: "ToolIntroUpdate",
      path: "edit",
      meta: { breadcrumb: "Edit" },
      component: () => import("../views/ctoolintro/Update.vue"),
    },
  ],
}
