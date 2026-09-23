<template>
  <div class="flex flex-col md:flex-row gap-4">
    <div class="md:basis-1/4 lg:basis-1/6 2xl:basis-1/8 flex flex-col">
      <UserProfileCard />
      <BaseCard
        class="mt-4"
        plain
      >
        <template #header>
          <div class="px-4 py-3 bg-gray-200 border-b border-gray-300">
            <h3 class="text-xl font-semibold">{{ t("Requests") }}</h3>
          </div>
        </template>
        <div class="px-4 py-3">
          <UserRelUserRequestsList
            v-if="isCurrentUser"
            ref="requestList"
            @relations-changed="reloadHandler"
          />
        </div>
      </BaseCard>
    </div>
    <div class="md:basis-3/4 lg:basis-5/6 2xl:basis-7/8">
      <router-view v-slot="{ Component }">
        <component
          :is="Component"
          ref="friendsListView"
          @relations-changed="reloadHandler"
        />
      </router-view>
    </div>
  </div>
</template>

<script setup>
import UserProfileCard from "../social/UserProfileCard.vue"
import { nextTick, onBeforeUnmount, onMounted, provide, ref, watch } from "vue"
import { useSocialInfo } from "../../composables/useSocialInfo"
import UserRelUserRequestsList from "./UserRelUserRequestsList.vue"
import BaseCard from "../../components/basecomponents/BaseCard.vue"
import { useI18n } from "vue-i18n"
import { useRoute } from "vue-router"

const { t } = useI18n()
const route = useRoute()
const { user, isCurrentUser, groupInfo, isGroup, loadUser } = useSocialInfo()

provide("social-user", user)
provide("is-current-user", isCurrentUser)
provide("group-info", groupInfo)
provide("is-group", isGroup)

const requestList = ref(null)
const friendsListView = ref(null)
const RELATION_SYNC_INTERVAL_MS = 10000
let relationSyncTimer = null
let refreshingRelations = false
let refreshQueued = false

const reloadHandler = async (options = {}) => {
  if (refreshingRelations) {
    refreshQueued = true
    return
  }

  refreshingRelations = true
  let silent = options?.silent === true

  try {
    do {
      refreshQueued = false
      const requestsRefresh = requestList.value?.loadRequests?.({ silent })
      await nextTick()
      const friendsRefresh = friendsListView.value?.reloadHandler?.({ silent })
      await Promise.allSettled([requestsRefresh, friendsRefresh].filter(Boolean))
      silent = true
    } while (refreshQueued)
  } finally {
    refreshingRelations = false
  }
}

const refreshWhenVisible = () => {
  if (document.visibilityState === "visible") {
    reloadHandler({ silent: true })
  }
}

const refreshOnFocus = () => {
  reloadHandler({ silent: true })
}

onMounted(async () => {
  await loadUser()
  await reloadHandler()
  window.addEventListener("focus", refreshOnFocus)
  document.addEventListener("visibilitychange", refreshWhenVisible)
  relationSyncTimer = window.setInterval(() => {
    if (document.visibilityState === "visible") {
      reloadHandler({ silent: true })
    }
  }, RELATION_SYNC_INTERVAL_MS)
})

onBeforeUnmount(() => {
  window.removeEventListener("focus", refreshOnFocus)
  document.removeEventListener("visibilitychange", refreshWhenVisible)
  if (relationSyncTimer) {
    window.clearInterval(relationSyncTimer)
    relationSyncTimer = null
  }
})

watch(
  () => route.fullPath,
  async () => {
    await nextTick()
    reloadHandler({ silent: true })
  },
)

watch(user, (newVal) => {
  if (newVal && newVal.id) {
    reloadHandler({ silent: true })
  }
})
</script>
