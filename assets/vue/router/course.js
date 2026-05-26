export default {
  path: "/resources/courses",
  meta: { requiresAuth: true },
  name: "courses",
  component: () => import("../components/course/Layout.vue"),
  redirect: { name: "CourseList" },
  children: [
    {
      name: "CourseList",
      path: "",
      meta: { breadcrumb: "Courses" },
      component: () => import("../views/course/List.vue"),
    },
    {
      name: "CourseCreate",
      path: "new",
      meta: { breadcrumb: "Create" },
      component: () => import("../views/course/Create.vue"),
    },
    {
      name: "CourseUpdate",
      path: ":id/edit",
      meta: { breadcrumb: "Edit" },
      component: () => import("../views/course/Update.vue"),
    },
    {
      name: "CourseShow",
      path: ":id",
      meta: { breadcrumb: "Show" },
      component: () => import("../views/course/Show.vue"),
    },
  ],
}
