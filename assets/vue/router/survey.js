export default {
  path: "/resources/survey/:node/",
  meta: {
    requiresAuth: true,
    showBreadcrumb: true,
    tool: "survey",
    breadcrumb: "Surveys",
  },
  name: "survey",
  component: () => import("../components/layout/SimpleRouterViewLayout.vue"),
  redirect: { name: "SurveyList" },
  children: [
    {
      name: "SurveyList",
      path: "",
      meta: { breadcrumb: "Surveys" },
      component: () => import("../views/survey/SurveyListView.vue"),
    },
    {
      name: "SurveyCreate",
      path: "create",
      meta: { breadcrumb: "Create survey" },
      component: () => import("../views/survey/SurveyConfigurationView.vue"),
    },
    {
      name: "SurveyEdit",
      path: ":surveyId/edit",
      meta: { breadcrumb: "Edit survey" },
      component: () => import("../views/survey/SurveyConfigurationView.vue"),
    },
    {
      name: "SurveyMeetingCreate",
      path: "meeting/create",
      meta: { breadcrumb: "Create meeting poll" },
      component: () => import("../views/survey/SurveyMeetingView.vue"),
    },
    {
      name: "SurveyMeetingEdit",
      path: ":surveyId/meeting/edit",
      meta: { breadcrumb: "Edit meeting poll" },
      component: () => import("../views/survey/SurveyMeetingView.vue"),
    },
    {
      name: "SurveyMeeting",
      path: ":surveyId/meeting",
      meta: { breadcrumb: "Meeting poll" },
      component: () => import("../views/survey/SurveyMeetingView.vue"),
    },
    {
      name: "SurveyQuestions",
      path: ":surveyId/questions",
      meta: { breadcrumb: "Survey questions" },
      component: () => import("../views/survey/SurveyQuestionsView.vue"),
    },

    {
      name: "SurveyCopy",
      path: ":surveyId/copy",
      meta: { breadcrumb: "Copy survey" },
      component: () => import("../views/survey/SurveyCopyView.vue"),
    },
    {
      name: "SurveyInvitations",
      path: ":surveyId/invitations",
      meta: { breadcrumb: "Survey invitations" },
      component: () => import("../views/survey/SurveyInvitationsView.vue"),
    },
    {
      name: "SurveyReporting",
      path: ":surveyId/reporting",
      meta: { breadcrumb: "Survey reporting" },
      component: () => import("../views/survey/SurveyReportingView.vue"),
    },
    {
      name: "SurveyPreview",
      path: ":surveyId/preview",
      meta: { breadcrumb: "Survey preview" },
      component: () => import("../views/survey/SurveyAnswerView.vue"),
    },
    {
      name: "SurveyAnswer",
      path: ":surveyId/answer",
      meta: { breadcrumb: "Answer survey", allowAnonymousAccess: true },
      component: () => import("../views/survey/SurveyAnswerView.vue"),
    },
  ],
}
