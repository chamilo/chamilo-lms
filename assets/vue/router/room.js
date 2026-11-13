// This tool lives under Administration. Labels are translation keys.
const adminCrumb = { label: "Administration", route: { name: "AdminIndex" } }
const sectionCrumb = { label: "Rooms", route: { name: "AdminRoomsSection" } }
const roomCrumbs = [adminCrumb, sectionCrumb, { label: "List", route: { name: "RoomList" } }]

export default {
  path: "/resources/rooms",
  meta: { requiresAuth: true, requiresAdmin: true, showBreadcrumb: true, breadcrumb: "Rooms" },
  name: "rooms",
  component: () => import("../components/room/Layout.vue"),
  redirect: { name: "RoomList" },
  children: [
    {
      name: "RoomList",
      path: "",
      meta: { breadcrumb: "List", breadcrumbParents: [adminCrumb, sectionCrumb] },
      component: () => import("../views/room/List.vue"),
    },
    {
      name: "RoomCreate",
      path: "new",
      meta: { breadcrumb: "Create", breadcrumbParents: roomCrumbs },
      component: () => import("../views/room/Create.vue"),
    },
    {
      name: "RoomUpdate",
      path: "edit",
      meta: { breadcrumb: "Edit", breadcrumbParents: roomCrumbs },
      component: () => import("../views/room/Update.vue"),
    },
    {
      name: "RoomOccupation",
      path: ":id/occupation",
      meta: { breadcrumb: "Occupation", breadcrumbParents: roomCrumbs },
      component: () => import("../views/room/Occupation.vue"),
    },
    {
      name: "RoomAvailability",
      path: "availability",
      meta: { breadcrumb: "Availability", breadcrumbParents: roomCrumbs },
      component: () => import("../views/room/Availability.vue"),
    },
  ],
}
