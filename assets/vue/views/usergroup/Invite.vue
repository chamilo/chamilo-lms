<template>
  <div class="invite-friends-container invite-friends">
    <div class="invite-friends-header">
      <h2>{{ t("Invite Friends to Group") }}</h2>
    </div>
    <div class="invite-friends-body">
      <div class="friends-list">
        <div class="list-header">
          <h3>{{ t("Available Friends") }}</h3>
        </div>
        <div class="list-content">
          <div
            v-for="friend in availableFriends"
            :key="friend.id"
            class="friend-entry"
          >
            <div class="friend-info">
              <img
                :src="friend.avatar"
                alt="avatar"
                class="friend-avatar"
              />
              <span class="friend-name">{{ friend.name }}</span>
            </div>
            <button
              class="invite-btn"
              @click="selectFriend(friend)"
            >
              +
            </button>
          </div>
        </div>
      </div>
      <div class="selected-friends-list">
        <div class="list-header">
          <h3>{{ t("Selected Friends") }}</h3>
        </div>
        <div class="list-content">
          <div
            v-for="friend in selectedFriends"
            :key="friend.id"
            class="friend-entry"
          >
            <div class="friend-info">
              <img
                :src="friend.avatar"
                alt="avatar"
                class="friend-avatar"
              />
              <span class="friend-name">{{ friend.name }}</span>
            </div>
            <button
              class="remove-btn"
              @click="removeFriend(friend)"
            >
              -
            </button>
          </div>
        </div>
      </div>
    </div>
    <div class="invite-friends-footer">
      <button
        class="send-invites-btn"
        @click="sendInvitations"
      >
        {{ t("Send Invitations") }}
      </button>
    </div>
    <div class="invited-friends-list mt-4">
      <div class="list-header">
        <h3>{{ t("Users Already Invited") }}</h3>
      </div>
      <div class="invited-users-grid mt-4">
        <div
          v-for="user in invitedFriends"
          :key="user.id"
          class="user-card"
        >
          <img
            :src="user.avatar"
            alt="avatar"
            class="user-avatar"
          />
          <span class="user-name">{{ user.name }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute } from "vue-router"
import axios from "axios"
import { useSecurityStore } from "../../store/securityStore"

const { t } = useI18n()
const route = useRoute()
const securityStore = useSecurityStore()
const availableFriends = ref([])
const selectedFriends = ref([])
const invitedFriends = ref([])
const selectFriend = (friend) => {
  availableFriends.value = availableFriends.value.filter((f) => f.id !== friend.id)
  selectedFriends.value.push(friend)
}
const removeFriend = (friend) => {
  selectedFriends.value = selectedFriends.value.filter((f) => f.id !== friend.id)
  availableFriends.value.push(friend)
}
const loadAvailableFriends = async () => {
  const groupId = route.params.group_id
  const userId = securityStore.user.id
  try {
    const response = await axios.get(`/social-network/invite-friends/${userId}/${groupId}`)
    availableFriends.value = response.data.friends
  } catch (error) {
    console.error("Error loading available friends:", error)
  }
}
onMounted(() => {
  loadAvailableFriends()
  loadInvitedFriends()
})
const sendInvitations = async () => {
  const groupId = route.params.group_id
  const userIds = selectedFriends.value.map((friend) => friend.id)
  try {
    await axios.post(`/social-network/add-users-to-group/${groupId}`, {
      userIds,
    })
    console.log("Users added to group successfully!")
    selectedFriends.value = []
    loadInvitedFriends()
  } catch (error) {
    console.error("Error adding users to group:", error)
  }
}
const loadInvitedFriends = async () => {
  const groupId = route.params.group_id
  try {
    const response = await axios.get(`/social-network/group/${groupId}/invited-users`)
    invitedFriends.value = response.data.invitedUsers
  } catch (error) {
    console.error("Error loading invited friends:", error)
  }
}
</script>
