export default {
  path: "/resources/assignment/:node(\\d+)",
  meta: {
    requiresAuth: true,
    showBreadcrumb: true,
  },
  name: "assignments",
  component: () => import("../components/assignments/AssignmentsLayout.vue"),
  redirect: { name: "AssignmentsList" },
  children: [
    {
      name: "AssignmentsList",
      path: "",
      component: () => import("../views/assignments/AssignmentsList.vue"),
    },
    {
      name: "AssignmentsCreate",
      path: "new",
      component: () => import("../views/assignments/AssignmentsCreate.vue"),
    },
    {
      name: "AssignmentsUpdate",
      path: "edit/:id",
      component: () => import("../views/assignments/AssignmentsUpdate.vue"),
      props: true,
    },
  ],
};
