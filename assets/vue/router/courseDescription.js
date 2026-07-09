export default {
  path: "/resources/course-description/:node/",
  meta: {
    requiresAuth: true,
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
      meta: { breadcrumb: "Course description" },
      component: () => import("../views/courseDescription/CourseDescriptionListView.vue"),
    },
    {
      name: "CourseDescriptionEdit",
      path: "edit/:id?",
      meta: { breadcrumb: "Edit" },
      component: () => import("../views/courseDescription/CourseDescriptionFormView.vue"),
    },
    {
      name: "CourseDescriptionAdd",
      path: "add",
      meta: { breadcrumb: "Add" },
      component: () => import("../views/courseDescription/CourseDescriptionFormView.vue"),
    },
  ],
}
