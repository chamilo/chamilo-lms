<template>
  <h2
    v-t="'Search users for friends'"
    class="mr-auto"
  />
  <hr />
  <div class="user-rel-user-search">
    <BaseToolbar>
      <BaseButton
        :label="t('Go to friends list')"
        icon="back"
        type="black"
        @click="goToBack"
      />
    </BaseToolbar>
    <div class="search-area mb-4">
      <input
        v-model="searchQuery"
        class="search-input mr-3"
        placeholder="Search Users"
        type="text"
      />
      <BaseButton
        :label="t('Search')"
        class="search-button"
        icon="search"
        type="button"
        @click="executeSearch"
      />
    </div>
    <div
      v-if="!loadingResults"
      class="results-list"
    >
      <div
        v-for="(user, index) in foundUsers"
        :key="index"
        class="user-card"
      >
        <div class="user-avatar">
          <img
            :alt="`${user.username}'s avatar`"
            :src="user.illustrationUrl || defaultAvatar"
            class="avatar-image"
          />
        </div>
        <div class="user-details">
          <div class="username">{{ user.username }}</div>
          <div class="user-actions">
            <button
              class="action-button invite-button"
              @click="addFriend(user)"
            >
              Send invitation
            </button>
            <a
              :href="`/main/inc/ajax/user_manager.ajax.php?a=get_user_popup&user_id=${user.id}`"
              class="action-button message-button ajax"
              data-title="Send message"
              title="Send message"
            >
              <i
                aria-hidden="true"
                class="fa fa-envelope"
              ></i>
              Send message
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { onMounted, ref } from "vue"
import { useRouter } from "vue-router"
import { useStore } from "vuex"
import { useI18n } from "vue-i18n"
import { useNotification } from "../../composables/notification"
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import { ENTRYPOINT } from "../../config/entrypoint"
import axios from "axios"

const store = useStore()
const router = useRouter()
const { t } = useI18n()
const { showSuccessNotification, showErrorNotification } = useNotification()
const isLoadingSelect = ref(false)
const loadingResults = ref(false)
const user = store.getters["security/getUser"]
const foundUsers = ref([])
const friendsList = ref([])
const searchQuery = ref("")
const executeSearch = async () => {
  if (searchQuery.value.trim().length > 0) {
    await router.push({ name: "UserRelUserSearch", query: { search: searchQuery.value } })
    await fetchFriendsList()
    await asyncFind(searchQuery.value)
  } else {
    showErrorNotification(t("Please enter a search query"))
  }
}
const isFriend = (user) => {
  return friendsList.value.some((friend) => friend.id === user.id)
}

async function fetchFriendsList() {
  try {
    const response = await axios.get(`${ENTRYPOINT}user_rel_users`, {
      params: { user: user.id, relationType: [3, 10] },
    })
    friendsList.value = response.data["hydra:member"].map((friendship) => friendship.friend.id).concat(user.id)
  } catch (error) {
    showErrorNotification(t("Error fetching friends list"))
    console.error("Error fetching friends list:", error)
  }
}

const asyncFind = async (query) => {
  if (query.length < 3) return
  isLoadingSelect.value = true
  try {
    const { data } = await axios.get(`${ENTRYPOINT}users`, { params: { username: query } })
    foundUsers.value = data["hydra:member"].filter((foundUser) => !friendsList.value.includes(foundUser.id))
  } catch (error) {
    showErrorNotification(t("Error fetching users"))
  } finally {
    isLoadingSelect.value = false
  }
}
const addFriend = async (friend) => {
  try {
    await axios.post(`${ENTRYPOINT}user_rel_users`, {
      user: user["@id"],
      friend: friend["@id"],
      relationType: 10,
    })
    showSuccessNotification(t("Friend request sent successfully"))
    await fetchFriendsList()
    const searchQuery = router.currentRoute.value.query.search
    if (searchQuery) {
      await asyncFind(searchQuery)
    }
  } catch (error) {
    showErrorNotification(t("Failed to send friend request"))
    console.error("Error adding friend:", error)
  }
}
const goToBack = () => {
  router.push({ name: "UserRelUserList" })
}
onMounted(async () => {
  const urlSearchQuery = router.currentRoute.value.query.search
  if (urlSearchQuery && searchQuery.value.trim().length === 0) {
    searchQuery.value = urlSearchQuery
    await fetchFriendsList()
    await asyncFind(searchQuery.value)
  }
})
</script>
