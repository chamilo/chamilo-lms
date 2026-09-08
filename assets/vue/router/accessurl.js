// Same ancestors as the sibling assign-* pages declared in admin.js (not imported from there:
// this route intentionally stays outside the requiresGlobalAdmin-gated /admin/urls/* tree, since
// the backend leaves auth-sources/* actions at plain ROLE_ADMIN -- see AccessUrlController's
// class docblock).
const accessUrlAuthSourcesBreadcrumbParents = [
  { label: "Administration", route: { name: "AdminIndex" } },
  { label: "Multi URLs", route: { name: "AdminMultiUrlList" } },
  { label: "Multiple access URL / Branding", route: { name: "AccessUrlManage" } },
]

export default {
  meta: { requiresAdmin: true },
  path: "/access-url",
  component: () => import("../components/layout/SimpleRouterViewLayout.vue"),
  children: [
    {
      path: "auth-sources",
      name: "AccessUrlAuthSourcesAssign",
      meta: {
        requiresAuth: true,
        showBreadcrumb: true,
        breadcrumb: "Assign authentication sources",
        breadcrumbParents: accessUrlAuthSourcesBreadcrumbParents,
      },
      component: () => import("../views/accessurl/AccessUrlAuthSourcesAssign.vue"),
    },
  ],
}
