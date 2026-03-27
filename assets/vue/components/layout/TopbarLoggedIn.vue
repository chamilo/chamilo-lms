<template>
  <div class="app-topbar">
    <div class="app-topbar__start">
      <PlatformLogo />
    </div>
    <div class="app-topbar__items">
      <BaseAppLink
        v-if="isTeacher && allowUsersToCreateCourses"
        :to="{ name: 'CourseCreate' }"
        class="item-button"
      >
        <BaseIcon
          icon="courses"
          badge-icon="plus"
          :tooltip="t('Create course')"
          class="item-button__icon text-success"
        />
      </BaseAppLink>
      <BaseAppLink
        v-if="!isAnonymous && 'false' !== platformConfigStore.getSetting('ticket.show_link_ticket_notification')"
        :url="ticketUrl"
        class="item-button"
      >
        <BaseIcon
          class="item-button__icon"
          icon="ticket"
        />
      </BaseAppLink>
      <BaseAppLink
        v-if="!isAnonymous && messagingEnabled"
        :class="{ 'item-button--unread': !!btnInboxBadge }"
        :to="{ name: 'MessageList' }"
        class="item-button"
      >
        <BaseIcon
          class="item-button__icon"
          icon="inbox"
        />
        <span
          v-if="btnInboxBadge"
          class="item-button__badge"
          v-text="btnInboxBadge"
        />
      </BaseAppLink>
    </div>
    <div class="app-topbar__end">
      <Avatar
        v-if="!isAnonymous"
        :image="currentUser?.illustrationUrl"
        class="user-avatar"
        shape="circle"
        unstyled
        @click="toggleUserMenu"
      />
      <BaseAppLink
        v-else
        class="item-button"
        :url="loginUrl"
        :to="null"
        tabindex="0"
      >
        <BaseIcon
          class="item-button__icon"
          icon="login"
        />
      </BaseAppLink>
    </div>
  </div>

  <Menu
    v-if="!isAnonymous"
    id="user-submenu"
    ref="elUserSubmenu"
    :model="userSubmenuItems"
    :popup="true"
    class="app-topbar__user-submenu"
  />
</template>

<script setup>
import { computed, ref } from "vue"
import { useRouter } from "vue-router"

import Avatar from "primevue/avatar"
import Menu from "primevue/menu"
import { usePlatformConfig } from "../../store/platformConfig"
import { useMessageRelUserStore } from "../../store/messageRelUserStore"

import { useNotification } from "../../composables/notification"
import { useI18n } from "vue-i18n"
import PlatformLogo from "./PlatformLogo.vue"
import BaseIcon from "../basecomponents/BaseIcon.vue"
import BaseAppLink from "../basecomponents/BaseAppLink.vue"
import { useCidReqStore } from "../../store/cidReq"
import { useSecurityStore } from "../../store/securityStore"
import { storeToRefs } from "pinia"

const { t } = useI18n()
const router = useRouter()

const props = defineProps({
  currentUser: { required: true, type: Object },
})

const platformConfigStore = usePlatformConfig()
const messageRelUserStore = useMessageRelUserStore()
const notification = useNotification()
const cidReqStore = useCidReqStore()
const securityStore = useSecurityStore()
const { isTeacher } = storeToRefs(securityStore)

const loginUrl = "/login"
const elUserSubmenu = ref(null)
const allowUsersToCreateCourses = computed(() => {
  return platformConfigStore.getSetting("workflows.allow_users_to_create_courses") === "true"
})

/**
 * Role mapping used by display.show_tabs_per_role.
 * This matches the legacy constants used across the platform.
 */
const ROLE_MAP = {
  ROLE_ADMIN: "ADMIN",
  ROLE_SESSION_MANAGER: "SESSIONADMIN",
  ROLE_TEACHER: "COURSEMANAGER",
  ROLE_STUDENT_BOSS: "STUDENT_BOSS",
  ROLE_DRH: "DRH",
  ROLE_INVITEE: "INVITEE",
  ROLE_STUDENT: "STUDENT",
}

