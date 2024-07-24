<template>
  <div
    v-if="loading"
    class="space-y-4"
  >
    <div
      v-for="i in 6"
      :key="i"
      class="flex flex-row gap-2 items-center"
    >
      <Skeleton
        shape="circle"
        size="2.5rem"
      />
      <Skeleton width="6rem" />
      <Skeleton
        class="ml-auto"
        size="2.5rem"
      />
    </div>
  </div>

  <div
    v-else
    class="space-y-4"
  >
    <div
      v-for="(request, i) in friendRequests"
      :key="i"
      class="flex flex-row gap-2 items-center"
    >
      <BaseUserAvatar :image-url="request.user.illustrationUrl" :alt="t('Picture')" />

      {{ request.user.username }}

      <BaseButton
        class="ml-auto"
        icon="user-add"
        only-icon
        type="black"
        :tooltip="t('Accept invitation')"
        @click="acceptFriendRequest(request)"
      />
      <BaseButton
        class="ml-2"
        icon="user-delete"
        only-icon
        type="danger"
        :tooltip="t('Reject invitation')"
        @click="rejectFriendRequest(request)"
      />
    </div>

    <div
      v-for="(request, i) in waitingRequests"
      :key="i"
      class="flex flex-row gap-2 items-center"
    >
      <BaseUserAvatar :image-url="request.friend.illustrationUrl" />

      {{ request.friend.username }}

      <BaseTag
        :label="t('Waiting')"
        class="ml-auto"
        type="info"
      />
    </div>
  </div>
</template>

<script setup>
import BaseTag from "../basecomponents/BaseTag.vue"
import BaseButton from "../basecomponents/BaseButton.vue"
import Skeleton from "primevue/skeleton"
import BaseUserAvatar from "../basecomponents/BaseUserAvatar.vue"
import { ref } from "vue"
import { useSecurityStore } from "../../store/securityStore"
import userRelUserService from "../../services/userreluser"
import { useNotification } from "../../composables/notification"
import axios from "axios"
import { useI18n } from "vue-i18n"

const emit = defineEmits(["accept-friend"])

const { t } = useI18n()

const securityStore = useSecurityStore()
const notification = useNotification()

const friendRequests = ref([])
const waitingRequests = ref([])

const friendRequestFilter = {
  friend: securityStore.user.id,
  relationType: 10, // friend request
}
const waitingFilter = {
  user: securityStore.user.id,
  relationType: 10,
}

const loading = ref(true)

const loadRequests = () => {
  loading.value = true

  friendRequests.value = []
  waitingRequests.value = []

  Promise.all([
    userRelUserService.findAll({ params: friendRequestFilter }),
    userRelUserService.findAll({ params: waitingFilter }),
  ])
    .then(([sentRequestsResponse, waitingRequestsRespose]) =>
      Promise.all([sentRequestsResponse.json(), waitingRequestsRespose.json()])
    )
    .then(([sentRequestsJson, waitingRequestsJson]) => {
      friendRequests.value = sentRequestsJson["hydra:member"]
      waitingRequests.value = waitingRequestsJson["hydra:member"]
    })
    .catch((e) => notification.showErrorNotification(e))
    .finally(() => (loading.value = false))
}

function acceptFriendRequest(request) {
  axios
    .put(request["@id"], { relationType: 3 })
    .then(() => {
      emit("accept-friend", request)
      notification.showSuccessNotification(t("Friend added successfully"))
      loadRequests()
    })
    .catch((e) => notification.showErrorNotification(e))
}

function rejectFriendRequest(request) {
  axios
    .delete(request["@id"])
    .then(() => {
      notification.showSuccessNotification(t("Friend request rejected"))
      loadRequests()
    })
    .catch((e) => notification.showErrorNotification(e))
}

defineExpose({ loadRequests })
</script>
