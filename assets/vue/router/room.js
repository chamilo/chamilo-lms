export default {
  path: "/resources/rooms",
  meta: { requiresAuth: true, requiresAdmin: true },
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
      component: () => import("../views/room/Create.vue"),
    },
    {
      name: "RoomUpdate",
      path: "edit",
      component: () => import("../views/room/Update.vue"),
    },
    {
      name: "RoomOccupation",
      path: ":id/occupation",
      component: () => import("../views/room/Occupation.vue"),
    },
    {
      name: "RoomAvailability",
      path: "availability",
      component: () => import("../views/room/Availability.vue"),
    },
  ],
}
