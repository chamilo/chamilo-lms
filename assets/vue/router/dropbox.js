export default {
  path: "/resources/dropbox/:node/",
  meta: {
    requiresAuth: true,
    showBreadcrumb: true,
  },
  name: "dropbox",
  component: () => import("../components/dropbox/DropboxLayout.vue"),
  redirect: (to) => ({
    name: "DropboxListReceived",
    params: to.params,
    query: to.query,
  }),
  children: [
    {
      name: "DropboxListSent",
      path: "sent",
      component: () => import("../views/dropbox/DropboxListSent.vue"),
      meta: { breadcrumb: "Sent files" },
    },
    {
      name: "DropboxListReceived",
      path: "received",
      component: () => import("../views/dropbox/DropboxListReceived.vue"),
      meta: { breadcrumb: "Received files" },
    },
    {
      name: "DropboxCreate",
      path: "new",
      component: () => import("../views/dropbox/DropboxCreate.vue"),
      meta: { breadcrumb: "Share a new file" },
    },
    {
      name: "DropboxCategoryCreate",
      path: "folders/new",
      component: () => import("../views/dropbox/DropboxCategoryCreate.vue"),
      meta: { breadcrumb: "New folder" },
    },
    {
      name: "DropboxCategoryUpdate",
      path: "folders/edit/:id",
      component: () => import("../views/dropbox/DropboxCategoryUpdate.vue"),
      props: true,
      meta: { breadcrumb: "Edit folder" },
    },
  ],
}
