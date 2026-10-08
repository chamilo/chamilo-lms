const courseRoutes = {
  path: "/conference/teams/course",
  name: "TeamsCourse",
  meta: {
    requiresAuth: true,
    requiresCourseContext: true,
    showBreadcrumb: true,
    tool: "teams",
    breadcrumb: "Microsoft Teams",
    teamsCourseScope: true,
  },
  component: () => import("../components/layout/SimpleRouterViewLayout.vue"),
  redirect: { name: "TeamsCourseList" },
  children: [
    {
      name: "TeamsCourseList",
      path: "",
      component: () => import("../views/teams/TeamsMeetingListView.vue"),
      meta: { breadcrumb: "" },
    },
    {
      name: "TeamsCourseCreate",
      path: "create",
      component: () => import("../views/teams/TeamsMeetingFormView.vue"),
      meta: { breadcrumb: "Create" },
    },
    {
      name: "TeamsCourseDetail",
      path: ":meetingId(\\d+)",
      component: () => import("../views/teams/TeamsMeetingDetailView.vue"),
      meta: { breadcrumb: "Meeting details" },
    },
    {
      name: "TeamsCourseEdit",
      path: ":meetingId(\\d+)/edit",
      component: () => import("../views/teams/TeamsMeetingFormView.vue"),
      meta: { breadcrumb: "Edit" },
    },
  ],
}

const hubRoutes = {
  path: "/conference/teams",
  name: "TeamsHub",
  meta: {
    requiresAuth: true,
    showBreadcrumb: true,
    breadcrumb: "Microsoft Teams",
    teamsCourseScope: false,
  },
  component: () => import("../components/layout/SimpleRouterViewLayout.vue"),
  redirect: { name: "TeamsHubList" },
  children: [
    {
      name: "TeamsHubList",
      path: "",
      component: () => import("../views/teams/TeamsMeetingListView.vue"),
      meta: { breadcrumb: "" },
    },
    {
      name: "TeamsHubCreate",
      path: "create",
      component: () => import("../views/teams/TeamsMeetingFormView.vue"),
      meta: { breadcrumb: "Create" },
    },
    {
      name: "TeamsHubDetail",
      path: ":meetingId(\\d+)",
      component: () => import("../views/teams/TeamsMeetingDetailView.vue"),
      meta: { breadcrumb: "Meeting details" },
    },
    {
      name: "TeamsHubEdit",
      path: ":meetingId(\\d+)/edit",
      component: () => import("../views/teams/TeamsMeetingFormView.vue"),
      meta: { breadcrumb: "Edit" },
    },
  ],
}

export default [courseRoutes, hubRoutes]
