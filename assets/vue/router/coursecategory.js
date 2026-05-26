export default {
  path: "/resources/course_categories",
  meta: { requiresAuth: true },
  name: "course_categories",
  component: () => import("../components/coursecategory/Layout.vue"),
  redirect: { name: "CourseCategoryList" },
  children: [
    {
      name: "CourseCategoryList",
      path: "",
      meta: { breadcrumb: "Course categories" },
      component: () => import("../views/coursecategory/List.vue"),
    },
    {
      name: "CourseCategoryCreate",
      path: "new",
      meta: { breadcrumb: "Create" },
      component: () => import("../views/coursecategory/Create.vue"),
    },
    {
      name: "CourseCategoryUpdate",
      path: ":id/edit",
      meta: { breadcrumb: "Edit" },
      component: () => import("../views/coursecategory/Update.vue"),
    },
    {
      name: "CourseCategoryShow",
      path: ":id",
      meta: { breadcrumb: "Show" },
      component: () => import("../views/coursecategory/Show.vue"),
    },
  ],
}
