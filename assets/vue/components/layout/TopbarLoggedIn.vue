<template>
  <div class="app-topbar">
    <div class="app-topbar__start">
      <PlatformLogo />
    </div>
    <div class="app-topbar__items">
      <BaseAppLink
        v-if="'false' !== platformConfigStore.getSetting('display.show_link_ticket_notification')"
        :url="ticketUrl"
        class="item-button"
      >
        <BaseIcon
          icon="ticket"
          class="item-button__icon"
        />
      </BaseAppLink>

      <BaseAppLink
        :to="{ name: 'MessageList' }"
        class="item-button"
        :class="{ 'item-button--unread': !!btnInboxBadge }"
      >
        <BaseIcon
          icon="inbox"
          class="item-button__icon"
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
        :image="currentUser.illustrationUrl"
        class="user-avatar"
        shape="circle"
        unstyled
        @click="toggleUserMenu"
      />
    </div>
  </div>

  <Menu
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
import PrimeButton from "primevue/button"
import { usePlatformConfig } from "../../store/platformConfig"
import { useCidReq } from "../../composables/cidReq"
import { useMessageRelUserStore } from "../../store/messageRelUserStore"

import { useNotification } from "../../composables/notification"
import { useI18n } from "vue-i18n"
import PlatformLogo from "./PlatformLogo.vue"
import BaseAppLink from "../basecomponents/BaseAppLink.vue"
import BaseIcon from "../basecomponents/BaseIcon.vue"
import { useCidReqStore } from "../../store/cidReq"

const { t } = useI18n()

// eslint-disable-next-line no-undef
const props = defineProps({
  currentUser: {
    required: true,
    type: Object,
  },
})

const router = useRouter()

const platformConfigStore = usePlatformConfig()
const messageRelUserStore = useMessageRelUserStore()
const notification = useNotification()
const cidReqStore = useCidReqStore()

const ticketUrl = computed(() => {
  const searchParms = new URLSearchParams()
  searchParms.append("project_id", "1")
  searchParms.append("cid", cidReqStore.course?.id ?? 0)
  searchParms.append("sid", cidReqStore.session?.id ?? 0)
  searchParms.append("gid", cidReqStore.group?.id ?? 0)

  return "/main/ticket/tickets.php?" + searchParms.toString()
})

const elUserSubmenu = ref(null)
const userSubmenuItems = computed(() => [
  {
    label: props.currentUser.fullName,
    items: [
      {
        label: t("My profile"),
        url: router.resolve({ name: "AccountHome" }).href,
      },
      {
        label: t("My General Certificate"),
        url: "/main/social/my_skills_report.php?a=generate_custom_skill",
      },
      {
        label: t("My skills"),
        url: "/main/social/my_skills_report.php",
      },
      {
        separator: true,
      },
      {
        label: t("Sign out"),
        url: "/logout",
        icon: "mdi mdi-logout-variant",
      },
    ],
  },
])

function toggleUserMenu(event) {
  elUserSubmenu.value.toggle(event)
}

const btnInboxBadge = computed(() => {
  const unreadCount = messageRelUserStore.countUnread
  return unreadCount > 20 ? "9+" : unreadCount > 0 ? unreadCount.toString() : null
})

messageRelUserStore.findUnreadCount().catch((e) => notification.showErrorNotification(e))
</script>
