export default {
  path: "/resources/assignment/:node(\\d+)",
  meta: {
    requiresAuth: true,
    showBreadcrumb: true,
  },
  name: "assignments",
  component: () => import("../components/assignments/AssignmentsLayout.vue"),
  redirect: { name: "AssigmnentsList" },
  children: [
    {
      name: "AssigmnentsList",
      path: "",
      component: () => import("../views/assignments/AssignmentsList.vue"),
    },
    {
      name: "AssigmnentsCreate",
      path: "new",
      component: () => import("../views/assignments/AssignmentsCreate.vue"),
    },
    {
      name: "AssigmnentsUpdate",
      path: "edit",
      component: () => import("../views/assignments/AssignmentsUpdate.vue"),
    },
  ],
};
