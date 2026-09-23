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
        class="ms-auto"
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
      <BaseUserAvatar
        :alt="t('Picture')"
        :image-url="request.user.illustrationUrl"
      />

      {{ request.user.username }}

      <BaseButton
        class="ms-auto"
        icon="alert"
        only-icon
        type="danger"
        :label="
          t(
            'By accepting this invitation, you will become linked to the requesting user in a way that will make some or all of your profile data available for reading by the requesting user. Please make sure you do not share too much by checking your profile with users you already trust.',
          )
        "
      />

      <BaseButton
        :label="t('Accept invitation')"
        icon="user-add"
        only-icon
        type="black"
        @click="acceptFriendRequest(request)"
      />
      <BaseButton
        :label="t('Reject invitation')"
        class="ms-2"
        icon="user-delete"
        only-icon
        type="danger"
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
        class="ms-auto"
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
import relUserService from "../../services/userRelUserService"
import { useNotification } from "../../composables/notification"
import { useI18n } from "vue-i18n"

const emit = defineEmits(["relations-changed"])

const { t } = useI18n()

const securityStore = useSecurityStore()
const notification = useNotification()

const friendRequests = ref([])
const waitingRequests = ref([])
const loading = ref(true)

const loadRequests = async (options = {}) => {
  const silent = options?.silent === true
  const userId = Number(securityStore.user?.id || 0)

  if (!userId) {
    friendRequests.value = []
    waitingRequests.value = []
    loading.value = false
    return
  }

  if (!silent) {
    loading.value = true
    friendRequests.value = []
    waitingRequests.value = []
  }

  try {
    const [sentRequestsResponse, waitingRequestsResponse] = await Promise.all([
      userRelUserService.findAll({ params: { friend: userId, relationType: 10 } }),
      userRelUserService.findAll({ params: { user: userId, relationType: 10 } }),
    ])
    const [sentRequestsJson, waitingRequestsJson] = await Promise.all([
      sentRequestsResponse.json(),
      waitingRequestsResponse.json(),
    ])

    friendRequests.value = sentRequestsJson["hydra:member"]
    waitingRequests.value = waitingRequestsJson["hydra:member"]
  } catch (e) {
    notification.showErrorNotification(e)
  } finally {
    // The first refresh can be silent, but the initial skeleton must still finish.
    loading.value = false
  }
}

async function acceptFriendRequest(request) {
  try {
    await relUserService.update(request["@id"], { relationType: 3 })
    emit("relations-changed")
    notification.showSuccessNotification(t("Friend added successfully"))
  } catch (e) {
    notification.showErrorNotification(e)
  }
}

async function rejectFriendRequest(request) {
  try {
    await relUserService.remove(request["@id"])
    emit("relations-changed")
    notification.showSuccessNotification(t("Friend request rejected"))
  } catch (e) {
    notification.showErrorNotification(e)
  }
}

defineExpose({ loadRequests })
</script>
