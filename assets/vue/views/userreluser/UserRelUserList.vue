<template>
  <h2
    v-text="t('Friends')"
    class="mr-auto"
  />
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
              <div
                v-if="item.user['@id'] === user['@id']"
                class="friend-info"
              >
                <img
                  :alt="item.friend.username"
                  :src="item.friend.illustrationUrl"
                  class="friend-info__avatar"
                />
                <div
                  class="friend-info__username"
                  v-text="item.friend.username"
                />
              </div>
              <div
                v-else
                class="friend-info"
              >
                <img
                  :alt="item.user.username"
                  :src="item.user.illustrationUrl"
                  class="friend-info__avatar"
                />
                <div
                  class="friend-info__username"
                  v-text="item.user.username"
                />
              </div>
              <div
                v-if="isCurrentUser"
                class="friend-options"
              >
                <span
                  class="friend-options__time"
                  v-text="relativeDatetime(item.createdAt)"
                />
                <BaseButton
                  icon="user-delete"
                  only-icon
                  type="danger"
                  @click="onClickDeleteFriend(item)"
                />
              </div>
            </div>
          </div>
        </template>
      </DataView>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref, watch } from "vue"
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

function buildUserIri() {
  if (user.value?.["@id"]) {
    return user.value["@id"]
  }
  if (user.value?.id) {
    return `/api/users/${user.value.id}`
  }
  return null
}

/**
 * Normalize relation so that:
 * - relation.user is always "me"
 * - relation.friend is always "the other user"
 * Returns null if invalid or self-relation.
 */
function normalizeFriendRelation(rel, meIri) {
  const userIri = rel?.user?.["@id"]
  const friendIri = rel?.friend?.["@id"]

  if (!userIri || !friendIri) {
    return null
  }

  // Ignore broken self-relations
  if (userIri === meIri && friendIri === meIri) {
    return null
  }

  // Forward already (user=me)
  if (userIri === meIri) {
    if (friendIri === meIri) return null
    return rel
  }

  // Backward (friend=me) -> swap
  if (friendIri === meIri) {
    const swapped = {
      ...rel,
      user: rel.friend, // me
      friend: rel.user, // other
    }

    if (swapped.friend?.["@id"] === meIri) return null
    return swapped
  }

  return null
}

function reloadHandler() {
  if (!user.value) {
    console.log("User not defined yet")
    return
  }

  const meIri = buildUserIri()
  if (!meIri) {
    console.warn("Friends list: current user IRI is missing.")
    return
  }

  loadingFriends.value = true
  items.value = []

  Promise.all([
    userRelUserService.findAll({ params: { user: meIri, relationType: 3 } }),
    userRelUserService.findAll({ params: { friend: meIri, relationType: 3 } }),
  ])
    .then(([friendshipResponse, friendshipBackResponse]) => {
      return Promise.all([friendshipResponse.json(), friendshipBackResponse.json()])
    })
    .then(([friendshipJson, friendshipBackJson]) => {
      const merged = [...(friendshipJson?.["hydra:member"] || []), ...(friendshipBackJson?.["hydra:member"] || [])]
      const seen = new Set()
      const normalized = []

      for (const rel of merged) {
        const fixed = normalizeFriendRelation(rel, meIri)
        if (!fixed) continue

        const otherIri = fixed.friend?.["@id"]
        if (!otherIri) continue

        if (seen.has(otherIri)) continue
        seen.add(otherIri)

        normalized.push(fixed)
      }

      items.value = normalized
    })
    .catch((e) => {
      console.error("Error occurred", e)
      notification.showErrorNotification(e)
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

defineExpose({ reloadHandler })
</script>
