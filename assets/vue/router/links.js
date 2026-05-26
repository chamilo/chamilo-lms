export default {
  path: "/resources/links/:node/",
  meta: {
    requiresAuth: true,
    showBreadcrumb: true,
    tool: "link",
  },
  name: "links",
  component: () => import("../components/layout/SimpleRouterViewLayout.vue"),
  redirect: { name: "LinksList" },
  children: [
    {
      name: "LinksList",
      path: "",
      meta: { breadcrumb: "" },
      component: () => import("../views/links/LinksList.vue"),
    },
    {
      name: "CreateLink",
      path: "create",
      meta: { breadcrumb: "Create link" },
      component: () => import("../views/links/LinksCreate.vue"),
    },
    {
      name: "UpdateLink",
      path: "edit/:id",
      meta: { breadcrumb: "Edit" },
      component: () => import("../views/links/LinksUpdate.vue"),
    },
    {
      name: "CreateLinkCategory",
      path: "create_link_category",
      meta: { breadcrumb: "Create category" },
      component: () => import("../views/links/LinksCategoryCreate.vue"),
    },
    {
      name: "UpdateLinkCategory",
      path: "update_link_category/:id",
      meta: { breadcrumb: "Edit category" },
      component: () => import("../views/links/LinksCategoryUpdate.vue"),
    },
  ],
}
