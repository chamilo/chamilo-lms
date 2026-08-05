export default {
  path: "/resources/course-progress/:node/",
  meta: {
    requiresCourseContext: true,
    showBreadcrumb: true,
    tool: "course_progress",
    breadcrumb: "Course progress",
  },
  name: "courseProgress",
  component: () => import("../components/layout/SimpleRouterViewLayout.vue"),
  redirect: { name: "CourseProgressList" },
  children: [
    {
      name: "CourseProgressList",
      path: "",
      meta: { requiresAuth: false, breadcrumb: "Course progress" },
      component: () => import("../views/courseProgress/CourseProgressListView.vue"),
    },
    {
      name: "CourseProgressImport",
      path: "import",
      meta: { requiresAuth: true, breadcrumb: "Import course progress" },
      component: () => import("../views/courseProgress/CourseProgressImportView.vue"),
    },
    {
      name: "CourseProgressThematicAdd",
      path: "add",
      meta: { requiresAuth: true, breadcrumb: "New thematic section" },
      component: () => import("../views/courseProgress/CourseProgressThematicFormView.vue"),
    },
    {
      name: "CourseProgressThematicEdit",
      path: "edit/:id",
      meta: { requiresAuth: true, breadcrumb: "Edit thematic section" },
      component: () => import("../views/courseProgress/CourseProgressThematicFormView.vue"),
    },
    {
      name: "CourseProgressThematicPlan",
      path: "plan/:thematicId",
      meta: { requiresAuth: true, breadcrumb: "Thematic plan" },
      component: () => import("../views/courseProgress/CourseProgressThematicPlanView.vue"),
    },
    {
      name: "CourseProgressThematicAdvanceList",
      path: "advance/:thematicId",
      meta: { requiresAuth: true, breadcrumb: "Thematic advance" },
      component: () => import("../views/courseProgress/CourseProgressThematicAdvanceListView.vue"),
    },
    {
      name: "CourseProgressThematicAdvanceAdd",
      path: "advance/:thematicId/add",
      meta: { requiresAuth: true, breadcrumb: "New thematic advance" },
      component: () => import("../views/courseProgress/CourseProgressThematicAdvanceFormView.vue"),
    },
    {
      name: "CourseProgressThematicAdvanceEdit",
      path: "advance/:thematicId/edit/:advanceId",
      meta: { requiresAuth: true, breadcrumb: "Edit thematic advance" },
      component: () => import("../views/courseProgress/CourseProgressThematicAdvanceFormView.vue"),
    },
  ],
}
