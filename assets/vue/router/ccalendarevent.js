export default {
  path: "/resources/ccalendarevent",
  meta: { requiresAuth: true },
  name: "ccalendarevent",
  redirect: { name: "CCalendarEventList" },
  component: () => import("../components/ccalendarevent/CCalendarEventLayout.vue"),
  children: [
    {
      name: "CCalendarEventShow",
      path: "show",
      component: () => import("../views/ccalendarevent/CCalendarEventShow.vue"),
      meta: { breadcrumb: "Event" },
    },
    {
      name: "CCalendarEventCreate",
      path: "new",
      component: () => import("../views/ccalendarevent/CCalendarEventCreate.vue"),
      meta: { breadcrumb: "Add event" },
    },
    {
      name: "CCalendarEventList",
      path: "",
      component: () => import("../views/ccalendarevent/CCalendarEventList.vue"),
      props: (route) => ({ type: route.query.type }),
      meta: { breadcrumb: "" },
    },
    {
      name: "CCalendarEventListView",
      path: "list",
      component: () => import("../views/ccalendarevent/CCalendarEventListView.vue"),
      meta: { breadcrumb: "Events list" },
    },

    {
      name: "CalendarSessionsPlan",
      path: "sessions-plan",
      component: () => import("../views/ccalendarevent/CalendarSessionsPlan.vue"),
      meta: { breadcrumb: "Sessions plan calendar" },
    },
    {
      name: "CalendarMyStudentsSchedule",
      path: "my-students-schedule",
      component: () => import("../views/ccalendarevent/CalendarMyStudentsSchedule.vue"),
      meta: { breadcrumb: "My students schedule" },
    },
  ],
}
