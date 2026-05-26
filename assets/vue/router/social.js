export default {
  path: "/social",
  meta: { requiresAuth: true },
  name: "Social",
  component: () => import("../views/social/SocialLayout.vue"),
  children: [
    {
      name: "SocialWall",
      path: ":filterType?",
      meta: { breadcrumb: "Social wall" },
      component: () => import("../views/social/SocialWall.vue"),
    },
    {
      name: "SocialSearch",
      path: "search",
      meta: { breadcrumb: "Search" },
      component: () => import("../views/social/SocialSearch.vue"),
    },
  ],
}
