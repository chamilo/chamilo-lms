<template>
  <div class="social-group-show">
    <div class="group-header">
      <h1 class="group-title">mi grupo 0002</h1>
      <p class="group-description">test</p>
    </div>

    <ul class="tabs">
      <li :class="{ active: activeTab === 'discussions' }" @click="activeTab = 'discussions'">Discussions</li>
      <li :class="{ active: activeTab === 'members' }" @click="activeTab = 'members'">Members</li>
    </ul>

    <div class="tab-content">
      <GroupDiscussions v-if="activeTab === 'discussions'" :group-id="groupId" />
      <GroupMembers v-if="activeTab === 'members'" :group-id="groupId" />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'
import GroupDiscussions from "../../components/usergroup/GroupDiscussions.vue"
import GroupMembers from "../../components/usergroup/GroupMembers.vue"
const route = useRoute()
const activeTab = ref('discussions')
const groupId = ref(route.params.group_id)
const group = ref(null)
onMounted(async () => {
  if (groupId.value) {
    try {
      const response = await axios.get(`/api/usergroup/${groupId.value}`)
      group.value = response.data
    } catch (error) {
      console.error('Error fetching group details:', error)
    }
  }
})
</script>
