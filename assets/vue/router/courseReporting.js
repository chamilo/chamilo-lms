const sectionView = (section, title) => ({
  component: () => import("../views/courseReporting/CourseReportingSectionView.vue"),
  props: { section, title },
})

export default {
  path: "/resources/course-reporting/",
  meta: {
    requiresAuth: true,
    requiresCourseContext: true,
    showBreadcrumb: true,
    tool: "tracking",
    breadcrumb: "Reporting",
  },
  name: "courseReporting",
  component: () => import("../components/layout/SimpleRouterViewLayout.vue"),
  redirect: { name: "CourseReportingLearners" },
  children: [
    {
      name: "CourseReportingLearners",
      path: "",
      meta: { breadcrumb: "Report on learners" },
      component: () => import("../views/courseReporting/CourseReportingLearnersView.vue"),
    },
    {
      name: "CourseReportingLearnerDetail",
      path: "learners/:userId",
      meta: { breadcrumb: "Course tracking details" },
      component: () => import("../views/courseReporting/CourseReportingLearnerDetailView.vue"),
    },
    {
      name: "CourseReportingActivity",
      path: "activity",
      meta: { breadcrumb: "Course activity statistics" },
      ...sectionView("activity", "Course activity statistics"),
    },
    {
      name: "CourseReportingGroups",
      path: "groups",
      meta: { breadcrumb: "Group reporting" },
      ...sectionView("groups", "Group report"),
    },
    {
      name: "CourseReportingResources",
      path: "resources",
      meta: { breadcrumb: "Report on resources" },
      ...sectionView("resources", "Resource report"),
    },
    {
      name: "CourseReportingTools",
      path: "course",
      meta: { breadcrumb: "Course report" },
      ...sectionView("tools", "Course report"),
    },
    {
      name: "CourseReportingExams",
      path: "exams",
      meta: { breadcrumb: "Exam tracking" },
      ...sectionView("exams", "Test report"),
    },
    {
      name: "CourseReportingAudit",
      path: "audit",
      meta: { breadcrumb: "Audit report" },
      ...sectionView("audit", "Audit report"),
    },
    {
      name: "CourseReportingLearningPaths",
      path: "learning-paths",
      meta: { breadcrumb: "Learning paths generic stats" },
      ...sectionView("learning-paths", "Learning path report"),
    },
    {
      name: "CourseReportingTotalTime",
      path: "total-time",
      meta: { breadcrumb: "Total time" },
      ...sectionView("total-time", "Total time"),
    },
    {
      name: "CourseReportingSession",
      path: "session",
      meta: { breadcrumb: "Session report" },
      ...sectionView("session", "Session report"),
    },
    {
      name: "CourseReportingMessages",
      path: "messages",
      meta: { breadcrumb: "Message report" },
      ...sectionView("messages", "Message report"),
    },
  ],
}
