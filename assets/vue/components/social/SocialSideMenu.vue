<template>
  <BaseCard class="social-side-menu mt-4">
    <div class="text-center text-lg font-bold mb-4">{{ t("Social Network") }}</div>
    <ul class="menu-list">
      <li :class="['menu-item', { 'active': isActive('/social') }]">
        <router-link to="/social">
          <i class="mdi mdi-home" aria-hidden="true"></i>
          {{ t("Home") }}
        </router-link>
      </li>
      <li :class="['menu-item', { 'active': isActive('/resources/messages') }]">
        <router-link to="/resources/messages">
          <i class="mdi mdi-email" aria-hidden="true"></i>
          {{ t("Messages") }}
          <span class="badge badge-warning">{{ unreadMessagesCount }}</span>
        </router-link>
      </li>

      <li :class="['menu-item', { 'active': isActive('/resources/personal_files') }]">
        <router-link :to="{ name: 'PersonalFileList', params: { node: currentNodeId } }">
        <i class="mdi mdi-briefcase"></i>
          {{ t("My files") }}
        </router-link>
      </li>
      <li class="menu-item shared-profile-icon">
        <router-link :to="{ name: 'AccountHome' }">
          <i class="mdi mdi-account-circle" aria-hidden="true"></i>
          {{ t("My Profile") }}
        </router-link>
      </li>
      <li class="menu-item friends-icon">
        <router-link :to="{ name: 'UserRelUserList' }">
          <i class="mdi mdi-handshake" aria-hidden="true"></i>
          {{ t("My Friends") }}
        </router-link>
      </li>
      <li :class="['menu-item', { 'active': isActive('/social', 'promoted') }]">
        <router-link :to="{ path: '/social', query: { filterType: 'promoted' } }">
          <i class="mdi mdi-star" aria-hidden="true"></i>
          {{ t("Promoted Messages") }}
        </router-link>
      </li>
      <li :class="['menu-item', { 'active': isActive('/resources/users/personal_data') }]">
        <router-link to="/resources/users/personal_data">
          <i class="mdi mdi-account" aria-hidden="true"></i>
          {{ t("Personal Data") }}
        </router-link>
      </li>
    </ul>
  </BaseCard>
</template>

<script setup>
import BaseCard from "../basecomponents/BaseCard.vue"
import { useRoute } from 'vue-router'
import { useI18n } from "vue-i18n"
import { useMessageRelUserStore } from "../../store/messageRelUserStore"
import { onMounted, computed, ref, provide, readonly, inject, watchEffect } from "vue"
import { useStore } from "vuex"
import { useSecurityStore } from "../../store/securityStore"

const { t } = useI18n()
const route = useRoute()
const store = useStore()
const securityStore = useSecurityStore()
const currentNodeId = ref(0)
const messageRelUserStore = useMessageRelUserStore()
const unreadMessagesCount = computed(() => messageRelUserStore.countUnread)

const user = inject('social-user')

const isActive = (path, filterType = null) => {
  const pathMatch = route.path.startsWith(path)
  const hasQueryParams = Object.keys(route.query).length > 0
  const filterMatch = filterType ? (route.query.filterType === filterType && hasQueryParams) : !hasQueryParams
  return pathMatch && filterMatch
}

watchEffect(() => {
  try {
    if (user.value && user.value.resourceNode) {
      currentNodeId.value = user.value.resourceNode.id
    } else {
      let currentUser = securityStore.user
      if (currentUser && currentUser.resourceNode) {
        currentNodeId.value = currentUser.resourceNode.id
      }
    }
    messageRelUserStore.findUnreadCount()
  } catch (e) {
    console.error('Error loading user:', e)
  }
})
</script>
