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
            @accept-friend="reloadHandler"
            @reject-friend="reloadHandler"
          />
        </div>
      </BaseCard>
    </div>
    <div class="md:basis-3/4 lg:basis-5/6 2xl:basis-7/8">
      <router-view
        ref="friendsListView"
        @friend-request-sent="reloadRequestsList"
      />
    </div>
  </div>
</template>

<script setup>
import UserProfileCard from "../social/UserProfileCard.vue"
import { nextTick, onMounted, provide, ref, watch } from "vue"
import { useSocialInfo } from "../../composables/useSocialInfo"
import UserRelUserRequestsList from "./UserRelUserRequestsList.vue"
import BaseCard from "../../components/basecomponents/BaseCard.vue"
import { useNotification } from "../../composables/notification"
import userRelUserService from "../../services/userreluser"
import { useI18n } from "vue-i18n"

const { t } = useI18n()
const { user, isCurrentUser, groupInfo, isGroup, loadUser } = useSocialInfo()

provide("social-user", user)
provide("is-current-user", isCurrentUser)
provide("group-info", groupInfo)
provide("is-group", isGroup)

const requestList = ref(null)
const friendsListView = ref(null)
const items = ref([])
const loadingFriends = ref(true)
const notification = useNotification()

const friendFilter = {
  user: user.id,
  relationType: 3,
}

const friendBackFilter = {
  friend: user.id,
  relationType: 3,
}

const reloadHandler = async () => {
  loadingFriends.value = true
  items.value = []

  try {
    const [resp1, resp2] = await Promise.all([
      userRelUserService.findAll({ params: friendFilter }),
      userRelUserService.findAll({ params: friendBackFilter }),
    ])

    const [json1, json2] = await Promise.all([resp1.json(), resp2.json()])
    const seen = new Set()
    items.value = [...json1["hydra:member"], ...json2["hydra:member"]].filter((item) => {
      const otherId = item.user["@id"] === user["@id"] ? item.friend["@id"] : item.user["@id"]
      if (seen.has(otherId)) return false
      seen.add(otherId)
      return true
    })
  } catch (e) {
    notification.showErrorNotification(e)
  } finally {
    loadingFriends.value = false
    requestList.value?.loadRequests()
    await nextTick()
    friendsListView.value?.reloadHandler?.()
  }
}

const reloadRequestsList = () => {
  requestList.value?.loadRequests()
}

onMounted(async () => {
  await loadUser()
  await reloadHandler()
})

watch(user, (newVal) => {
  if (newVal && newVal.id) {
    reloadHandler()
  }
})
</script>
