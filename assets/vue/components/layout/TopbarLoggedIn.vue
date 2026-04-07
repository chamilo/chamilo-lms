<template>
  <div class="app-topbar">
    <div class="app-topbar__start">
      <PlatformLogo />
    </div>
    <div class="app-topbar__items">
      <BaseAppLink
        v-if="isTeacher && allowUsersToCreateCourses"
        :title="t('Create course')"
        :to="{ name: 'CourseCreate' }"
        class="item-button"
      >
        <BaseIcon
          icon="courses"
          badge-icon="plus"
          class="item-button__icon text-success"
        />
      </BaseAppLink>
      <button
        v-if="showTourButton"
        type="button"
        class="item-button group text-support-5 hover:text-secondary-hover disabled:cursor-wait disabled:opacity-60"
        :aria-label="t('Tour')"
        :title="t('Tour')"
        :disabled="tourBusy"
        @click="startTourFromTopbar"
      >
        <span
          class="item-button__icon mdi mdi-sign-direction text-[1.8rem] leading-none transition-transform duration-200 group-hover:scale-110"
          aria-hidden="true"
        />
      </button>
      <BaseAppLink
        v-if="!isAnonymous && 'false' !== platformConfigStore.getSetting('ticket.show_link_ticket_notification')"
        :title="t('Ticket')"
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
        :title="t('Inbox')"
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
import { useRoute, useRouter } from "vue-router"
import Avatar from "primevue/avatar"
import Menu from "primevue/menu"
import { storeToRefs } from "pinia"
import { useI18n } from "vue-i18n"
import { usePlatformConfig } from "../../store/platformConfig"
import { useMessageRelUserStore } from "../../store/messageRelUserStore"
import { useCidReqStore } from "../../store/cidReq"
import { useSecurityStore } from "../../store/securityStore"
import { useNotification } from "../../composables/notification"
import { useTopbarTour } from "../../composables/useTopbarTour"
import PlatformLogo from "./PlatformLogo.vue"
import BaseIcon from "../basecomponents/BaseIcon.vue"
import BaseAppLink from "../basecomponents/BaseAppLink.vue"

const { t } = useI18n()
const router = useRouter()
const route = useRoute()

const props = defineProps({
  currentUser: {
    required: true,
    type: Object,
  },
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

const hideLogoutButton = computed(() => {
  return platformConfigStore.getSetting("display.hide_logout_button") === "true"
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
  return {
    menu: {},
    topbar: {},
  }
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
    if (!roleValue) continue

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
    const resolvedRoute = router.resolve({ name: "SurveyPending" })
    if (resolvedRoute?.href) return resolvedRoute.href
  } catch {}
  return "/main/survey/pending.php"
})

const isAnonymous = computed(() => {
  const currentUser = props.currentUser || securityStore.user || {}
  const roles = Array.isArray(currentUser.roles) ? currentUser.roles : []

  if (roles.includes("ROLE_ANONYMOUS")) return true
  if (currentUser.is_anonymous === true || currentUser.isAnonymous === true) return true

  const status = (currentUser.status || "").toString().toUpperCase()

  return status === "ANONYMOUS"
})

const messagingEnabled = computed(() => {
  return platformConfigStore.getSetting("message.allow_message_tool") === "true" && !isAnonymous.value
})

const ticketUrl = computed(() => {
  const searchParams = new URLSearchParams()

  searchParams.append("project_id", "1")
  searchParams.append("cid", cidReqStore.course?.id ?? 0)
  searchParams.append("sid", cidReqStore.session?.id ?? 0)
  searchParams.append("gid", cidReqStore.group?.id ?? 0)

  return "/main/ticket/tickets.php?" + searchParams.toString()
})

function isSettingTrue(keys, defaultValue = false) {
  for (const key of keys) {
    const value = platformConfigStore.getSetting(key)

    if (value === "true") return true
    if (value === "false") return false
  }

  return defaultValue
}

const skillsToolAllowed = computed(() => {
  return isSettingTrue(["skill.allow_skills_tool", "allow_skills_tool"], true)
})

const generalCertificateAllowed = computed(() => {
  return isSettingTrue(["certificate.allow_general_certificate", "allow_general_certificate"], false)
})

const { tourBusy, showTourButton, startTourFromTopbar } = useTopbarTour({
  platformConfigStore,
  route,
  isAnonymous,
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
    const response = await fetch("/main/social/my_skills_report.php?a=has_custom_certificate", {
      method: "GET",
      credentials: "same-origin",
      headers: {
        Accept: "application/json",
      },
    })

    if (!response.ok) {
      throw new Error("Request failed: " + response.status)
    }

    const data = await response.json()

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

  if (isTopbarEnabled("topbar_my_certificates")) {
    items[0].items.push({
      label: t("My certificates"),
      url: "/main/gradebook/my_certificates.php",
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

  items[0].items.push({
    label: t("My services"),
    url: router.resolve({ name: "MyServices" }).href,
  })

  if (!hideLogoutButton.value) {
    items[0].items.push(
      { separator: true },
      {
        label: t("Sign out"),
        url: "/logout",
        icon: "mdi mdi-logout-variant",
      },
    )
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
  if (!messagingEnabled.value) return null
  const unreadCount = messageRelUserStore.countUnread
  return unreadCount > 9 ? "9+" : unreadCount > 0 ? unreadCount.toString() : null
})

if (messagingEnabled.value) {
  messageRelUserStore.findUnreadCount().catch((e) => notification.showErrorNotification(e))
}
</script>
