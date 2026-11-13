import { activeReportLabelKey } from "../utils/statisticsReports"
import { useSecurityStore } from "../store/securityStore"

// Fixed breadcrumb ancestors, shared by the pages that hang from a list page.
// Labels are translation keys; the breadcrumb component translates them.
const adminCrumb = { label: "Administration", route: { name: "AdminIndex" } }
// Keep these route declarations literal so the breadcrumb-route checker can evaluate them.
// Section page content and icons are configured separately in utils/adminSections.js.
const sectionDefinitions = {
  users: { name: "AdminUsersSection", path: "users", title: "User management", allowSessionAdmin: true },
  courses: { name: "AdminCoursesSection", path: "courses", title: "Course management" },
  sessions: { name: "AdminSessionsSection", path: "sessions", title: "Sessions management", allowSessionAdmin: true },
  platform: { name: "AdminPlatformSection", path: "platform", title: "Platform management" },
  tracking: { name: "AdminAnalyticsSection", path: "analytics", title: "Analytics" },
  gradebook: { name: "AdminAssessmentsSection", path: "assessments", title: "Assessments" },
  skills: { name: "AdminSkillsSection", path: "skills", title: "Skills" },
  settings: { name: "AdminSystemSection", path: "system", title: "System" },
  rooms: { name: "AdminRoomsSection", path: "rooms", title: "Rooms" },
  security: { name: "AdminSecuritySection", path: "security", title: "Security" },
  data_privacy: { name: "AdminPrivacySection", path: "privacy", title: "Personal data protection" },
  plugins: { name: "AdminPluginsSection", path: "plugins", title: "Plugins" },
  health_check: { name: "AdminHealthCheckSection", path: "health-check", title: "Health check" },
  chamilo: { name: "AdminChamiloSection", path: "chamilo", title: "Chamilo.org" },
}

const sectionCrumbs = (section) => [
  adminCrumb,
  { label: sectionDefinitions[section].title, route: { name: sectionDefinitions[section].name } },
]

// Question managers and session admins can access a few tools but not their admin-only blocks.
const adminOnlyCrumbs = (section) => () =>
  useSecurityStore().isAdmin ? sectionCrumbs(section) : [adminCrumb]

const sectionRoutes = Object.entries(sectionDefinitions).map(([section, config]) => ({
  name: config.name,
  path: config.path,
  meta: {
    requiresAdmin: true,
    ...(config.allowSessionAdmin ? { requiresSessionAdmin: true } : {}),
    showBreadcrumb: true,
    breadcrumb: config.title,
    breadcrumbParents: [adminCrumb],
  },
  component: () => import("../views/admin/AdminSection.vue"),
  props: { section },
}))
const classesCrumbs = [...sectionCrumbs("users"), { label: "Classes", route: { name: "AdminUsergroupList" } }]
const surveyTemplateCrumbs = [...sectionCrumbs("platform"), { label: "Survey templates", route: { name: "SurveyTemplateList" } }]
const supportBotCrumbs = [...sectionCrumbs("tracking"), { label: "Support bot", route: { name: "SupportBotTreeList" } }]
const termsCrumbs = [...sectionCrumbs("platform"), { label: "Terms and conditions", route: { name: "TermsConditionsList" } }]
const multiUrlCrumbs = [...sectionCrumbs("platform"), { label: "Multi URLs", route: { name: "AdminMultiUrlList" } }]
const accessUrlCrumbs = [
  ...multiUrlCrumbs,
  { label: "Multiple access URL / Branding", route: { name: "AccessUrlManage" } },
]

