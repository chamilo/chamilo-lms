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
      name: "AttendanceEditAttendance",
      path: "edit/:id",
      component: () => import("../views/attendance/AttendanceEdit.vue"),
      meta: { breadcrumb: "Edit attendance" },
    },
    {
      name: "AttendanceSheetList",
      path: ":id/sheet-list",
      component: () => import("../views/attendance/AttendanceSheetList.vue"),
      meta: { breadcrumb: "Sheet list" },
    },
    {
      name: "AttendanceCalendarList",
      path: ":id/calendar",
      component: () => import("../views/attendance/AttendanceCalendarList.vue"),
      meta: { breadcrumb: "Calendar" },
    },
    {
      name: "AttendanceAddCalendarEvent",
      path: ":id/calendar/create",
      component: () => import("../views/attendance/AttendanceCalendarAdd.vue"),
      meta: { breadcrumb: "Add calendar" },
    },
    {
      name: "ExportToPdf",
      path: ":id?/export/pdf",
      component: () => import("../views/attendance/AttendanceExport.vue"),
    },
    {
      name: "AttendanceSheetTablet",
      path: ":id/sheet/:calendarId/tablet",
      component: () => import("../views/attendance/AttendanceSheetTablet.vue"),
      meta: { breadcrumb: "Tablet view" },
    },
  ],
}
