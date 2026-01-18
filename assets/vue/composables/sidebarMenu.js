import { useI18n } from "vue-i18n"
import { computed } from "vue"
import { useSecurityStore } from "../store/securityStore"
import { usePlatformConfig } from "../store/platformConfig"
import { useEnrolledStore } from "../store/enrolledStore"
import { useRoute } from "vue-router"
import { useSocialMenuItems } from "./useSocialMenuItems"

const ROLE_MAP = {
  ROLE_ADMIN: "ADMIN",
  ROLE_SESSION_MANAGER: "SESSIONADMIN",
  ROLE_TEACHER: "COURSEMANAGER",
  ROLE_STUDENT_BOSS: "STUDENT_BOSS",
  ROLE_DRH: "DRH",
  ROLE_INVITEE: "INVITEE",
  ROLE_STUDENT: "STUDENT",
}

// We keep this list only to support legacy array -> full replacement behavior.
// Unknown keys in JSON simply won't have any effect in the UI.
const KNOWN_MENU_TABS = [
  "campus_homepage",
  "my_courses",
  "reporting",
  "platform_administration",
  "my_agenda",
  "social",
  "videoconference",
  "diagnostics",
  "catalogue",
  "session_admin",
  "search",
  "question_manager",
]

const KNOWN_TOPBAR_TABS = ["topbar_certificate", "topbar_skills"]

function safeParseJson(value, warnLabel) {
  if (!value || "string" !== typeof value) return null
  try {
    return JSON.parse(value)
  } catch (e) {
    console.warn(warnLabel, e)
    return null
  }
}

function makeEmptyConfig() {
  return { menu: {}, topbar: {} }
}

function normalizeConfigObject(obj) {
  const cfg = makeEmptyConfig()
  if (!obj || "object" !== typeof obj) return cfg

  if (obj.menu && "object" === typeof obj.menu) cfg.menu = { ...obj.menu }
  if (obj.topbar && "object" === typeof obj.topbar) cfg.topbar = { ...obj.topbar }

  return cfg
}

// Legacy list semantics: only listed tabs are enabled, all known tabs are disabled.
function configFromLegacyList(list) {
  const cfg = makeEmptyConfig()

  for (const k of KNOWN_MENU_TABS) cfg.menu[k] = false
  for (const k of KNOWN_TOPBAR_TABS) cfg.topbar[k] = false

  const arr = Array.isArray(list) ? list : []
  for (const key of arr) {
    if ("string" !== typeof key) continue
    if (KNOWN_MENU_TABS.includes(key)) cfg.menu[key] = true
    if (KNOWN_TOPBAR_TABS.includes(key)) cfg.topbar[key] = true
  }

  return cfg
}

// Merge semantics: role config overrides default config (only for keys provided)
function mergeConfig(baseCfg, overrideCfg) {
  const out = makeEmptyConfig()

  out.menu = { ...(baseCfg?.menu || {}) }
  out.topbar = { ...(baseCfg?.topbar || {}) }

  if (overrideCfg?.menu && "object" === typeof overrideCfg.menu) {
    for (const [k, v] of Object.entries(overrideCfg.menu)) out.menu[k] = v
  }
  if (overrideCfg?.topbar && "object" === typeof overrideCfg.topbar) {
    for (const [k, v] of Object.entries(overrideCfg.topbar)) out.topbar[k] = v
  }

  return out
}