export default {
  path: "/admin",
  name: "admin",
  meta: { requiresAuth: true, showBreadcrumb: false, breadcrumb: "Administration" },
  component: () => import("../components/admin/AdminLayout.vue"),
  children: [
    {
      path: "",
      name: "AdminIndex",
      meta: { requiresAdmin: true, requiresSessionAdmin: true, showBreadcrumb: false },
      component: () => import("../views/admin/AdminIndex.vue"),
    },
    ...sectionRoutes,
    {
      name: "SurveyTemplateList",
      path: "survey-templates",
      meta: {
        requiresAdmin: true,
        showBreadcrumb: true,
        breadcrumb: "Survey templates",
        breadcrumbParents: sectionCrumbs("platform"),
      },
      component: () => import("../views/surveytemplate/SurveyTemplateList.vue"),
    },
    {
      name: "SurveyTemplateCreate",
      path: "survey-templates/create",
      meta: {
        requiresAdmin: true,
        showBreadcrumb: true,
        breadcrumb: "Create survey template",
        breadcrumbParents: surveyTemplateCrumbs,
      },
      component: () => import("../views/surveytemplate/SurveyTemplateEdit.vue"),
    },
    {
      name: "SurveyTemplateEdit",
      path: "survey-templates/:id(\\d+)/edit",
      meta: {
        requiresAdmin: true,
        showBreadcrumb: true,
        breadcrumb: "Edit survey template",
        breadcrumbParents: surveyTemplateCrumbs,
      },
      component: () => import("../views/surveytemplate/SurveyTemplateEdit.vue"),
    },
    {
      name: "SurveyTemplateQuestions",
      path: "survey-templates/:id(\\d+)/questions",
      meta: {
        requiresAdmin: true,
        showBreadcrumb: true,
        breadcrumb: "Survey template questions",
        breadcrumbParents: surveyTemplateCrumbs,
      },
      component: () => import("../views/surveytemplate/SurveyTemplateQuestions.vue"),
    },
    {
      name: "SupportBotTreeList",
      path: "support-bot",
      meta: {
        requiresAdmin: true,
        showBreadcrumb: true,
        breadcrumb: "Support bot",
        breadcrumbParents: sectionCrumbs("tracking"),
      },
      component: () => import("../views/supportbot/SupportBotTreeList.vue"),
    },
    {
      name: "SupportBotTreeCreate",
      path: "support-bot/create",
      meta: {
        requiresAdmin: true,
        showBreadcrumb: true,
        breadcrumb: "Create support bot tree",
        breadcrumbParents: supportBotCrumbs,
      },
      component: () => import("../views/supportbot/SupportBotTreeEdit.vue"),
    },
    {
      name: "SupportBotTreeEdit",
      path: "support-bot/:id(\\d+)/edit",
      meta: {
        requiresAdmin: true,
        showBreadcrumb: true,
        breadcrumb: "Edit support bot tree",
        breadcrumbParents: supportBotCrumbs,
      },
      component: () => import("../views/supportbot/SupportBotTreeEdit.vue"),
    },
    {
      name: "SupportBotConversationList",
      path: "support-bot/:id(\\d+)/conversations",
      meta: {
        requiresAdmin: true,
        showBreadcrumb: true,
        breadcrumb: "Support bot conversations",
        breadcrumbParents: supportBotCrumbs,
      },
      component: () => import("../views/supportbot/SupportBotConversationList.vue"),
    },
    {
      name: "SupportBotConversationDetail",
      path: "support-bot/:id(\\d+)/conversations/:conversationId(\\d+)",
      meta: {
        requiresAdmin: true,
        showBreadcrumb: true,
        breadcrumb: "Support bot conversation",
        breadcrumbParents: supportBotCrumbs,
      },
      component: () => import("../views/supportbot/SupportBotConversationDetail.vue"),
    },
    {
      name: "AdminConfigurationColors",
      path: "configuration/colors",
      meta: {
        requiresAdmin: true,
        requiresSessionAdmin: true,
        showBreadcrumb: true,
        breadcrumb: "Colors",
        breadcrumbParents: adminOnlyCrumbs("settings"),
      },
      component: () => import("../views/admin/AdminConfigureColors.vue"),
    },
    {
      path: "gdpr/third-parties",
      name: "ThirdPartyManager",
      meta: { breadcrumbParents: sectionCrumbs("data_privacy"), requiresAdmin: true, showBreadcrumb: true, breadcrumb: "Third parties" },
      component: () => import("../views/admin/ThirdPartyManager.vue"),
    },
    {
      path: "gdpr/data-exchanges/:thirdPartyId?",
      name: "DataExchangeManager",
      meta: { breadcrumbParents: sectionCrumbs("data_privacy"), requiresAdmin: true, showBreadcrumb: true, breadcrumb: "Data exchanges" },
      component: () => import("../views/admin/DataExchangeManager.vue"),
    },
    {
      name: "TermsConditionsList",
      path: "terms-conditions",
      meta: { breadcrumbParents: sectionCrumbs("platform"), requiresAdmin: true, showBreadcrumb: true, breadcrumb: "Terms and conditions" },
      component: () => import("../views/terms/TermsList.vue"),
    },
    {
      name: "TermsConditionsEdit",
      path: "terms-conditions/edit",
      meta: { breadcrumbParents: termsCrumbs, requiresAdmin: true, showBreadcrumb: true, breadcrumb: "Edit" },
      component: () => import("../views/terms/TermsEdit.vue"),
    },
    {
      name: "TermsConditionsView",
      path: "terms-conditions/view",
      meta: { breadcrumbParents: termsCrumbs, requiresAdmin: true, showBreadcrumb: true, breadcrumb: "Show" },
      component: () => import("../views/terms/Terms.vue"),
    },
    {
      name: "AdminUserList",
      path: "user-list",
      meta: { breadcrumbParents: sectionCrumbs("users"), requiresAdmin: true, requiresSessionAdmin: true, showBreadcrumb: true, breadcrumb: "User list" },
      component: () => import("../views/admin/UserList.vue"),
    },
    {
      name: "AdminUserAdd",
      path: "user-add",
      meta: { breadcrumbParents: sectionCrumbs("users"), requiresAdmin: true, requiresSessionAdmin: true, showBreadcrumb: true, breadcrumb: "Add a user" },
      component: () => import("../views/admin/UserAdd.vue"),
    },
    {
      name: "AdminUserEdit",
      path: "user-edit/:userId",
      meta: {
        breadcrumbParents: sectionCrumbs("users"),
        requiresAdmin: true,
        requiresSessionAdmin: true,
        showBreadcrumb: true,
        breadcrumb: "Edit user information",
      },
      component: () => import("../views/admin/UserEdit.vue"),
    },
    {
      name: "AdminQuestionBank",
      path: "questions",
      meta: {
        requiresQuestionManager: true,
        showBreadcrumb: true,
        breadcrumb: "Questions",
        breadcrumbParents: adminOnlyCrumbs("courses"),
      },
      component: () => import("../views/admin/QuestionBank.vue"),
    },
    {
      name: "AdminBuyCoursesCourseLifecycle",
      path: "buycourses/course-lifecycle",
      meta: {
        requiresAdmin: true,
        showBreadcrumb: true,
        breadcrumb: "Course lifecycle",
        breadcrumbParents: sectionCrumbs("courses"),
      },
      component: () => import("../views/admin/BuyCoursesCourseLifecycle.vue"),
    },
    {
      name: "AdminCourseList",
      path: "course-list",
      meta: { breadcrumbParents: sectionCrumbs("courses"), requiresAdmin: true, showBreadcrumb: true, breadcrumb: "Course list" },
      component: () => import("../views/admin/CourseList.vue"),
    },
    {
      name: "AdminListIcons",
      path: "list-icons",
      meta: { breadcrumbParents: sectionCrumbs("settings"), requiresAdmin: true, showBreadcrumb: true, breadcrumb: "Icons" },
      component: () => import("../views/admin/ListIcons.vue"),
    },
    {
      name: "AdminStatistics",
      path: "statistics",
      // The last crumb names the report the query asks for, and is omitted when there is
      // none, so the trail reads "Administration / Statistics" on the bare page.
      meta: {
        requiresAdmin: true,
        showBreadcrumb: true,
        breadcrumb: (route) => activeReportLabelKey(route),
        breadcrumbParents: [...sectionCrumbs("tracking"), { label: "Statistics", route: { name: "AdminStatistics" } }],
      },
      component: () => import("../views/admin/AdminStatisticsView.vue"),
    },
    {
      name: "AdminSystemStatus",
      path: "system-status",
      meta: { breadcrumbParents: sectionCrumbs("settings"), requiresAdmin: true, showBreadcrumb: true, breadcrumb: "System status" },
      component: () => import("../views/admin/SystemStatus.vue"),
    },
    {
      name: "AdminSystemUpdate",
      path: "system-update",
      meta: { breadcrumbParents: sectionCrumbs("settings"), requiresAdmin: true, showBreadcrumb: true, breadcrumb: "System update" },
      component: () => import("../views/admin/SystemUpdate.vue"),
    },
    {
      name: "AdminSessionList",
      path: "session-list",
      meta: { breadcrumbParents: sectionCrumbs("sessions"), requiresAdmin: true, requiresSessionAdmin: true, showBreadcrumb: true, breadcrumb: "Session list" },
      component: () => import("../views/admin/SessionList.vue"),
    },
    {
      name: "AdminUsergroupList",
      path: "usergroups",
      meta: { breadcrumbParents: sectionCrumbs("users"), requiresAdmin: true, showBreadcrumb: true, breadcrumb: "Classes" },
      component: () => import("../views/admin/UsergroupList.vue"),
    },
    {
      name: "AdminUsergroupPreview",
      path: "usergroups/:id/preview",
      meta: {
        requiresAdmin: true,
        showBreadcrumb: true,
        breadcrumb: "Class overview",
        breadcrumbParents: classesCrumbs,
      },
      component: () => import("../views/admin/UsergroupPreview.vue"),
    },
    {
      name: "AdminUsergroupAddUsers",
      path: "usergroups/:id/add-users",
      meta: {
        requiresAdmin: true,
        showBreadcrumb: true,
        breadcrumb: "Subscribe users to class",
        breadcrumbParents: classesCrumbs,
      },
      component: () => import("../views/admin/UsergroupAddUsers.vue"),
    },
    {
      name: "AdminUsergroupAddCourses",
      path: "usergroups/:id/add-courses",
      meta: {
        requiresAdmin: true,
        showBreadcrumb: true,
        breadcrumb: "Subscribe class to courses",
        breadcrumbParents: classesCrumbs,
      },
      component: () => import("../views/admin/UsergroupAddCourses.vue"),
    },
    {
      name: "AdminUsergroupAddSessions",
      path: "usergroups/:id/add-sessions",
      meta: {
        requiresAdmin: true,
        showBreadcrumb: true,
        breadcrumb: "Subscribe class to sessions",
        breadcrumbParents: classesCrumbs,
      },
      component: () => import("../views/admin/UsergroupAddSessions.vue"),
    },
    {
      name: "AdminUsergroupUsers",
      path: "usergroup-users/:id",
      meta: {
        requiresAdmin: true,
        showBreadcrumb: true,
        breadcrumb: "Class users",
        breadcrumbParents: classesCrumbs,
      },
      component: () => import("../views/admin/UsergroupUsers.vue"),
    },
    {
      name: "AdminUsergroupImport",
      path: "usergroup-import",
      meta: {
        requiresAdmin: true,
        showBreadcrumb: true,
        breadcrumb: "Import class list via CSV",
        breadcrumbParents: classesCrumbs,
      },
      component: () => import("../views/admin/UsergroupImport.vue"),
    },
    {
      name: "AdminUsergroupUserImport",
      path: "usergroup-user-import",
      meta: {
        requiresAdmin: true,
        showBreadcrumb: true,
        breadcrumb: "Add users to a class",
        breadcrumbParents: classesCrumbs,
      },
      component: () => import("../views/admin/UsergroupUserImport.vue"),
    },
    {
      name: "AdminMultiUrlList",
      path: "urls",
      meta: { requiresGlobalAdmin: true, showBreadcrumb: true, breadcrumb: "Multi URLs", breadcrumbParents: sectionCrumbs("platform") },
      component: () => import("../views/admin/MultiUrlList.vue"),
    },
    {
      name: "AccessUrlManage",
      path: "urls/manage",
      meta: {
        requiresGlobalAdmin: true,
        showBreadcrumb: true,
        breadcrumb: "Multiple access URL / Branding",
        breadcrumbParents: multiUrlCrumbs,
      },
      component: () => import("../views/accessurl/AccessUrlManage.vue"),
    },
    {
      name: "AccessUrlUsers",
      path: "urls/assign-users",
      meta: {
        requiresGlobalAdmin: true,
        showBreadcrumb: true,
        breadcrumb: "Manage users",
        breadcrumbParents: accessUrlCrumbs,
      },
      component: () => import("../views/accessurl/AccessUrlUsers.vue"),
    },
    {
      name: "AccessUrlCourses",
      path: "urls/assign-courses",
      meta: {
        requiresGlobalAdmin: true,
        showBreadcrumb: true,
        breadcrumb: "Manage courses",
        breadcrumbParents: accessUrlCrumbs,
      },
      component: () => import("../views/accessurl/AccessUrlCourses.vue"),
    },
    {
      name: "AccessUrlUserGroups",
      path: "urls/assign-usergroups",
      meta: {
        requiresGlobalAdmin: true,
        showBreadcrumb: true,
        breadcrumb: "Manage user groups",
        breadcrumbParents: accessUrlCrumbs,
      },
      component: () => import("../views/accessurl/AccessUrlUserGroups.vue"),
    },
    {
      name: "AccessUrlCourseCategories",
      path: "urls/assign-course-categories",
      meta: {
        requiresGlobalAdmin: true,
        showBreadcrumb: true,
        breadcrumb: "Manage course categories",
        breadcrumbParents: accessUrlCrumbs,
      },
      component: () => import("../views/accessurl/AccessUrlCourseCategories.vue"),
    },
    {
      name: "AdminMultiUrlUserDetail",
      path: "urls/users/:id(\\d+)",
      meta: {
        requiresGlobalAdmin: true,
        showBreadcrumb: true,
        breadcrumb: "User details",
        breadcrumbParents: multiUrlCrumbs,
      },
      component: () => import("../views/admin/MultiUrlUserDetail.vue"),
    },
    {
      name: "AdminMultiUrlCourseDetail",
      path: "urls/courses/:id(\\d+)",
      meta: {
        requiresGlobalAdmin: true,
        showBreadcrumb: true,
        breadcrumb: "Course details",
        breadcrumbParents: multiUrlCrumbs,
      },
      component: () => import("../views/admin/MultiUrlCourseDetail.vue"),
    },
  ],
}
