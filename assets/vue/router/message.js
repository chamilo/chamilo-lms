export default {
  path: "/resources/messages",
  meta: { requiresAuth: true },
  name: "messages",
  component: () => import("../components/message/MessageLayout.vue"),
  redirect: { name: "MessageList" },
  children: [
    {
      name: "MessageList",
      path: "",
      meta: { breadcrumb: "Inbox" },
      component: () => import("../views/message/MessageList.vue"),
    },
    {
      name: "MessageCreate",
      path: "new",
      meta: { breadcrumb: "Compose" },
      component: () => import("../views/message/MessageCreate.vue"),
    },
    {
      name: "MessageReply",
      path: "reply",
      meta: { breadcrumb: "Reply" },
      component: () => import("../views/message/MessageReply.vue"),
    },
    /*{
      name: 'MessageUpdate',
      path: ':id/edit',
      component: () => import('../views/message/Update.vue')
    },*/
    {
      name: "MessageShow",
      //path: ':id',
      path: "show",
      meta: { breadcrumb: "Show" },
      component: () => import("../views/message/MessageShow.vue"),
    },
  ],
}
