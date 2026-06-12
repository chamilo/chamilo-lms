export default {
  path: "/resources/exercise/:node/",
  meta: {
    requiresAuth: true,
    showBreadcrumb: true,
    tool: "exercise",
    breadcrumb: "Exercises",
  },
  name: "exercise",
  component: () => import("../components/layout/SimpleRouterViewLayout.vue"),
  redirect: { name: "ExerciseList" },
  children: [
    {
      name: "ExerciseList",
      path: "",
      meta: { breadcrumb: "Exercises" },
      component: () => import("../views/exercise/ExerciseListView.vue"),
    },
    {
      name: "ExerciseCreate",
      path: "create",
      meta: { breadcrumb: "Create exercise" },
      component: () => import("../views/exercise/ExerciseConfigurationView.vue"),
    },
    {
      name: "ExerciseEdit",
      path: ":exerciseId/edit",
      meta: { breadcrumb: "Edit exercise" },
      component: () => import("../views/exercise/ExerciseConfigurationView.vue"),
    },
    {
      name: "ExercisePlayer",
      path: ":exerciseId/player",
      meta: { breadcrumb: "Exercise player" },
      component: () => import("../views/exercise/ExercisePlayerView.vue"),
    },
    {
      name: "ExerciseResult",
      path: ":exerciseId/result/:attemptId",
      meta: { breadcrumb: "Exercise result" },
      component: () => import("../views/exercise/ExerciseResultView.vue"),
    },
    {
      name: "ExerciseReport",
      path: ":exerciseId/report",
      meta: { breadcrumb: "Learner score" },
      component: () => import("../views/exercise/ExerciseReportView.vue"),
    },
    {
      name: "ExerciseQuestions",
      path: ":exerciseId/questions",
      meta: { breadcrumb: "Questions" },
      component: () => import("../views/exercise/ExerciseQuestionSelectorView.vue"),
    },
    {
      name: "ExerciseQuestionCreate",
      path: ":exerciseId/questions/create/:questionType",
      meta: { breadcrumb: "Create question" },
      component: () => import("../views/exercise/ExerciseQuestionEditorView.vue"),
    },
    {
      name: "ExerciseQuestionEdit",
      path: ":exerciseId/questions/:questionId/edit",
      meta: { breadcrumb: "Edit question" },
      component: () => import("../views/exercise/ExerciseQuestionEditorView.vue"),
    },
  ],
}
