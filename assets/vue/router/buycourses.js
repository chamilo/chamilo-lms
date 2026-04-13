export default [
  {
    path: "/my-services",
    name: "MyServices",
    component: () => import("../views/buycourses/MyServices.vue"),
    meta: {
      requiresAuth: true,
      breadcrumb: "My services",
    },
  },
]
