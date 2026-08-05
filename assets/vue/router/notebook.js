export default {
  path: "/resources/notebook/:node/",
  meta: {
    requiresAuth: true,
    requiresCourseContext: true,
    showBreadcrumb: true,
    tool: "notebook",
    breadcrumb: "Notebook",
  },
  name: "notebook",
  component: () => import("../components/layout/SimpleRouterViewLayout.vue"),
  redirect: { name: "NotebookList" },
  children: [
    {
      name: "NotebookList",
      path: "",
      meta: { requiresAuth: true, breadcrumb: "Notebook" },
      component: () => import("../views/notebook/NotebookListView.vue"),
    },
    {
      name: "NotebookAdd",
      path: "add",
      meta: { requiresAuth: true, breadcrumb: "Add" },
      component: () => import("../views/notebook/NotebookFormView.vue"),
    },
    {
      name: "NotebookEdit",
      path: "edit/:id(\\d+)",
      meta: { requiresAuth: true, breadcrumb: "Edit" },
      component: () => import("../views/notebook/NotebookFormView.vue"),
    },
  ],
}
