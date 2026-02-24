<template>
  <h2
    v-text="t('Add friends')"
    class="mr-auto"
  />
  <hr />
  <BaseToolbar>
    <BaseButton
      icon="back"
      type="black"
      @click="goToBack"
    />
  </BaseToolbar>

  <div class="flex flex-row pt-2">
    <div class="w-full">
      <div
        v-text="t('Search')"
        class="text-h4 q-mb-md"
      />

      <VueMultiselect
        :internal-search="false"
        :loading="isLoadingSelect"
        :multiple="true"
        :options="users"
        :placeholder="t('Add')"
        :searchable="true"
        label="username"
        limit="3"
        limit-text="3"
        track-by="id"
        @select="addFriend"
        @search-change="asyncFind"
      />
    </div>
  </div>
</template>
<script setup>
import { onMounted, ref } from "vue"
import { useRoute, useRouter } from "vue-router"
import { useI18n } from "vue-i18n"
import { useNotification } from "../../composables/notification"
import VueMultiselect from "vue-multiselect"
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import userService from "../../services/userService"
import userRelUserService from "../../services/userRelUserService"
import { useSecurityStore } from "../../store/securityStore"

const emit = defineEmits(["friend-request-sent"])

const securityStore = useSecurityStore()
const router = useRouter()
const route = useRoute()
const { t } = useI18n()
const { showSuccessNotification, showErrorNotification } = useNotification()
const users = ref([])
const isLoadingSelect = ref(false)
const searchQuery = ref("")

const asyncFind = (query) => {
  if (query.toString().length < 3) return
  isLoadingSelect.value = true

  userService
    .findBySearchTerm(query)
    .then(({ items }) => (users.value = items))
    .catch((error) => {
      console.error("Error fetching users:", error)
    })
    .finally(() => {
      isLoadingSelect.value = false
    })
}

const extractIdFromPath = (path) => {
  const parts = path.split("/")
  return parts[parts.length - 1]
}

const addFriend = (friend) => {
  isLoadingSelect.value = true

  userRelUserService
    .sendFriendRequest(securityStore.user["@id"], friend["@id"])
    .then(() => {
      showSuccessNotification(t("Friend request sent successfully"))
      emit("friend-request-sent")
      sendNotificationMessage(friend)
    })
    .catch((error) => {
      showErrorNotification(t("Failed to send friend request"))
      console.error("Error adding friend:", error)
    })
    .finally(() => {
      isLoadingSelect.value = false
    })
}

const sendNotificationMessage = async (friend) => {
  const userId = extractIdFromPath(securityStore.user["@id"])
  const targetUserId = extractIdFromPath(friend["@id"])

  const messageData = {
    userId: userId,
    targetUserId: targetUserId,
    action: "send_message",
    subject: t("You have a new friend request"),
    content:
      t("You have received a new friend request. Visit the invitations page to accept or reject the request.") +
      ` <a href="/resources/friends">${t("here")}</a>`,
  }

  try {
    const response = await fetch("/social-network/user-action", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(messageData),
    })
    const result = await response.json()
    if (result.success) {
      showSuccessNotification(t("Notification message sent successfully"))
    } else {
      showErrorNotification(t("Failed to send notification message"))
    }
  } catch (error) {
    showErrorNotification(t("An error occurred while sending the notification message"))
    console.error("Error sending notification message:", error)
  }
}

const goToBack = () => {
  router.push({ name: "UserRelUserList" })
}

// Lifecycle hooks
onMounted(() => {
  if (route.query.search) {
    searchQuery.value = route.query.search
    asyncFind(searchQuery.value)
  }
})
</script>

<style src="vue-multiselect/dist/vue-multiselect.css"></style>
