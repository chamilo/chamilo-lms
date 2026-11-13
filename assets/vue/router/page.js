// Admin-only entry points opt into a navigation ancestor using an explicit query
// marker. Ordinary Pages routes keep their existing breadcrumb visibility.
const isPlatformEntry = (route) => route.query.admin_origin === "platform"
const platformParentCrumbs = (route) =>
  isPlatformEntry(route)
    ? [
        { label: "Administration", route: { name: "AdminIndex" } },
        { label: "Platform management", route: { name: "AdminPlatformSection" } },
      ]
    : []
const platformPageCrumbs = (route) =>
  isPlatformEntry(route)
    ? [...platformParentCrumbs(route), { label: "Pages", route: { name: "PageList", query: route.query } }]
    : []

export default {
  path: "/resources/pages",
  meta: { requiresAuth: true, showBreadcrumb: isPlatformEntry, breadcrumb: "Pages" },
  name: "pages",
  component: () => import("../components/page/Layout.vue"),
  redirect: { name: "PageList" },
  children: [
    {
      name: "PageList",
      path: "",
      meta: { breadcrumb: "Pages", breadcrumbParents: platformParentCrumbs },
      component: () => import("../views/page/List.vue"),
    },
    {
      name: "PageCreate",
      path: "new",
      meta: { breadcrumb: "Create", breadcrumbParents: platformPageCrumbs },
      component: () => import("../views/page/Create.vue"),
    },
    {
      name: "PageUpdate",
      //path: ':id/edit',
      path: "edit",
      meta: { breadcrumb: "Edit", breadcrumbParents: platformPageCrumbs },
      component: () => import("../views/page/Update.vue"),
    },
    {
      name: "PageShow",
      //path: ':id',
      path: "show",
      meta: { breadcrumb: "Show", breadcrumbParents: platformPageCrumbs },
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
