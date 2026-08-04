export default {
  path: "/my-classes",
  name: "MyClassList",
  component: () => import("../views/courseUser/MyClassListView.vue"),
  meta: {
    requiresAuth: true,
    showBreadcrumb: true,
    breadcrumb: "My classes",
  },
}
