import { computed, onMounted, ref } from "vue"
import { useRoute, useRouter } from "vue-router"
import { storeToRefs } from "pinia"
import { useI18n } from "vue-i18n"
import { usePlatformConfig } from "../store/platformConfig"
import { useMessageRelUserStore } from "../store/messageRelUserStore"
import { useCidReqStore } from "../store/cidReq"
import { useSecurityStore } from "../store/securityStore"
import { useNotification } from "./notification"
import baseService from "../services/baseService"

const ROLE_MAP = {
  ROLE_ADMIN: "ADMIN",
  ROLE_SESSION_MANAGER: "SESSIONADMIN",
  ROLE_TEACHER: "COURSEMANAGER",
  ROLE_STUDENT_BOSS: "STUDENT_BOSS",
  ROLE_DRH: "DRH",
  ROLE_INVITEE: "INVITEE",
  ROLE_STUDENT: "STUDENT",
}

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

const KNOWN_TOPBAR_TABS = [
  "topbar_certificate",
  "topbar_my_certificates",
  "topbar_my_custom_certificate",
  "topbar_skills",
]

function safeParseJson(value, warnLabel) {
  if (!value || "string" !== typeof value) {
    return null
  }

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

function normalizeTopbarKeys(topbar) {
  const out = topbar && "object" === typeof topbar ? { ...topbar } : {}

  if (Object.prototype.hasOwnProperty.call(out, "topbar_certificate")) {
    const enabled = out.topbar_certificate === true

    if (!Object.prototype.hasOwnProperty.call(out, "topbar_my_certificates")) {
      out.topbar_my_certificates = enabled
    }

    if (!Object.prototype.hasOwnProperty.call(out, "topbar_my_custom_certificate")) {
      out.topbar_my_custom_certificate = enabled
    }
  } else {
    const hasNewKeys =
      Object.prototype.hasOwnProperty.call(out, "topbar_my_certificates") ||
      Object.prototype.hasOwnProperty.call(out, "topbar_my_custom_certificate")

    if (hasNewKeys && !Object.prototype.hasOwnProperty.call(out, "topbar_certificate")) {
      out.topbar_certificate = out.topbar_my_certificates === true || out.topbar_my_custom_certificate === true
    }
  }

  return out
}

function normalizeConfigObject(obj) {
  const cfg = makeEmptyConfig()

  if (!obj || "object" !== typeof obj) {
    return cfg
  }

  if (obj.menu && "object" === typeof obj.menu) {
    cfg.menu = { ...obj.menu }
  }

  if (obj.topbar && "object" === typeof obj.topbar) {
    cfg.topbar = normalizeTopbarKeys(obj.topbar)
  }

  return cfg
}

function configFromLegacyList(list) {
  const cfg = makeEmptyConfig()

  for (const k of KNOWN_MENU_TABS) {
    cfg.menu[k] = false
  }

  for (const k of KNOWN_TOPBAR_TABS) {
    cfg.topbar[k] = false
  }

  const arr = Array.isArray(list) ? list : []
  for (const key of arr) {
    if ("string" !== typeof key) {
      continue
    }

    if (KNOWN_MENU_TABS.includes(key)) {
      cfg.menu[key] = true

      continue
    }

    if ("topbar_certificate" === key) {
      cfg.topbar.topbar_certificate = true
      cfg.topbar.topbar_my_certificates = true
      cfg.topbar.topbar_my_custom_certificate = true

      continue
    }

    if (KNOWN_TOPBAR_TABS.includes(key)) {
      cfg.topbar[key] = true
    }
  }

  cfg.topbar = normalizeTopbarKeys(cfg.topbar)

  return cfg
}

function mergeConfig(baseCfg, overrideCfg) {
  const out = makeEmptyConfig()

  out.menu = { ...(baseCfg?.menu || {}) }
  out.topbar = { ...(baseCfg?.topbar || {}) }

  if (overrideCfg?.menu && "object" === typeof overrideCfg.menu) {
    for (const [k, v] of Object.entries(overrideCfg.menu)) {
      out.menu[k] = v
    }
  }

  if (overrideCfg?.topbar && "object" === typeof overrideCfg.topbar) {
    const normalized = normalizeTopbarKeys(overrideCfg.topbar)

    for (const [k, v] of Object.entries(normalized)) {
      out.topbar[k] = v
    }
  }

  out.topbar = normalizeTopbarKeys(out.topbar)

  return out
}

function resolveDisplayTabsConfig(platformConfigStore, securityStore) {
  const showTabsRaw = platformConfigStore.getSetting("display.show_tabs")
  let defaultCfg = makeEmptyConfig()

  if (Array.isArray(showTabsRaw)) {
    defaultCfg = configFromLegacyList(showTabsRaw)
  } else if ("string" === typeof showTabsRaw && showTabsRaw.trim() !== "") {
    const parsed = safeParseJson(showTabsRaw, "[Topbar] Invalid JSON in display.show_tabs")

    if (Array.isArray(parsed)) {
      defaultCfg = configFromLegacyList(parsed)
    } else {
      defaultCfg = normalizeConfigObject(parsed)
    }
  }

  const perRoleRaw = platformConfigStore.getSetting("display.show_tabs_per_role") || ""
  const perRole = safeParseJson(perRoleRaw, "[Topbar] Invalid JSON in display.show_tabs_per_role") || {}

  const roles = securityStore.user?.roles || []
  for (const role of roles) {
    const mappedRole = ROLE_MAP[role] || role
    const roleValue = perRole?.[mappedRole]

    if (!roleValue) {
      continue
    }

    if (Array.isArray(roleValue)) {
      return configFromLegacyList(roleValue)
    }

    const roleCfg = normalizeConfigObject(roleValue)

    return mergeConfig(defaultCfg, roleCfg)
  }

  return defaultCfg
}

function isSettingEnabled(platformConfigStore, key) {
  return normalizeBooleanFlag(platformConfigStore.getSetting(key))
}

function normalizeBooleanFlag(value) {
  if (typeof value === "boolean") {
    return value
  }

  if (typeof value === "string") {
    return value === "true"
  }

  return false
}

function normalizeExternalLogoutBehaviour(data) {
  if (!data || data.active !== true) {
    return null
  }

  const logoutUrl = typeof data.logoutUrl === "string" && data.logoutUrl.trim() ? data.logoutUrl.trim() : "/logout"

  return {
    logoutUrl,
    tooltip: typeof data.tooltip === "string" ? data.tooltip : "",
    showAlert: data.showAlert === true,
    alertText: typeof data.alertText === "string" ? data.alertText : "",
    disabled: data.disabled === true || logoutUrl === "#",
  }
}

async function fetchExternalLogoutBehaviour() {
  try {
    const response = await fetch("/plugin/ExtAuthChamiloLogoutButtonBehaviour/logout-config.php", {
      credentials: "same-origin",
      headers: {
        Accept: "application/json",
      },
    })

    if (!response.ok) {
      return null
    }

    return normalizeExternalLogoutBehaviour(await response.json())
  } catch (e) {
    console.warn("[ExtAuthChamiloLogoutButtonBehaviour] Unable to load logout behavior", e)

    return null
  }
}

export function useTopbarLoggedIn(props) {
  const { t } = useI18n()
  const router = useRouter()
  const route = useRoute()

  const platformConfigStore = usePlatformConfig()
  const messageRelUserStore = useMessageRelUserStore()
  const notification = useNotification()
  const cidReqStore = useCidReqStore()
  const securityStore = useSecurityStore()
  const { isTeacher, isAdmin, isHRM } = storeToRefs(securityStore)

  const loginUrl = "/login"
  const elUserSubmenu = ref(null)
  const externalLogoutBehaviour = ref(null)

  const allowUsersToCreateCourses = computed(() =>
    isSettingEnabled(platformConfigStore, "workflows.allow_users_to_create_courses"),
  )

  const canCreateCourseFromTopbar = computed(() => isAdmin.value || (isTeacher.value && allowUsersToCreateCourses.value))

  const hideLogoutButton = computed(() => isSettingEnabled(platformConfigStore, "display.hide_logout_button"))

  const showTicketLink = computed(
    () => platformConfigStore.getSetting("ticket.show_link_ticket_notification") !== "false",
  )

  const displayTabs = computed(() => resolveDisplayTabsConfig(platformConfigStore, securityStore))

  function isTopbarEnabled(key) {
    return displayTabs.value?.topbar?.[key] === true
  }

  const showPendingSurveys = computed(() => isSettingEnabled(platformConfigStore, "survey.show_pending_survey_in_menu"))

  const pendingSurveysUrl = computed(() => {
    try {
      const resolvedRoute = router.resolve({ name: "SurveyPending" })

      if (resolvedRoute?.href) {
        return resolvedRoute.href
      }
    } catch {}
    return "/main/survey/pending.php"
  })

  const myServicesUrl = computed(() => {
    try {
      const resolvedRoute = router.resolve({ name: "MyServices" })

      if (resolvedRoute?.href) {
        return resolvedRoute.href
      }
    } catch {}
    return "/my-services"
  })

  const isAnonymous = computed(() => {
    const currentUser = props.currentUser || securityStore.user || {}
    const roles = Array.isArray(currentUser.roles) ? currentUser.roles : []

    if (roles.includes("ROLE_ANONYMOUS")) {
      return true
    }

    if (currentUser.is_anonymous === true || currentUser.isAnonymous === true) {
      return true
    }

    const status = (currentUser.status || "").toString().toUpperCase()

    return status === "ANONYMOUS"
  })

  const messagingEnabled = computed(
    () => isSettingEnabled(platformConfigStore, "message.allow_message_tool") && !isAnonymous.value,
  )

  const ticketUrl = computed(() => {
    const searchParams = new URLSearchParams()

    searchParams.append("project_id", "1")
    searchParams.append("cid", cidReqStore.course?.id ?? 0)
    searchParams.append("sid", cidReqStore.session?.id ?? 0)
    searchParams.append("gid", cidReqStore.group?.id ?? 0)

    return "/main/ticket/tickets.php?" + searchParams.toString()
  })

  const buyCoursesConfig = computed(() => platformConfigStore.plugins?.buycourses || {})

  const showMyServicesLink = computed(() => normalizeBooleanFlag(buyCoursesConfig.value?.enabled))

  const justificationMenu = ref({
    enabled: false,
    label: "My justifications",
    url: "/plugin/Justification/upload.php",
  })

  const showMyJustificationsLink = computed(() => !isAnonymous.value && justificationMenu.value.enabled === true)

  async function fetchJustificationMenu() {
    if (isAnonymous.value) {
      justificationMenu.value.enabled = false

      return
    }

    try {
      const data = await baseService.get("/plugin/Justification/user_menu.php")

      justificationMenu.value = {
        enabled: data?.enabled === true,
        label: data?.label || "My justifications",
        url: data?.url || "/plugin/Justification/upload.php",
      }
    } catch (e) {
      console.warn("[Topbar] Failed to load Justification user menu", e)
      justificationMenu.value.enabled = false
    }
  }

  const skillsToolAllowed = computed(() => isSettingEnabled(platformConfigStore, "skill.allow_skills_tool"))

  const certificatesSearchAllowed = computed(() =>
    isSettingEnabled(platformConfigStore, "certificate.allow_certificates_search"),
  )

  const skillsManagementAllowed = computed(() =>
    isSettingEnabled(platformConfigStore, "skill.allow_hr_skills_management"),
  )

  const canManageSkills = computed(
    () => skillsToolAllowed.value && skillsManagementAllowed.value && (isAdmin.value || isHRM.value),
  )

  const generalCertificateAllowed = computed(() =>
    isSettingEnabled(platformConfigStore, "certificate.allow_general_certificate"),
  )

  const hasCustomCertificate = ref(null)
  const isFetchingCustomCertificate = ref(false)

  async function fetchHasCustomCertificate() {
    if (isFetchingCustomCertificate.value) {
      return
    }

    if (hasCustomCertificate.value !== null) {
      return
    }

    isFetchingCustomCertificate.value = true

    try {
      const data = await baseService.get("/main/social/my_skills_report.php", { a: "has_custom_certificate" })

      hasCustomCertificate.value = !!(
        data &&
        (data.hasCustomCertificate === true || data.has_custom_certificate === true || data.exists === true)
      )
    } catch (e) {
      console.warn("[Topbar] Failed to check custom certificate existence", e)
      hasCustomCertificate.value = false
    } finally {
      isFetchingCustomCertificate.value = false
    }
  }

  function runExternalLogoutBehaviour() {
    const behaviour = externalLogoutBehaviour.value

    if (!behaviour) {
      window.location.href = "/logout"

      return
    }

    if (behaviour.showAlert && behaviour.alertText) {
      window.alert(behaviour.alertText)
    }

    if (!behaviour.disabled) {
      window.location.href = behaviour.logoutUrl || "/logout"
    }
  }

  function buildLogoutMenuItem() {
    const behaviour = externalLogoutBehaviour.value

    if (!behaviour) {
      return {
        label: t("Sign out"),
        url: "/logout",
        icon: "mdi mdi-logout-variant",
      }
    }

    return {
      label: t("Sign out"),
      icon: behaviour.disabled ? "mdi mdi-logout-variant opacity-60" : "mdi mdi-logout-variant",
      command: runExternalLogoutBehaviour,
    }
  }

  const userSubmenuItems = computed(() => {
    const items = [
      {
        label: props.currentUser?.fullName || t("My profile"),
        items: [
          {
            label: t("My profile"),
            url: router.resolve({ name: "AccountHome" }).href,
          },
        ],
      },
    ]

    if (showMyJustificationsLink.value) {
      items[0].items.push({
        label: justificationMenu.value.label || t("My justifications"),
        url: justificationMenu.value.url || "/plugin/Justification/upload.php",
        icon: "mdi mdi-file-document-check-outline",
      })
    }

    if (showPendingSurveys.value) {
      items[0].items.push({
        label: t("Pending surveys"),
        url: pendingSurveysUrl.value,
      })
    }

    if (!isAnonymous.value && showMyServicesLink.value) {
      items[0].items.push({
        label: t("My services"),
        url: myServicesUrl.value,
      })
    }

    if (isTopbarEnabled("topbar_my_certificates")) {
      items[0].items.push({
        label: t("My certificates"),
        url: "/main/gradebook/my_certificates.php",
      })
    }

    if (certificatesSearchAllowed.value) {
      items[0].items.push({
        label: t("Search certificates"),
        url: "/main/gradebook/search.php",
      })
    }

    if (isTopbarEnabled("topbar_my_custom_certificate") && generalCertificateAllowed.value) {
      if (hasCustomCertificate.value === true) {
        items[0].items.push({
          label: t("My custom certificate"),
          url: "/main/social/my_skills_report.php?a=generate_custom_skill",
        })
      }
    }

    if (isTopbarEnabled("topbar_skills") && skillsToolAllowed.value) {
      items[0].items.push({
        label: t("My skills"),
        url: "/main/social/my_skills_report.php",
      })
    }

    if (canManageSkills.value) {
      items[0].items.push({
        label: t("Manage skills"),
        url: "/main/skills/skill_list.php",
      })
    }

    if (!hideLogoutButton.value) {
      items[0].items.push({ separator: true }, buildLogoutMenuItem())
    }

    return items
  })

  function toggleUserMenu(event) {
    elUserSubmenu.value?.toggle(event)

    const shouldCheck =
      !isAnonymous.value &&
      generalCertificateAllowed.value &&
      isTopbarEnabled("topbar_my_custom_certificate") &&
      hasCustomCertificate.value === null

    if (shouldCheck) {
      fetchHasCustomCertificate()
    }
  }

  const btnInboxBadge = computed(() => {
    if (!messagingEnabled.value) {
      return null
    }

    const unreadCount = messageRelUserStore.countUnread

    return unreadCount > 9 ? "9+" : unreadCount > 0 ? unreadCount.toString() : null
  })

  onMounted(async () => {
    fetchJustificationMenu()

    if (!isAnonymous.value) {
      externalLogoutBehaviour.value = await fetchExternalLogoutBehaviour()
    }
  })

  if (messagingEnabled.value) {
    messageRelUserStore.findUnreadCount().catch((e) => notification.showErrorNotification(e))
  }

  return {
    loginUrl,
    elUserSubmenu,
    canCreateCourseFromTopbar,
    isTeacher,
    allowUsersToCreateCourses,
    showTicketLink,
    isAnonymous,
    messagingEnabled,
    ticketUrl,
    btnInboxBadge,
    userSubmenuItems,
    toggleUserMenu,
  }
}