/**
 * We keep these lists to support legacy array semantics:
 * - "Full replacement": only listed tabs are enabled, known tabs are disabled.
 * Unknown keys won't have any effect in the UI.
 */
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

/**
 * Backward compatibility for topbar keys:
 * - If legacy "topbar_certificate" exists, map it to the 2 new keys when missing.
 * - Keep a "topbar_certificate" alias when only new keys exist (so old checks still work).
 */
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
    // Build legacy alias from new keys (best-effort)
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
  if (!obj || "object" !== typeof obj) return cfg

  if (obj.menu && "object" === typeof obj.menu) cfg.menu = { ...obj.menu }
  if (obj.topbar && "object" === typeof obj.topbar) cfg.topbar = normalizeTopbarKeys(obj.topbar)

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

    if (KNOWN_MENU_TABS.includes(key)) {
      cfg.menu[key] = true
      continue
    }

    // Legacy alias: enable both new entries when legacy key is present
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

  // Ensure mapping/alias consistency even for legacy lists
  cfg.topbar = normalizeTopbarKeys(cfg.topbar)

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
    const normalized = normalizeTopbarKeys(overrideCfg.topbar)
    for (const [k, v] of Object.entries(normalized)) out.topbar[k] = v
  }

  out.topbar = normalizeTopbarKeys(out.topbar)

  return out
}

/**
 * Resolve the effective display tabs config by combining:
 * - display.show_tabs (default)
 * - display.show_tabs_per_role (role overrides)
 *
 * This keeps the same semantics as sidebarMenu.js so that menu + topbar are consistent.
 */
