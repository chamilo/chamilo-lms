<template>
  <div class="app-topbar">
    <div class="app-topbar__start">
      <PlatformLogo />
    </div>
    <div class="app-topbar__items">
      <PrimeButton
        v-if="'false' !== platformConfigStore.getSetting('display.show_link_ticket_notification')"
        :icon="chamiloIconToClass['ticket']"
        class="item-button"
        icon-class="item-button__icon"
        link
        unstyled
        @click="btnTicketsOnClick"
      />

      <PrimeButton
        :badge="btnInboxBadge"
        :class="{ 'item-button--unread': !!btnInboxBadge }"
        :icon="chamiloIconToClass['inbox']"
        badge-class="item-button__badge"
        class="item-button"
        icon-class="item-button__icon"
        link
        unstyled
        @click="btnInboxOnClick"
      />
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
import { chamiloIconToClass } from "../basecomponents/ChamiloIcons"
import { useCidReq } from "../../composables/cidReq"
import { useMessageRelUserStore } from "../../store/messageRelUserStore"

import { useNotification } from "../../composables/notification"
import { useI18n } from "vue-i18n"
import PlatformLogo from "./PlatformLogo.vue"

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

const btnTicketsOnClick = () => {
  const { cid, sid, gid } = useCidReq()

  window.location = window.location.origin + `/main/ticket/tickets.php?project_id=1&cid=${cid}&sid=${sid}&gid=${gid}`
}

const btnInboxOnClick = async () => await router.push({ name: "MessageList" })

const elUserSubmenu = ref(null)
const userSubmenuItems = computed(() => [
  {
    label: props.currentUser.fullName,
    items: [
      {
        label: t("My profile"),
        command: async () => await router.push({ name: "AccountHome" }),
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