function resolveDisplayTabsConfig(platformConfigStore, securityStore) {
  // display.show_tabs (default)
  const showTabsRaw = platformConfigStore.getSetting("display.show_tabs")
  let defaultCfg = makeEmptyConfig()

  if (Array.isArray(showTabsRaw)) {
    // Very old installations may still provide an array
    defaultCfg = configFromLegacyList(showTabsRaw)
  } else if ("string" === typeof showTabsRaw && showTabsRaw.trim() !== "") {
    const parsed = safeParseJson(showTabsRaw, "[Sidebar] Invalid JSON in display.show_tabs")
    if (Array.isArray(parsed)) {
      defaultCfg = configFromLegacyList(parsed)
    } else {
      defaultCfg = normalizeConfigObject(parsed)
    }
  }

  // display.show_tabs_per_role (overrides)
  const perRoleRaw = platformConfigStore.getSetting("display.show_tabs_per_role") || ""
  const perRole = safeParseJson(perRoleRaw, "[Sidebar] Invalid JSON in display.show_tabs_per_role") || {}

  const roles = securityStore.user?.roles || []
  for (const role of roles) {
    const mappedRole = ROLE_MAP[role] || role
    const roleValue = perRole?.[mappedRole]
    if (!roleValue) continue

    // Backward compatible:
    // - If role value is an array -> full replacement behavior (legacy)
    // - If role value is an object with menu/topbar -> override behavior (recommended)
    if (Array.isArray(roleValue)) {
      return configFromLegacyList(roleValue)
    }

    const roleCfg = normalizeConfigObject(roleValue)
    return mergeConfig(defaultCfg, roleCfg)
  }

  return defaultCfg
}

