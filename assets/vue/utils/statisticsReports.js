// The statistics report menu, shared by AdminStatisticsView and the admin router, which
// needs it to name the active report in the breadcrumb. Every `label` is a translation key.
export const reportGroups = [
  {
    label: "Courses",
    items: [
      { report: "courses", label: "Courses" },
      { report: "tools", label: "Tools access" },
      { report: "tool_usage", label: "Tool-based resource count" },
      { report: "courselastvisit", label: "Latest access" },
      { report: "coursebylanguage", label: "Number of courses by language" },
      { report: "courses_usage", label: "Courses usage" },
    ],
  },
  {
    label: "Users",
    items: [
      { report: "users_online", label: "Users online" },
      { report: "users", label: "Number of users" },
      { report: "recentlogins", label: "Logins" },
      { report: "logins", type: "month", label: "Logins (Month)" },
      { report: "logins", type: "day", label: "Logins (Day)" },
      { report: "logins", type: "hour", label: "Logins (Hour)" },
      { report: "pictures", label: "Number of users (Picture)" },
      { report: "logins_by_date", label: "Logins by date" },
      { report: "no_login_users", label: "Not logged in for some time" },
      { report: "zombies", label: "Zombies" },
      { report: "users_active", label: "Users statistics" },
      { report: "new_user_registrations", label: "New users registrations" },
      { report: "subscription_by_day", label: "Course/Session subscriptions by day" },
      { report: "duplicated_users", label: "Duplicate users" },
    ],
  },
  {
    label: "System",
    items: [
      { report: "user_session", label: "Portal user session stats" },
      { report: "quarterly_report", label: "Quarterly report" },
    ],
  },
  {
    label: "Social",
    items: [
      { report: "messagereceived", label: "Number of messages received" },
      { report: "messagesent", label: "Number of messages sent" },
      { report: "friends", label: "Contacts count" },
    ],
  },
  {
    label: "Session",
    items: [{ report: "session_by_date", label: "Sessions by date" }],
  },
]

/**
 * Tells whether a report entry is the one the route asks for.
 *
 * @param {Object} item - One entry of a group's `items`.
 * @param {import('vue-router').RouteLocationNormalized} route - The current route.
 * @returns {boolean} True when the entry matches the route's `report` and `type`.
 */
export function isReportOfRoute(item, route) {
  if (String(route.query?.report || "") !== item.report) {
    return false
  }
  if (!item.type) {
    return true
  }

  return String(route.query?.type || "month") === item.type
}

/**
 * Resolves the translation key that names the report the route asks for.
 *
 * @param {import('vue-router').RouteLocationNormalized} route - The current route.
 * @returns {string} The report's label key, or an empty string when no report is selected.
 */
export function activeReportLabelKey(route) {
  for (const group of reportGroups) {
    const item = group.items.find((entry) => isReportOfRoute(entry, route))

    if (item) {
      return item.label
    }
  }

  return ""
}
