export default {
  path: "/resources/course-description/:node/",
  meta: {
    requiresCourseContext: true,
    showBreadcrumb: true,
    tool: "course_description",
    breadcrumb: "Course description",
  },
  name: "courseDescription",
  component: () => import("../components/layout/SimpleRouterViewLayout.vue"),
  redirect: { name: "CourseDescriptionList" },
  children: [
    {
      name: "CourseDescriptionList",
      path: "",
      meta: { requiresAuth: false, breadcrumb: "Course description" },
      component: () => import("../views/courseDescription/CourseDescriptionListView.vue"),
    },
    {
      name: "CourseDescriptionEdit",
      path: "edit/:id?",
      meta: { requiresAuth: true, breadcrumb: "Edit" },
      component: () => import("../views/courseDescription/CourseDescriptionFormView.vue"),
    },
    {
      name: "CourseDescriptionAdd",
      path: "add",
      meta: { requiresAuth: true, breadcrumb: "Add" },
      component: () => import("../views/courseDescription/CourseDescriptionFormView.vue"),
    },
  ],
}
