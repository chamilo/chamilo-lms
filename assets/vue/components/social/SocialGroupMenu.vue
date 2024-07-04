<template>
  <BaseCard class="social-side-menu mt-4">
    <template #header>
      <div class="px-4 py-2 -mb-2 bg-gray-15">
        <h2 class="text-h5">{{ t("Social Group") }}</h2>
      </div>
    </template>
    <hr class="-mt-2 mb-4 -mx-4" />
    <ul
      v-if="groupInfo.isMember"
      class="menu-list"
    >
      <li class="menu-item">
        <BaseAppLink to="/social">
          <i
            class="mdi mdi-home"
            aria-hidden="true"
          ></i>
          {{ t("Home") }}
        </BaseAppLink>
      </li>
      <li class="menu-item">
        <BaseAppLink :to="{ name: '', params: { group_id: groupInfo.id } }">
          <i
            class="mdi mdi-account-multiple-outline"
            aria-hidden="true"
          ></i>
          {{ t("Waiting list") }}
        </BaseAppLink>
      </li>
      <li class="menu-item">
        <BaseAppLink :to="{ name: 'UserGroupInvite', params: { group_id: groupInfo.id } }">
          <i
            class="mdi mdi-account-plus"
            aria-hidden="true"
          ></i>
          {{ t("Invite friends") }}
        </BaseAppLink>
      </li>
      <li
        v-if="groupInfo.isAllowedToLeave"
        class="menu-item"
      >
        <button @click="leaveGroup">
          <i
            class="mdi mdi-exit-to-app"
            aria-hidden="true"
          ></i>
          {{ t("Leave group") }}
        </button>
      </li>
    </ul>
    <ul v-else>
      <li class="menu-item">
        <BaseAppLink to="/social">
          <i
            class="mdi mdi-home"
            aria-hidden="true"
          ></i>
          {{ t("Home") }}
        </BaseAppLink>
      </li>
    </ul>
  </BaseCard>
</template>

<script setup>
import BaseCard from "../basecomponents/BaseCard.vue"
import { useRoute, useRouter } from "vue-router"
import { useI18n } from "vue-i18n"
import { useSecurityStore } from "../../store/securityStore"
import axios from "axios"
import { useNotification } from "../../composables/notification"
import { useSocialInfo } from "../../composables/useSocialInfo"
import BaseAppLink from "../basecomponents/BaseAppLink.vue"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const securityStore = useSecurityStore()
const notification = useNotification()

const { user, groupInfo, isGroup, loadGroup, isLoading } = useSocialInfo()
const leaveGroup = async () => {
  try {
    const response = await axios.post("/social-network/group-action", {
      userId: user.value.id,
      groupId: groupInfo.value.id,
      action: "leave",
    })
    if (response.data.success) {
      notification.showSuccessNotification(t("You have left the group successfully"))
      router.push("/social")
    }
  } catch (error) {
    console.error("Error leaving the group:", error)
  }
}
const isActive = (path, filterType = null) => {
  const pathMatch = route.path.startsWith(path)
  const hasQueryParams = Object.keys(route.query).length > 0
  const filterMatch = filterType ? route.query.filterType === filterType && hasQueryParams : !hasQueryParams
  return pathMatch && filterMatch
}
</script>
