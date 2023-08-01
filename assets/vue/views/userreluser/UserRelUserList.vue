<template>
  <ButtonToolbar>
    <BaseButton
      :disabled="isLoading"
      :label="t('Add friend')"
      icon="user-add"
      type="black"
      @click="goToAdd"
    />

    <BaseButton
      :disabled="isLoading"
      :label="t('Refresh')"
      icon="refresh"
      type="black"
      @click="reloadHandler"
    />
  </ButtonToolbar>

  <div class="flex flex-col lg:flex-row gap-4">
    <div class="basis-auto lg:basis-3/4">
      <DataView
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

    <div
      v-if="friendRequests.length || waitingRequests.length"
      class="basis-auto lg:basis-1/4"
    >
      <h3 v-t="'Requests'" />

      <div
        v-for="(request, i) in friendRequests"
        :key="i"
        class="flex flex-row gap-2 items-center"
      >
        <BaseUserAvatar :image-url="request.user.illustrationUrl" />

        {{ request.user.username }}

        <BaseButton
          icon="user-add"
          only-icon
          type="black"
          @click="addFriend(request)"
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
          type="info"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { useStore } from "vuex"
import { ref } from "vue"
import axios from "axios"
import ButtonToolbar from "../../components/basecomponents/ButtonToolbar.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseUserAvatar from "../../components/basecomponents/BaseUserAvatar.vue"
import BaseTag from "../../components/basecomponents/BaseTag.vue"
import { useI18n } from "vue-i18n"
import { useRouter } from "vue-router"
import { useConfirm } from "primevue/useconfirm"
import userRelUserService from "../../services/userreluser"
import { useRelativeDatetime } from "../../composables/formatDate"
import { useNotification } from "../../composables/notification"

const store = useStore()
const router = useRouter()
const { t } = useI18n()
const user = store.getters["security/getUser"]
const items = ref([])
const friendRequests = ref([])
const waitingRequests = ref([])

const notification = useNotification()

const friendRequestFilter = {
  friend: user.id,
  relationType: 10, // friend request
}

const waitingFilter = {
  user: user.id,
  relationType: 10,
}

const friendFilter = {
  user: user.id,
  relationType: 3, // friend status
}

const friendBackFilter = {
  friend: user.id,
  relationType: 3, // friend status
}

function addFriend(friend) {
  // Change from request to friend
  axios
    .put(friend["@id"], {
      relationType: 3,
    })
    .then((response) => {
      console.log(response)
      reloadHandler()
    })
    .catch(function (error) {
      console.log(error)
    })
}

function reloadHandler() {
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

  userRelUserService
    .findAll({
      params: friendRequestFilter,
    })
    .then((response) => response.json())
    .then((json) => (friendRequests.value = json["hydra:member"]))
    .catch((e) => notification.showErrorNotification(e))

  userRelUserService
    .findAll({
      params: waitingFilter,
    })
    .then((response) => response.json())
    .then((json) => (waitingRequests.value = json["hydra:member"]))
    .catch((e) => notification.showErrorNotification(e))
}

reloadHandler()

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
