export default {
  path: "/resources/course-settings/:node/",
  meta: {
    requiresAuth: true,
    requiresCourseContext: true,
    showBreadcrumb: true,
    tool: "course_setting",
    breadcrumb: "Course settings",
  },
  name: "CourseSettings",
  component: () => import("../components/layout/SimpleRouterViewLayout.vue"),
  redirect: { name: "CourseSettingsForm" },
  children: [
    {
      name: "CourseSettingsForm",
      path: "",
      meta: { requiresAuth: true, breadcrumb: "Course settings" },
      component: () => import("../views/courseSettings/CourseSettingsView.vue"),
    },
  ],
}
