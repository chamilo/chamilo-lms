export default {
  path: "/resources/forum/:node/",
  meta: {
    requiresAuth: true,
    showBreadcrumb: true,
    tool: "forum",
  },
  name: "forum",
  component: () => import("../components/layout/SimpleRouterViewLayout.vue"),
  redirect: { name: "ForumList" },
  children: [
    {
      name: "ForumList",
      path: "",
      meta: { breadcrumb: "Forums" },
      component: () => import("../views/forum/ForumList.vue"),
    },
    {
      name: "ForumThreadList",
      path: "forum/:forumId",
      meta: { breadcrumb: "Threads" },
      component: () => import("../views/forum/ForumThreadList.vue"),
    },
    {
      name: "ForumSearch",
      path: "search",
      meta: { breadcrumb: "Search" },
      component: () => import("../views/forum/ForumSearch.vue"),
    },
    {
      name: "ForumCreateThread",
      path: "forum/:forumId/new-thread",
      meta: { breadcrumb: "Create thread" },
      component: () => import("../views/forum/ForumCreateThread.vue"),
    },
    {
      name: "ForumPostList",
      path: "forum/:forumId/thread/:threadId",
      meta: { breadcrumb: "Posts" },
      component: () => import("../views/forum/ForumPostList.vue"),
    },
    {
      name: "ForumReply",
      path: "forum/:forumId/thread/:threadId/reply",
      meta: { breadcrumb: "Reply" },
      component: () => import("../views/forum/ForumReply.vue"),
    },
  ],
}
