export default {
  path: "/skill",
  component: () => import("../components/layout/SimpleRouterViewLayout.vue"),
  children: [
    {
      name: "SkillWheel",
      path: "wheel",
      meta: {
        requiresAuth: true,
        requiresAdmin: true,
        requiresSessionAdmin: false,
        requiresHR: true,
        showBreadcrumb: true,
        breadcrumb: "Skill wheel",
      },
      component: () => import("../views/skill/SkillWheel.vue"),
    },
    {
      name: "SkillRanking",
      path: "ranking",
      meta: {
        requiresAuth: true,
        showBreadcrumb: true,
        breadcrumb: "Skills ranking",
      },
      component: () => import("../views/skill/SkillRanking.vue"),
    },
  ],
}
