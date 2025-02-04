export default {
  path: "/resources/attendance/:node/",
  meta: { requiresAuth: true, showBreadcrumb: true },
  name: "attendance",
  component: () => import("../components/layout/SimpleRouterViewLayout.vue"),
  redirect: { name: "AttendanceList" },
  children: [
    {
      name: "AttendanceList",
      path: "",
      component: () => import("../views/attendance/AttendanceList.vue"),
    },
    {
      name: "CreateAttendance",
      path: "create",
      component: () => import("../views/attendance/AttendanceCreate.vue"),
    },
    {
      name: "EditAttendance",
      path: "edit/:id",
      component: () => import("../views/attendance/AttendanceEdit.vue"),
    },
    {
      name: "AttendanceSheetList",
      path: ":id?/sheet-list",
      component: () => import("../views/attendance/AttendanceSheetList.vue"),
    },
    {
      name: "CalendarList",
      path: ":id?/calendar",
      component: () => import("../views/attendance/AttendanceCalendarList.vue"),
    },
    {
      name: "AddCalendarEvent",
      path: ":id?/calendar/create",
      component: () => import("../views/attendance/AttendanceCalendarAdd.vue"),
    },
    {
      name: "ExportToPdf",
      path: ":id?/export/pdf",
      component: () => import("../views/attendance/AttendanceExport.vue"),
    },
  ],
}
