export default {
  path: "/tickets",
  meta: {
    requiresAuth: true,
    showBreadcrumb: true,
    breadcrumb: "My tickets",
  },
  name: "ticket",
  component: () => import("../components/layout/SimpleRouterViewLayout.vue"),
  redirect: { name: "TicketList" },
  children: [
    {
      name: "TicketList",
      path: "",
      meta: {
        requiresAuth: true,
        breadcrumb: "My tickets",
      },
      component: () => import("../views/ticket/TicketListView.vue"),
    },
    {
      name: "TicketCreate",
      path: "create",
      meta: {
        requiresAuth: true,
        breadcrumb: "Compose message",
      },
      component: () => import("../views/ticket/TicketCreateView.vue"),
    },
    {
      name: "TicketDetail",
      path: ":id(\\d+)",
      meta: {
        requiresAuth: true,
        breadcrumb: "Ticket details",
      },
      component: () => import("../views/ticket/TicketDetailView.vue"),
    },
  ],
}
