<template>
  <div class="group-members">
    <div
      v-if="groupInfo.isModerator"
      class="edit-members"
    >
      <BaseButton
        class="edit-members-btn"
        icon="pi pi-plus"
        label="Edit members list"
        type="primary"
        @click="editMembers"
      />
    </div>
    <div class="members-grid">
      <div
        v-for="member in members"
        :key="member.id"
        class="member-card"
      >
        <div class="member-avatar">
          <img
            v-if="member.avatar"
            :src="member.avatar"
            alt="Member avatar"
          />
          <i
            v-else
            class="mdi mdi-account-circle-outline"
          ></i>
        </div>
        <div class="member-name">
          {{ member.name }}
          <i
            v-if="member.isAdmin"
            class="mdi mdi-star-outline admin-icon"
          ></i>
        </div>
        <div
          v-if="member.role"
          class="member-role"
        >
          {{ member.role }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from "vue"
import { useRoute } from "vue-router"
import BaseButton from "../basecomponents/BaseButton.vue"
import axios from "axios"
import { useSocialInfo } from "../../composables/useSocialInfo"

const route = useRoute()
const members = ref([])
const groupId = ref(route.params.group_id)
const { user, groupInfo, isGroup, loadGroup, isLoading } = useSocialInfo()
const fetchMembers = async (groupId) => {
  if (groupId.value) {
    try {
      const response = await axios.get(`/api/usergroups/${groupId.value}/members`)
      members.value = response.data["hydra:member"].map((member) => ({
        id: member.id,
        name: member.username,
        role: member.relationType === 1 ? "Admin" : "Member",
        avatar: member.pictureUri,
        isAdmin: member.relationType === 1,
      }))
    } catch (error) {
      console.error("Error fetching group members:", error)
      members.value = []
    }
  }
}
const editMembers = () => {}

onMounted(() => {
  if (groupId.value) {
    fetchMembers(groupId)
  }
})
</script>
