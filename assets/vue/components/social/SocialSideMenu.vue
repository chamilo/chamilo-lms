<template>
  <BaseCard class="social-side-menu mt-4">
    <template #header>
      <div class="px-4 py-2 -mb-2 bg-gray-15">
        <h2 class="text-h5">{{ t("Social network") }}</h2>
      </div>
    </template>
    <hr class="-mt-2 mb-4 -mx-4" />
    <ul
      v-if="isCurrentUser"
      class="menu-list"
    >
      <li :class="['menu-item', { active: isActive('/social') }]">
        <BaseAppLink to="/social">
          <i
            aria-hidden="true"
            class="mdi mdi-home"
          ></i>
          {{ t("Home") }}
        </BaseAppLink>
      </li>
      <li :class="['menu-item', { active: isActive('/resources/messages') }]">
        <BaseAppLink to="/resources/messages">
          <i
            aria-hidden="true"
            class="mdi mdi-email"
          ></i>
          {{ t("Messages") }}
          <span
            v-if="unreadMessagesCount > 0"
            class="badge badge-warning"
            >{{ unreadMessagesCount }}</span
          >
        </BaseAppLink>
      </li>
      <li :class="['menu-item', { active: isActive('/resources/friends/invitations') }]">
        <BaseAppLink :to="{ name: 'Invitations' }">
          <i
            aria-hidden="true"
            class="mdi mdi-mailbox"
          ></i>
          {{ t("Invitations") }}
          <span
            v-if="invitationsCount > 0"
            class="badge badge-warning"
            >{{ invitationsCount }}</span
          >
        </BaseAppLink>
      </li>
      <li :class="['menu-item', { active: isActive('/resources/friends') }]">
        <BaseAppLink :to="{ name: 'UserRelUserList' }">
          <i
            aria-hidden="true"
            class="mdi mdi-handshake"
          ></i>
          {{ t("My friends") }}
        </BaseAppLink>
      </li>
      <li :class="['menu-item', { active: isActive(groupLink) }]">
        <a
          v-if="isValidGlobalForumsCourse"
          :href="groupLink"
          rel="noopener noreferrer"
        >
          <i
            aria-hidden="true"
            class="mdi mdi-group"
          ></i>
          {{ t("Social groups") }}
        </a>
        <BaseAppLink
          v-else
          :to="groupLink"
        >
          <i
            aria-hidden="true"
            class="mdi mdi-group"
          ></i>
          {{ t("Social groups") }}
        </BaseAppLink>
      </li>
      <li :class="['menu-item', { active: isActive('/social/search') }]">
        <BaseAppLink to="/social/search">
          <i
            aria-hidden="true"
            class="mdi mdi-magnify"
          ></i>
          {{ t("Search") }}
        </BaseAppLink>
      </li>
      <li :class="['menu-item', { active: isActive('/resources/personal_files') }]">
        <BaseAppLink :to="{ name: 'PersonalFileList', params: { node: currentNodeId } }">
          <i class="mdi mdi-briefcase"></i>
          {{ t("My files") }}
        </BaseAppLink>
      </li>
      <li :class="['menu-item', { active: isActive('/resources/users/personal_data') }]">
        <BaseAppLink to="/resources/users/personal_data">
          <i
            aria-hidden="true"
            class="mdi mdi-account"
          ></i>
          {{ t("Personal data") }}
        </BaseAppLink>
      </li>
      <li :class="['menu-item', { active: isActive('/social', 'promoted') }]">
        <BaseAppLink :to="{ path: '/social', query: { filterType: 'promoted' } }">
          <i
            aria-hidden="true"
            class="mdi mdi-star"
          ></i>
          {{ t("Promoted messages") }}
        </BaseAppLink>
      </li>
    </ul>
    <ul
      v-else
      class="menu-list"
    >
      <li class="menu-item">
        <BaseAppLink to="/social">
          <i
            aria-hidden="true"
            class="mdi mdi-home"
          ></i>
          {{ t("Home") }}
        </BaseAppLink>
      </li>
      <li class="menu-item">
        <a
          class="ajax"
          href="/main/inc/ajax/user_manager.ajax.php?a=get_user_popup&user_id={{user.id}}"
          rel="noopener noreferrer"
        >
          <i
            aria-hidden="true"
            class="mdi mdi-email"
          ></i>
          {{ t("Send message") }}
        </a>
      </li>
    </ul>
  </BaseCard>
</template>

<script setup>
import BaseCard from "../basecomponents/BaseCard.vue"
import { useRoute } from "vue-router"
import { useI18n } from "vue-i18n"
import { useMessageRelUserStore } from "../../store/messageRelUserStore"
import { computed, inject, onMounted, ref, watchEffect } from "vue"
import { useSecurityStore } from "../../store/securityStore"
import axios from "axios"
import { usePlatformConfig } from "../../store/platformConfig"
import BaseAppLink from "../basecomponents/BaseAppLink.vue"

const { t } = useI18n()
const route = useRoute()
const securityStore = useSecurityStore()
const currentNodeId = ref(0)
const messageRelUserStore = useMessageRelUserStore()
const unreadMessagesCount = computed(() => messageRelUserStore.countUnread)
const invitationsCount = ref(0)

const user = inject("social-user")
const isCurrentUser = inject("is-current-user")
const groupLink = ref({ name: "UserGroupShow" })
const platformConfigStore = usePlatformConfig()
const globalForumsCourse = computed(() => platformConfigStore.getSetting("forum.global_forums_course_id"))
const isValidGlobalForumsCourse = computed(() => {
  const courseId = globalForumsCourse.value
  return courseId !== null && courseId !== undefined && courseId > 0
})
const getGroupLink = async () => {
  try {
    const response = await axios.get("/social-network/get-forum-link")
    if (isValidGlobalForumsCourse.value) {
      groupLink.value = response.data.go_to
    } else {
      groupLink.value = { name: "UserGroupList" }
    }
  } catch (error) {
    console.error("Error fetching forum link:", error)
    groupLink.value = { name: "UserGroupList" }
  }
}

const fetchInvitationsCount = async (userId) => {
  if (!userId) return
  try {
    const { data } = await axios.get(`/social-network/invitations/count/${userId}`)
    invitationsCount.value = data.totalInvitationsCount
  } catch (error) {
    console.error("Error fetching invitations count:", error)
  }
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
    if (user.value && user.value.id) {
      fetchInvitationsCount(user.value.id)
    }
  } catch (e) {
    console.error("Error loading user:", e)
  }
})

const isActive = (path, filterType = null) => {
  if (path === "/resources/friends/invitations" || path === "/social/search") {
    return route.path === path
  }

  const pathMatch = route.path.startsWith(path)
  const hasQueryParams = Object.keys(route.query).length > 0
  const filterMatch = filterType ? route.query.filterType === filterType && hasQueryParams : !hasQueryParams
  return (
    pathMatch &&
    filterMatch &&
    !route.path.startsWith("/resources/friends/invitations") &&
    !route.path.startsWith("/social/search")
  )
}

onMounted(async () => {
  await getGroupLink()
  if (user.value && user.value.id) {
    await fetchInvitationsCount(user.value.id)
  }
})
</script>
