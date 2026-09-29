export default {
  path: "/course-sessions",
  name: "CourseSession",
  component: () => import("../components/layout/SimpleRouterViewLayout.vue"),
  redirect: { name: "CourseSessionList" },
  meta: {
    requiresAuth: true,
    showBreadcrumb: true,
    breadcrumb: "Course sessions",
  },
  children: [
    {
      name: "CourseSessionList",
      path: "",
      meta: { requiresAuth: true, showBreadcrumb: true, breadcrumb: "Course sessions" },
      component: () => import("../views/courseSession/CourseSessionListView.vue"),
    },
    {
      name: "CourseSessionOverview",
      path: ":sessionId",
      meta: { requiresAuth: true, showBreadcrumb: true, breadcrumb: "Session overview" },
      component: () => import("../views/courseSession/CourseSessionOverviewView.vue"),
    },
    {
      name: "CourseSessionUsers",
      path: ":sessionId/users",
      meta: { requiresAuth: true, showBreadcrumb: true, breadcrumb: "Subscribe users to this session" },
      component: () => import("../views/courseSession/CourseSessionUsersView.vue"),
    },
    {
      name: "CourseSessionUserCourses",
      path: ":sessionId/users/:userId/courses",
      meta: { requiresAuth: true, showBreadcrumb: true, breadcrumb: "Courses" },
      component: () => import("../views/courseSession/CourseSessionUserCoursesView.vue"),
    },
  ],
}