export function useSidebarMenu() {
  const { t } = useI18n()
  const route = useRoute()
  const securityStore = useSecurityStore()
  const platformConfigStore = usePlatformConfig()
  const enrolledStore = useEnrolledStore()
  const { items: socialItems } = useSocialMenuItems()

  const allowSocialTool = computed(() => platformConfigStore.getSetting("social.allow_social_tool") !== "false")
  const allowSearchFeature = computed(() => platformConfigStore.getSetting("search.search_enabled") === "true")

  const displayTabs = computed(() => resolveDisplayTabsConfig(platformConfigStore, securityStore))

  const isMenuTabEnabled = (key) => displayTabs.value?.menu?.[key] === true
  const isTopbarTabEnabled = (key) => displayTabs.value?.topbar?.[key] === true // kept for completeness

  const rawShowCatalogue = platformConfigStore.getSetting("catalog.show_courses_sessions")
  const showCatalogue = Number(rawShowCatalogue)
  const isAnonymous = !securityStore.isAuthenticated
  const isPrivilegedUser =
    securityStore.isAdmin || securityStore.isTeacher || securityStore.isHRM || securityStore.isSessionAdmin
  const allowStudentCatalogue = computed(() => {
    if (isAnonymous) {
      return platformConfigStore.getSetting("catalog.course_catalog_published") !== "false"
    }

    if (isPrivilegedUser) {
      return true
    }

    if (securityStore.isStudent) {
      return platformConfigStore.getSetting("catalog.allow_students_to_browse_courses") !== "false"
    }

    return false
  })

  const isActive = (item) => {
    if (item.route) {
      return route.path === item.route || (item.route.name && route.name === item.route.name)
    } else if (item.items) {
      return item.items.some((subItem) => isActive(subItem))
    }
    return false
  }

  const createMenuItem = (key, icon, label, opts = null) => {
    if (!isMenuTabEnabled(key)) return null

    const item = {
      icon: `mdi ${icon}`,
      label: t(label),
    }

    if (typeof opts === "string") {
      item.route = { name: opts }
    } else if (opts) {
      if (opts.url) item.url = opts.url
      if (opts.routeName) item.route = { name: opts.routeName }
      if (opts.subItems) item.items = opts.subItems
    }

    return item
  }

  const menuItemsBeforeMyCourse = computed(() => {
    const items = []
    items.push(createMenuItem("campus_homepage", "mdi-home", "Home", "Home"))
    return items.filter(Boolean)
  })

  const menuItemMyCourse = computed(() => {
    const items = []

    if (securityStore.isAuthenticated && isMenuTabEnabled("my_courses")) {
      const courseItems = []

      if (enrolledStore.isEnrolledInCourses) {
        courseItems.push({
          label: t("My courses"),
          route: { name: "MyCourses" },
        })
      }

      if (enrolledStore.isEnrolledInSessions) {
        courseItems.push({
          label: t("My sessions"),
          route: { name: "MySessions" },
        })
      }

      if (courseItems.length > 0) {
        items.push({
          icon: "mdi mdi-book-open-page-variant",
          label: courseItems.length > 1 ? t("Courses") : courseItems[0].label,
          items: courseItems.length > 1 ? courseItems : undefined,
          route: 1 === courseItems.length ? courseItems[0].route : undefined,
          class: courseItems.length > 0 ? courseItems[0].class : "",
        })
      }
    }

    return items
  })

  const menuItemsAfterMyCourse = computed(() => {
    const items = []

    if (allowStudentCatalogue.value && isMenuTabEnabled("catalogue")) {
      if (showCatalogue === 0 || showCatalogue === 2) {
        items.push(createMenuItem("catalogue", "mdi-bookmark-multiple", "Explore more courses", "CatalogueCourses"))
      }
      if (showCatalogue > 0) {
        items.push(
          createMenuItem("catalogue", "mdi-bookmark-multiple-outline", "Sessions catalogue", "CatalogueSessions"),
        )
      }
    }

    items.push(createMenuItem("my_agenda", "mdi-calendar-text", "Events", "CCalendarEventList"))

    if (allowSearchFeature.value && isMenuTabEnabled("search")) {
      items.push({
        icon: "mdi mdi-magnify",
        label: t("Search"),
        url: "/search/ui",
      })
    }

    if (isMenuTabEnabled("reporting")) {
      const subItems = []

      if (securityStore.isTeacher || securityStore.isHRM || securityStore.isSessionAdmin) {
        subItems.push({
          label: securityStore.isHRM ? t("Course sessions") : t("Reporting"),
          url: "/main/my_space/" + (securityStore.isHRM ? "session.php" : "index.php"),
        })
      } else if (securityStore.isStudentBoss) {
        subItems.push({
          label: t("Learners"),
          url: "/main/my_space/student.php",
        })
      } else {
        subItems.push({
          label: t("Progress"),
          url: "/main/auth/my_progress.php",
        })
      }

      items.push({
        icon: "mdi mdi-chart-box",
        label: t("Reporting"),
        items: subItems,
      })
    }

    if (isMenuTabEnabled("social")) {
      if (allowSocialTool.value) {
        const styledSocialItems = socialItems.value.map((item) => {
          const newItem = {
            ...item,
            class: `${isActive(item) ? "p-focus" : ""} pl-4`,
            icon: item.icon ? item.icon : undefined,
          }

          if (newItem.isLink && newItem.route) {
            newItem.url = newItem.route
          } else if (newItem.route) {
            // nothing to do
          } else if (newItem.link) {
            newItem.url = newItem.link
          }

          return newItem
        })

        items.push({
          icon: "mdi mdi-sitemap-outline",
          label: t("Social network"),
          items: styledSocialItems,
          expanded: isActive({ items: styledSocialItems }),
        })
      }
    }

    if (
      isMenuTabEnabled("videoconference") &&
      platformConfigStore.plugins?.bbb?.show_global_conference_link &&
      platformConfigStore.plugins?.bbb?.listingURL
    ) {
      const conferenceItems = [
        {
          label: t("Conference Room"),
          url: platformConfigStore.plugins.bbb.listingURL,
        },
      ]

      if (conferenceItems.length > 0) {
        items.push(
          createMenuItem("videoconference", "mdi-video", "Videoconference", {
            subItems: conferenceItems,
          }),
        )
      }
    }

    if (isMenuTabEnabled("diagnostics")) {
      const subItems = [
        {
          label: t("Diagnosis management"),
          url: "/main/search/load_search.php",
          visible: securityStore.isStudentBoss,
        },
        {
          label: t("Diagnosis"),
          url: "/main/search/search.php",
        },
      ].filter((item) => item.visible !== false)

      if (subItems.length > 0) {
        items.push(
          createMenuItem("diagnostics", "mdi-text-box-search", "Diagnosis management", {
            subItems,
          }),
        )
      }
    }

    {
      const roles = securityStore.user?.roles || []
      const isQuestionManager = securityStore.isAdmin || roles.includes("ROLE_QUESTION_MANAGER")

      if (isQuestionManager && isMenuTabEnabled("question_manager")) {
        const questionAdminItems = [
          {
            label: t("Questions"),
            url: "/main/admin/questions.php",
            icon: "mdi mdi-comment-question-outline",
            class: "pl-4",
          },
        ]

        items.push({
          icon: "mdi mdi-comment-question-outline",
          label: t("Question manager"),
          items: questionAdminItems,
          expanded: isActive({ items: questionAdminItems }),
        })
      }
    }

    if (isMenuTabEnabled("session_admin") && (securityStore.isAdmin || securityStore.isSessionAdmin)) {
      const sessionAdminItems = [
        {
          label: t("Dashboard"),
          route: { name: "AdminDashboard" },
          icon: "mdi mdi-view-dashboard-outline",
          class: "pl-4",
        },
        {
          label: t("Favorite courses"),
          route: { name: "AdminFavoritesCourses" },
          icon: "mdi mdi-star-outline",
          class: "pl-4",
        },
        {
          label: t("Completed courses"),
          route: { name: "AdminCompletedCourses" },
          icon: "mdi mdi-check-circle-outline",
          class: "pl-4",
        },
        {
          label: t("Incomplete courses"),
          route: { name: "AdminIncompleteCourses" },
          icon: "mdi mdi-progress-close",
          class: "pl-4",
        },
        {
          label: t("Restartable courses"),
          route: { name: "AdminRestartCourses" },
          icon: "mdi mdi-history",
          class: "pl-4",
        },
      ]

      items.push({
        icon: "mdi mdi-account-cog",
        label: t("Session administrator"),
        items: sessionAdminItems,
      })
    }

    if (isMenuTabEnabled("platform_administration")) {
      if (securityStore.isAdmin || securityStore.isSessionAdmin) {
        const adminItems = [
          { label: t("Administration"), route: { name: "AdminIndex" } },
          ...(securityStore.isSessionAdmin &&
          "true" === platformConfigStore.getSetting("session.limit_session_admin_list_users")
            ? [{ label: t("Add user"), url: "/main/admin/user_add.php" }]
            : [{ label: t("Users"), url: "/main/admin/user_list.php" }]),
          { label: t("Courses"), url: "/main/admin/course_list.php" },
          { label: t("Sessions"), url: "/main/session/session_list.php" },
          ...(securityStore.isAdmin ? [{ label: t("Settings"), url: "/admin/settings" }] : []),
        ]

        items.push({
          icon: "mdi mdi-cog",
          items: adminItems,
          label: t("Administration"),
        })
      }
    }

    const filteredItems = items.filter(Boolean)

    if (filteredItems.length === 1) {
      const singleItem = filteredItems[0]
      if (singleItem.items && !singleItem.expanded) {
        singleItem.expanded = true
      }
    }

    ensureKeys(filteredItems)

    return filteredItems
  })

  const hasOnlyOneItem = computed(() => {
    const totalItems =
      (menuItemsBeforeMyCourse.value?.length || 0) +
      (menuItemMyCourse.value?.length || 0) +
      (menuItemsAfterMyCourse.value?.length || 0)

    return totalItems === 1
  })

  async function initialize() {
    await enrolledStore.initialize()
  }

  return {
    menuItemsBeforeMyCourse,
    menuItemMyCourse,
    menuItemsAfterMyCourse,
    hasOnlyOneItem,
    initialize,
  }
}

function ensureKeys(items) {
  for (const item of items) {
    if (!item.key) {
      item.key = item.label?.toLowerCase().replace(/\s+/g, "_") || Math.random().toString(36).slice(2)
    }
    if (item.items) {
      ensureKeys(item.items)
    }
  }
}
