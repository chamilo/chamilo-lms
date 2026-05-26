export default {
  path: "/resources/pages/layouts",
  meta: { requiresAuth: true },
  name: "page-layouts",
  component: () => import("../components/page/Layout.vue"),
  redirect: { name: "PageLayoutList" },
  children: [
    // Page Layout CRUD
    {
      name: "PageLayoutList",
      path: "",
      meta: { breadcrumb: "Layouts" },
      component: () => import("../views/pageLayout/List.vue"),
    },
    {
      name: "PageLayoutCreate",
      path: "new",
      meta: { breadcrumb: "Create" },
      component: () => import("../views/pageLayout/Create.vue"),
    },
    {
      name: "PageLayoutEdit",
      path: ":id/edit",
      meta: { breadcrumb: "Edit" },
      component: () => import("../views/pageLayout/Edit.vue"),
    },
    {
      name: "PageLayoutShow",
      path: ":id",
      meta: { breadcrumb: "Show" },
      component: () => import("../views/pageLayout/Show.vue"),
    },

    // Page Layout Template CRUD
    {
      name: "PageLayoutTemplateList",
      path: "templates",
      meta: { breadcrumb: "Templates" },
      component: () => import("../views/pageLayout/ListTemplates.vue"),
    },
    {
      name: "PageLayoutTemplateCreate",
      path: "templates/new",
      meta: { breadcrumb: "Create template" },
      component: () => import("../views/pageLayout/CreateTemplate.vue"),
    },
    {
      name: "PageLayoutTemplateEdit",
      path: "templates/:id/edit",
      meta: { breadcrumb: "Edit template" },
      component: () => import("../views/pageLayout/EditTemplate.vue"),
    },
  ],
}
