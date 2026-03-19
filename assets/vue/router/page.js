export default {
  path: "/resources/pages",
  meta: { requiresAuth: true },
  name: "pages",
  component: () => import("../components/page/Layout.vue"),
  redirect: { name: "PageList" },
  children: [
    {
      name: "PageList",
      path: "",
      meta: { breadcrumb: "Pages" },
      component: () => import("../views/page/List.vue"),
    },
    {
      name: "PageCreate",
      path: "new",
      meta: { breadcrumb: "Create" },
      component: () => import("../views/page/Create.vue"),
    },
    {
      name: "PageUpdate",
      //path: ':id/edit',
      path: "edit",
      meta: { breadcrumb: "Edit" },
      component: () => import("../views/page/Update.vue"),
    },
    {
      name: "PageShow",
      //path: ':id',
      path: "show",
      meta: { breadcrumb: "Show" },
      component: () => import("../views/page/Show.vue"),
    },
    {
      name: "PageEditorDemo",
      path: "editor-demo",
      meta: { breadcrumb: "Editor demo" },
      component: () => import("../views/page/EditorDemo.vue"),
    },
  ],
}
