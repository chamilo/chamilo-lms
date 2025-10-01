export default {
  path: "/resources/blog/:node/:blogId/",
  meta: {
    requiresAuth: true,
    showBreadcrumb: true,
  },
  name: "blog",
  component: () => import("../components/blog/BlogLayout.vue"),
  redirect: (to) => ({
    name: "BlogPosts",
    params: to.params,
    query: to.query,
  }),
  children: [
    {
      name: "BlogPosts",
      path: "posts",
      component: () => import("../views/blog/BlogPosts.vue"),
      meta: { breadcrumb: "Posts" },
    },
    {
      name: "BlogPostDetail",
      path: "posts/:postId",
      component: () => import("../views/blog/BlogPostDetail.vue"),
      props: true,
      meta: { breadcrumb: "Post detail" },
    },
    {
      name: "BlogTasks",
      path: "tasks",
      component: () => import("../views/blog/BlogTasks.vue"),
      meta: { breadcrumb: "Tasks" },
    },
    {
      name: "BlogMembers",
      path: "members",
      component: () => import("../views/blog/BlogMembers.vue"),
      meta: { breadcrumb: "Members" },
    },
  ],
}
