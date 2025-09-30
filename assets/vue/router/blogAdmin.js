export default {
  path: "/resources/blogs/:node/",
  meta: {
    requiresAuth: true,
    showBreadcrumb: true,
    breadcrumb: "Projects",
  },
  name: "BlogsAdmin",
  component: () => import("../views/blog/BlogAdmin.vue"),
}
