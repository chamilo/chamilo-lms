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
        v-if="!isAnonymous && showTicketLink"
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
import Avatar from "primevue/avatar"
import Menu from "primevue/menu"
import PlatformLogo from "./PlatformLogo.vue"
import BaseIcon from "../basecomponents/BaseIcon.vue"
import BaseAppLink from "../basecomponents/BaseAppLink.vue"
import { useTopbarLoggedIn } from "../../composables/useTopbarLoggedIn"
import { useTopbarTour } from "../../composables/useTopbarTour"
import { useI18n } from "vue-i18n"

const props = defineProps({
  currentUser: {
    required: true,
    type: Object,
  },
})

const { t } = useI18n()

const {
  loginUrl,
  elUserSubmenu,
  isTeacher,
  allowUsersToCreateCourses,
  showTicketLink,
  isAnonymous,
  messagingEnabled,
  ticketUrl,
  btnInboxBadge,
  userSubmenuItems,
  toggleUserMenu,
} = useTopbarLoggedIn(props)

const { tourBusy, showTourButton, startTourFromTopbar } = useTopbarTour({ isAnonymous })
</script>
