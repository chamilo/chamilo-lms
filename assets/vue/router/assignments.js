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
    {
      name: "AssignmentDetail",
      path: "submission/:id",
      component: () => import("../views/assignments/AssignmentDetail.vue"),
      props: true,
    },
    {
      name: "AssignmentSubmit",
      path: ":id/submit",
      component: () => import("../views/assignments/AssignmentSubmit.vue"),
      props: true,
    },
    {
      name: "AssignmentAddDocument",
      path: ":id/add-doc",
      component: () => import("../views/assignments/AssignmentAddDocument.vue"),
      props: true,
    },
    {
      name: "AssignmentAddUser",
      path: ":id/add-user",
      component: () => import("../views/assignments/AssignmentAddUser.vue"),
      props: true,
    },
    {
      name: "AssignmentUploadCorrections",
      path: ":id/corrections",
      component: () => import("../views/assignments/AssignmentUploadCorrections.vue"),
      props: true,
    },
    {
      name: "AssignmentMissing",
      path: ":id/missing",
      component: () => import("../views/assignments/AssignmentMissing.vue"),
      props: true,
    },
  ],
}
