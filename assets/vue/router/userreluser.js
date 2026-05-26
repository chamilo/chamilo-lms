export default {
  path: "/resources/friends",
  meta: { requiresAuth: true },
  name: "friends",
  component: () => import("../components/userreluser/Layout.vue"),
  redirect: { name: "UserRelUserList" },
  children: [
    {
      name: "UserRelUserList",
      path: "",
      meta: { breadcrumb: "My friends" },
      component: () => import("../views/userreluser/UserRelUserList.vue"),
    },
    {
      name: "UserRelUserAdd",
      path: "add",
      meta: { breadcrumb: "Add" },
      component: () => import("../views/userreluser/UserRelUserAdd.vue"),
    },
    {
      name: "UserRelUserSearch",
      path: "search",
      meta: { breadcrumb: "Search" },
      component: () => import("../views/userreluser/UserRelUserSearch.vue"),
    },
    {
      name: "Invitations",
      path: "invitations",
      meta: { breadcrumb: "Invitations" },
      component: () => import("../views/userreluser/Invitations.vue"),
    },
  ],
}
