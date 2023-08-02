<template>
  <ButtonToolbar>
    <BaseButton
      :disabled="loadingFriends"
      :label="t('Add friend')"
      icon="user-add"
      type="black"
      @click="goToAdd"
    />

    <BaseButton
      :disabled="loadingFriends"
      :label="t('Refresh')"
      icon="refresh"
      type="black"
      @click="reloadHandler"
    />
  </ButtonToolbar>

  <div class="flex flex-col lg:flex-row gap-4">
    <div class="basis-auto lg:basis-3/4">
      <div
        v-if="loadingFriends"
        class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3"
      >
        <Skeleton
          v-for="i in 6"
          :key="i"
          height="10.5rem"
        />
      </div>

      <DataView
        v-else
        :value="items"
        class="friend-list"
        layout="grid"
      >
        <template #grid="slotProps">
          <div class="friend-list__block">
            <div
              v-if="slotProps.data.user['@id'] === user['@id']"
              class="friend-info"
            >
              <img
                :alt="slotProps.data.friend.username"
                :src="slotProps.data.friend.illustrationUrl"
                class="friend-info__avatar"
              />
              <div
                class="friend-info__username"
                v-text="slotProps.data.friend.username"
              />
            </div>
            <div
              v-else
              class="friend-info"
            >
              <img
                :alt="slotProps.data.user.username"
                :src="slotProps.data.user.illustrationUrl"
                class="friend-info__avatar"
              />
              <div
                class="friend-info__username"
                v-text="slotProps.data.user.username"
              />
            </div>

            <div class="friend-options">
              <span
                class="friend-options__time"
                v-text="useRelativeDatetime(slotProps.data.createdAt)"
              />
              <BaseButton
                icon="user-delete"
                only-icon
                type="danger"
                @click="onClickDeleteFriend(slotProps.data)"
              />
            </div>
          </div>
        </template>
      </DataView>
    </div>

    <div class="basis-auto lg:basis-1/4">
      <UserRelUserRequestsList
        ref="requestList"
        @accept-friend="reloadHandler"
      />
    </div>
  </div>
</template>

<script setup>
import { useStore } from "vuex"
import { onMounted, ref } from "vue"
import ButtonToolbar from "../../components/basecomponents/ButtonToolbar.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import Skeleton from "primevue/skeleton"
import { useI18n } from "vue-i18n"
import { useRouter } from "vue-router"
import { useConfirm } from "primevue/useconfirm"
import userRelUserService from "../../services/userreluser"
import { useRelativeDatetime } from "../../composables/formatDate"
import { useNotification } from "../../composables/notification"
import UserRelUserRequestsList from "../../components/userreluser/UserRelUserRequestsList.vue"

const store = useStore()
const router = useRouter()
const { t } = useI18n()
const user = store.getters["security/getUser"]
const items = ref([])
const friendRequests = ref([])
const waitingRequests = ref([])

const notification = useNotification()

const loadingFriends = ref(true)

const friendFilter = {
  user: user.id,
  relationType: 3, // friend status
}

const friendBackFilter = {
  friend: user.id,
  relationType: 3, // friend status
}

const requestList = ref()

function reloadHandler() {
  loadingFriends.value = true

  items.value = []
  friendRequests.value = []
  waitingRequests.value = []

  Promise.all([
    userRelUserService.findAll({
      params: friendFilter,
    }),
    userRelUserService.findAll({
      params: friendBackFilter,
    }),
  ])
    .then(([friendshipResponse, friendshipBackResponse]) =>
      Promise.all([friendshipResponse.json(), friendshipBackResponse.json()]),
    )
    .then(([friendshipJson, friendshipBackJson]) =>
      items.value.push(...friendshipJson["hydra:member"], ...friendshipBackJson["hydra:member"]),
    )
    .catch((e) => notification.showErrorNotification(e))
    .finally(() => (loadingFriends.value = false))

  requestList.value.loadRequests()
}

onMounted(() => {
  reloadHandler()
})

const goToAdd = () => {
  router.push({ name: "UserRelUserAdd" })
}

const confirm = useConfirm()

function onClickDeleteFriend(friendship) {
  confirm.require({
    icon: "mdi mdi-alert-outline",
    header: t("Confirmation"),
    message: t("Are you sure to delete the friendship?"),
    accept: async () => {
      await userRelUserService.del(friendship)

      reloadHandler()
    },
  })
}
</script>
