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
  ],
}
