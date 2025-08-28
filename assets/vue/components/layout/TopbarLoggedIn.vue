<template>
  <div class="app-topbar">
    <div class="app-topbar__start">
      <PlatformLogo />
    </div>
    <div class="app-topbar__items">
      <BaseAppLink
        v-if="!isAnonymous && 'false' !== platformConfigStore.getSetting('display.show_link_ticket_notification')"
        :url="ticketUrl"
        class="item-button"
      >
        <BaseIcon class="item-button__icon" icon="ticket" />
      </BaseAppLink>

      <BaseAppLink
        v-if="!isAnonymous && messagingEnabled"
        :class="{ 'item-button--unread': !!btnInboxBadge }"
        :to="{ name: 'MessageList' }"
        class="item-button"
      >
        <BaseIcon class="item-button__icon" icon="inbox" />
        <span v-if="btnInboxBadge" class="item-button__badge" v-text="btnInboxBadge" />
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
        <BaseIcon class="item-button__icon" icon="login" />
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

const loginUrl = "/login"
const elUserSubmenu = ref(null)
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

  const tabs = platformConfigStore.getSetting("platform.show_tabs") || ""
  if (tabs.indexOf("topbar_certificate") > -1) {
    items[0].items.push({
      label: t("My General Certificate"),
      url: "/main/social/my_skills_report.php?a=generate_custom_skill",
    })
  }
  if (tabs.indexOf("topbar_skills") > -1) {
    items[0].items.push({
      label: t("My skills"),
      url: "/main/social/my_skills_report.php",
    })
  }

  items[0].items.push(
    { separator: true },
    { label: t("Sign out"), url: "/logout", icon: "mdi mdi-logout-variant" },
  )
  return items
})

function toggleUserMenu(event) {
  elUserSubmenu.value?.toggle(event)
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
