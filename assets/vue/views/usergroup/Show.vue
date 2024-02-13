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
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import GroupDiscussions from "../../components/usergroup/GroupDiscussions.vue"
import GroupMembers from "../../components/usergroup/GroupMembers.vue"

const route = useRoute()
const activeTab = ref('discussions')
const groupId = ref(route.query.group_id)

onMounted(() => {
  if (groupId.value) {
  }
})
</script>
