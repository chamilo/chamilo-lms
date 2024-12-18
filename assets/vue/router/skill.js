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
        showBreadcrumb: false,
      },
      component: () => import("../views/skill/SkillWheel.vue"),
    },
  ],
}
