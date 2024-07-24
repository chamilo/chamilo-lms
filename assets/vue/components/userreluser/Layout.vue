<template>
  <div class="flex flex-col md:flex-row gap-4">
    <div class="md:basis-1/4 lg:basis-1/6 2xl:basis-1/8 flex flex-col">
      <UserProfileCard />
      <BaseCard plain class="mt-4">
        <template #header>
          <div class="px-4 py-3 bg-gray-200 border-b border-gray-300">
            <h3 class="text-xl font-semibold">{{ t('Requests') }}</h3>
          </div>
        </template>
        <div class="px-4 py-3">
          <UserRelUserRequestsList
            v-if="isCurrentUser"
            ref="requestList"
            @accept-friend="reloadHandler"
          />
        </div>
      </BaseCard>
    </div>
    <div class="md:basis-3/4 lg:basis-5/6 2xl:basis-7/8">
      <router-view @friend-request-sent="reloadRequestsList"></router-view>
    </div>
  </div>
</template>

<script setup>
import UserProfileCard from "../social/UserProfileCard.vue"
import { nextTick, onMounted, provide, ref } from "vue"
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
const items = ref([])
const loadingFriends = ref(true)
const notification = useNotification()

const friendFilter = {
  user: user.id,
  relationType: 3, // friend status
}

const friendBackFilter = {
  friend: user.id,
  relationType: 3, // friend status
}

const reloadHandler = async () => {
  loadingFriends.value = true
  items.value = []

  try {
    const [friendshipResponse, friendshipBackResponse] = await Promise.all([
      userRelUserService.findAll({ params: friendFilter }),
      userRelUserService.findAll({ params: friendBackFilter }),
    ])
    const [friendshipJson, friendshipBackJson] = await Promise.all([
      friendshipResponse.json(),
      friendshipBackResponse.json(),
    ])
    items.value.push(...friendshipJson["hydra:member"], ...friendshipBackJson["hydra:member"])
  } catch (e) {
    notification.showErrorNotification(e)
  } finally {
    loadingFriends.value = false
    if (requestList.value) {
      await nextTick()
      requestList.value.loadRequests()
    }
  }
}

const reloadRequestsList = () => {
  if (requestList.value) {
    requestList.value.loadRequests();
  }
}

onMounted(async () => {
  await loadUser()
  await reloadHandler()
})
</script>
