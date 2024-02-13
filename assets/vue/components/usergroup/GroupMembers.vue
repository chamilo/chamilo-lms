<template>
  <div class="group-members">
    <div class="edit-members">
      <BaseButton
        label="Edit members list"
        type="primary"
        class="edit-members-btn"
        icon="pi pi-plus"
        @click="editMembers"
      />
    </div>
    <div class="members-grid">
      <div class="member-card" v-for="member in members" :key="member.id">
        <div class="member-avatar">
          <img v-if="member.image" :src="member.image" alt="Member avatar">
          <i v-else class="mdi mdi-account-circle-outline"></i>
        </div>
        <div class="member-name">{{ member.name }}</div>
        <div class="member-role" v-if="member.role">{{ member.role }}</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watchEffect } from 'vue'
import { useRoute } from 'vue-router'
import BaseButton from "../basecomponents/BaseButton.vue"

const route = useRoute()
//const members = ref([])
const members = ref([
  { name: 'John Doe', role: 'Admin', avatar: '/path/to/avatar.jpg' },
  { name: 'Jane Doe', role: 'Miembro', avatar: '/path/to/avatar2.jpg' },
  // ...
])
const editMembers = () => {
}
/*
watchEffect(() => {
  const groupId = route.query.group_id
  if (groupId) {
    // members.value = fetchMembers(groupId)
  }
});*/
</script>
