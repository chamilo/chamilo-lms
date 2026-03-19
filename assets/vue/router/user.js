export default {
  path: "/resources/users",
  meta: { requiresAuth: true },
  name: "users",
  component: () => import("../components/user/Layout.vue"),
  children: [
    {
      name: "UserGroupShow",
      //path: ':id',
      path: "show",
      meta: { breadcrumb: "Show" },
      component: () => import("../views/usergroup/Show.vue"),
    },
    {
      name: "PersonalData",
      path: "personal_data",
      meta: { breadcrumb: "Personal data" },
      component: () => import("../views/user/PersonalData.vue"),
    },
  ],
}
