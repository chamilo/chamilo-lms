export default {
  path: "/resources/course-invitation/",
  meta: {
    requiresCourseContext: true,
    requiresAuth: true,
    showBreadcrumb: true,
    tool: "course_invitation",
    breadcrumb: "Course invitations",
  },
  name: "CourseInvitationList",
  component: () => import("../views/user/CourseInvitationListView.vue"),
}
