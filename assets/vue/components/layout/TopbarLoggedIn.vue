<template>
  <div class="app-topbar">
    <div class="app-topbar__start">
      <img
        :src="headerLogo"
        alt="Chamilo LMS"
      />
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
        :class="{ 'item-button--unread': btnInboxBadge }"
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
        @click="toogleUserMenu"
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

import headerLogoPath from "../../../../assets/css/themes/chamilo/images/header-logo.svg"
import { useNotification } from "../../composables/notification"
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

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
const userSubmenuItems = [
  {
    label: props.currentUser.fullName,
    items: [
      {
        label: t("My General Certificate"),
        url: "/main/social/my_skills_report.php?a=generate_custom_skill",
      },
      {
        label: t("My Skills"),
        url: "/main/social/my_skills_report.php",
      },
      {
        label: t("Settings"),
        to: { name: "AccountHome" },
      },
    ],
  },
]

function toogleUserMenu(event) {
  elUserSubmenu.value.toggle(event)
}

const headerLogo = headerLogoPath

const btnInboxBadge = computed(() => (messageRelUserStore.countUnread > 9 ? "9+" : messageRelUserStore.countUnread))

messageRelUserStore.findUnreadCount().catch((e) => notification.showErrorNotification(e))
</script>