function resolveDisplayTabsConfig(platformConfigStore, securityStore) {
  // display.show_tabs (default)
  const showTabsRaw = platformConfigStore.getSetting("display.show_tabs")
  let defaultCfg = makeEmptyConfig()

  if (Array.isArray(showTabsRaw)) {
    // Very old installations may still provide an array
    defaultCfg = configFromLegacyList(showTabsRaw)
  } else if ("string" === typeof showTabsRaw && showTabsRaw.trim() !== "") {
    const parsed = safeParseJson(showTabsRaw, "[Topbar] Invalid JSON in display.show_tabs")
    if (Array.isArray(parsed)) {
      defaultCfg = configFromLegacyList(parsed)
    } else {
      defaultCfg = normalizeConfigObject(parsed)
    }
  }

  // display.show_tabs_per_role (overrides)
  const perRoleRaw = platformConfigStore.getSetting("display.show_tabs_per_role") || ""
  const perRole = safeParseJson(perRoleRaw, "[Topbar] Invalid JSON in display.show_tabs_per_role") || {}

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

const displayTabs = computed(() => resolveDisplayTabsConfig(platformConfigStore, securityStore))

function isTopbarEnabled(key) {
  return displayTabs.value?.topbar?.[key] === true
}

const showPendingSurveys = computed(() => {
  return platformConfigStore.getSetting("survey.show_pending_survey_in_menu") === "true"
})

const pendingSurveysUrl = computed(() => {
  try {
    const r = router.resolve({ name: "SurveyPending" })
    if (r?.href) return r.href
  } catch {}
  return "/main/survey/pending.php"
})

const isAnonymous = computed(() => {
  const u = props.currentUser || securityStore.user || {}
  const roles = Array.isArray(u.roles) ? u.roles : []
  if (roles.includes("ROLE_ANONYMOUS")) return true
  if (u.is_anonymous === true || u.isAnonymous === true) return true
  const st = (u.status || "").toString().toUpperCase()
  return st === "ANONYMOUS"
})

const messagingEnabled = computed(() => {
  return platformConfigStore.getSetting("message.allow_message_tool") === "true" && !isAnonymous.value
})

const ticketUrl = computed(() => {
  const searchParms = new URLSearchParams()
  searchParms.append("project_id", "1")
  searchParms.append("cid", cidReqStore.course?.id ?? 0)
  searchParms.append("sid", cidReqStore.session?.id ?? 0)
  searchParms.append("gid", cidReqStore.group?.id ?? 0)

  return "/main/ticket/tickets.php?" + searchParms.toString()
})

function isSettingTrue(keys, defaultValue = false) {
  for (const k of keys) {
    const v = platformConfigStore.getSetting(k)
    if (v === "true") return true
    if (v === "false") return false
  }
  return defaultValue
}

/**
 * Settings that must now have an effect on the topbar menu.
 * We support both namespaced and legacy keys to stay compatible.
 */
const skillsToolAllowed = computed(() => {
  // Legacy installs might still expose "allow_skills_tool" (without prefix) to the frontend.
  return isSettingTrue(["skill.allow_skills_tool", "allow_skills_tool"], true)
})

const generalCertificateAllowed = computed(() => {
  // Default should be false on new installs (per decision), so we fallback to false.
  return isSettingTrue(["certificate.allow_general_certificate", "allow_general_certificate"], false)
})

/**
 * "My custom certificate" must only appear if it actually exists.
 * We check existence through a lightweight JSON action in my_skills_report.php.
 */
const hasCustomCertificate = ref(null)
const isFetchingCustomCertificate = ref(false)

async function fetchHasCustomCertificate() {
  if (isFetchingCustomCertificate.value) return
  if (hasCustomCertificate.value !== null) return

  isFetchingCustomCertificate.value = true
  try {
    const r = await fetch("/main/social/my_skills_report.php?a=has_custom_certificate", {
      method: "GET",
      credentials: "same-origin",
      headers: {
        Accept: "application/json",
      },
    })

    if (!r.ok) {
      throw new Error("Request failed: " + r.status)
    }

    const data = await r.json()
    hasCustomCertificate.value = !!(
      data &&
      (data.hasCustomCertificate === true || data.has_custom_certificate === true || data.exists === true)
    )
  } catch (e) {
    console.warn("[Topbar] Failed to check custom certificate existence", e)
    // Fail-closed: do not show the entry if we cannot confirm it exists.
    hasCustomCertificate.value = false
  } finally {
    isFetchingCustomCertificate.value = false
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

  if (showPendingSurveys.value) {
    items[0].items.push({
      label: t("Pending surveys"),
      url: pendingSurveysUrl.value,
    })
  }

  // My certificates (gradebook)
  if (isTopbarEnabled("topbar_my_certificates")) {
    items[0].items.push({
      label: t("My certificates"),
      url: "/main/gradebook/my_certificates.php",
    })
  }

  // My custom certificate (PDF)
  if (isTopbarEnabled("topbar_my_custom_certificate") && generalCertificateAllowed.value) {
    if (hasCustomCertificate.value === true) {
      items[0].items.push({
        label: t("My custom certificate"),
        url: "/main/social/my_skills_report.php?a=generate_custom_skill",
      })
    }
  }

  // My skills
  if (isTopbarEnabled("topbar_skills") && skillsToolAllowed.value) {
    items[0].items.push({
      label: t("My skills"),
      url: "/main/social/my_skills_report.php",
    })
  }

  items[0].items.push({ separator: true }, { label: t("Sign out"), url: "/logout", icon: "mdi mdi-logout-variant" })
  return items
})

function toggleUserMenu(event) {
  // Always open immediately (PrimeVue needs the click event to position the popup).
  elUserSubmenu.value?.toggle(event)

  // Fetch in background only when needed.
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
  if (!messagingEnabled.value) return null
  const unreadCount = messageRelUserStore.countUnread
  return unreadCount > 20 ? "20+" : unreadCount > 0 ? unreadCount.toString() : null
})

if (messagingEnabled.value) {
  messageRelUserStore.findUnreadCount().catch((e) => notification.showErrorNotification(e))
}
</script>
