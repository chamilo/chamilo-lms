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
      component: () => import("../views/pageLayout/List.vue"),
    },
    {
      name: "PageLayoutCreate",
      path: "new",
      component: () => import("../views/pageLayout/Create.vue"),
    },
    {
      name: "PageLayoutEdit",
      path: ":id/edit",
      component: () => import("../views/pageLayout/Edit.vue"),
    },
    {
      name: "PageLayoutShow",
      path: ":id",
      component: () => import("../views/pageLayout/Show.vue"),
    },

    // Page Layout Template CRUD
    {
      name: "PageLayoutTemplateList",
      path: "templates",
      component: () => import("../views/pageLayout/ListTemplates.vue"),
    },
    {
      name: "PageLayoutTemplateCreate",
      path: "templates/new",
      component: () => import("../views/pageLayout/CreateTemplate.vue"),
    },
    {
      name: "PageLayoutTemplateEdit",
      path: "templates/:id/edit",
      component: () => import("../views/pageLayout/EditTemplate.vue"),
    },
  ],
}
