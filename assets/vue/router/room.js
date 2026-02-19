export default {
  path: "/resources/rooms",
  meta: { requiresAuth: true, requiresAdmin: true, showBreadcrumb: true },
  name: "rooms",
  component: () => import("../components/room/Layout.vue"),
  redirect: { name: "RoomList" },
  children: [
    {
      name: "RoomList",
      path: "",
      component: () => import("../views/room/List.vue"),
    },
    {
      name: "RoomCreate",
      path: "new",
      meta: { breadcrumb: "Create" },
      component: () => import("../views/room/Create.vue"),
    },
    {
      name: "RoomUpdate",
      path: "edit",
      meta: { breadcrumb: "Edit" },
      component: () => import("../views/room/Update.vue"),
    },
    {
      name: "RoomOccupation",
      path: ":id/occupation",
      meta: { breadcrumb: "Occupation" },
      component: () => import("../views/room/Occupation.vue"),
    },
    {
      name: "RoomAvailability",
      path: "availability",
      meta: { breadcrumb: "Availability" },
      component: () => import("../views/room/Availability.vue"),
    },
  ],
}
