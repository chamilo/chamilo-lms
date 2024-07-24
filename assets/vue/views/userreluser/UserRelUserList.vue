<template>
  <h2 v-t="'Friends'" class="mr-auto" />
  <hr />
  <BaseToolbar v-if="isCurrentUser">
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
  </BaseToolbar>

  <div class="flex flex-col gap-4">
    <div class="w-full">
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
          <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <div
              v-for="(item, index) in slotProps.items"
              :key="index"
              class="friend-list__block"
            >
              <div v-if="item.user['@id'] === user['@id']" class="friend-info">
                <img
                  :alt="item.friend.username"
                  :src="item.friend.illustrationUrl"
                  class="friend-info__avatar"
                />
                <div class="friend-info__username" v-text="item.friend.username" />
              </div>
              <div v-else class="friend-info">
                <img
                  :alt="item.user.username"
                  :src="item.user.illustrationUrl"
                  class="friend-info__avatar"
                />
                <div class="friend-info__username" v-text="item.user.username" />
              </div>
              <div class="friend-options" v-if="isCurrentUser">
                <span class="friend-options__time" v-text="relativeDatetime(item.createdAt)" />
                <BaseButton icon="user-delete" only-icon type="danger" @click="onClickDeleteFriend(item)" />
              </div>
            </div>
          </div>
        </template>
      </DataView>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from "vue"
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import Skeleton from "primevue/skeleton"
import DataView from "primevue/dataview"
import { useI18n } from "vue-i18n"
import { useRouter } from "vue-router"
import { useConfirm } from "primevue/useconfirm"
import userRelUserService from "../../services/userreluser"
import { useFormatDate } from "../../composables/formatDate"
import { useNotification } from "../../composables/notification"
import { useSocialInfo } from "../../composables/useSocialInfo"

const { user, isCurrentUser } = useSocialInfo()
const { t } = useI18n()
const items = ref([])
const loadingFriends = ref(true)
const notification = useNotification()
const { relativeDatetime } = useFormatDate()
const router = useRouter()
const confirm = useConfirm()

const requestList = ref()

function reloadHandler() {
  if (!user.value) {
    console.log('User not defined yet');
    return;
  }

  loadingFriends.value = true
  items.value = []

  Promise.all([
    userRelUserService.findAll({ params: { user: user.value.id, relationType: 3 } }),
    userRelUserService.findAll({ params: { friend: user.value.id, relationType: 3 } }),
  ])
    .then(([friendshipResponse, friendshipBackResponse]) => {
      return Promise.all([friendshipResponse.json(), friendshipBackResponse.json()])
    })
    .then(([friendshipJson, friendshipBackJson]) => {
      const friendsSet = new Set()
      items.value = [...friendshipJson["hydra:member"], ...friendshipBackJson["hydra:member"]]
        .filter(friend => {
          const friendId = friend.user['@id'] === user.value['@id'] ? friend.friend['@id'] : friend.user['@id']
          if (friendsSet.has(friendId)) {
            return false
          } else {
            friendsSet.add(friendId)
            return true
          }
        })
    })
    .catch((e) => {
      console.error('Error occurred', e);
      notification.showErrorNotification(e);
    })
    .finally(() => {
      loadingFriends.value = false
      if (requestList.value) {
        requestList.value.loadRequests()
      }
    })
}

watch(user, (newValue) => {
  if (newValue && newValue.id) {
    reloadHandler()
  }
})

onMounted(() => {
  if (user.value && user.value.id) {
    reloadHandler()
  }
})

const goToAdd = () => {
  router.push({ name: "UserRelUserAdd" })
}

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
