export default {
  path: "/resources/announcement/:node/",
  meta: {
    requiresCourseContext: true,
    allowAnonymousAccess: true,
    showBreadcrumb: true,
    tool: "announcement",
    breadcrumb: "Announcements",
  },
  name: "announcement",
  component: () => import("../components/layout/SimpleRouterViewLayout.vue"),
  redirect: { name: "AnnouncementList" },
  children: [
    {
      name: "AnnouncementList",
      path: "",
      meta: {
        requiresAuth: false,
        allowAnonymousAccess: true,
        breadcrumb: "Announcements",
      },
      component: () => import("../views/announcement/AnnouncementListView.vue"),
    },
    {
      name: "AnnouncementDetail",
      path: "view/:id",
      meta: {
        requiresAuth: false,
        allowAnonymousAccess: true,
        breadcrumb: "View",
      },
      component: () => import("../views/announcement/AnnouncementDetailView.vue"),
    },
    {
      name: "AnnouncementAdd",
      path: "add",
      meta: {
        requiresAuth: true,
        breadcrumb: "Add an announcement",
      },
      component: () => import("../views/announcement/AnnouncementFormView.vue"),
    },
    {
      name: "AnnouncementEdit",
      path: "edit/:id",
      meta: {
        requiresAuth: true,
        breadcrumb: "Edit announcement",
      },
      component: () => import("../views/announcement/AnnouncementFormView.vue"),
    },
  ],
}
