<template>
  <div>
    <div class="discussions-header">
      <h2>Discussions</h2>
      <a :href="threadCreationUrl" class="btn btn-primary create-thread-btn">
        <i class="pi pi-plus"></i> {{ t("Create thread") }}
      </a>
    </div>
    <div class="discussion-item" v-for="discussion in discussions" :key="discussion.id">
      <div class="discussion-content">
        <div class="discussion-title" v-html="discussion.title"></div>
        <div class="discussion-details">
          <i class="mdi mdi-message-reply-text icon"></i>
          <span>{{ discussion.repliesCount }} {{ t("Replies") }}</span>
          <i class="mdi mdi-clock-outline icon"></i>
          <span>Created {{ new Date(discussion.sendDate).toLocaleDateString() }}</span>
        </div>
      </div>
      <div class="discussion-author">
        <img v-if="discussion.sender.illustrationUrl" :src="discussion.sender.illustrationUrl" class="author-avatar-icon">
        <i v-else class="mdi mdi-account-circle-outline author-avatar-icon"></i>
        <span class="author-name">{{ discussion.sender.name }}</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'
const route = useRoute()
const discussions = ref([])
const groupId = ref(route.params.group_id)

onMounted(async () => {
  if (groupId.value) {
    try {
      const response = await axios.get(`/api/messages/by-group/list?groupId=${groupId.value}`)
      discussions.value = response.data['hydra:member'].map(discussion => ({
        ...discussion,
        repliesCount: discussion.receiversTo.length + discussion.receiversCc.length
      }))
    } catch (error) {
      console.error('Error fetching discussions:', error)
      discussions.value = []
    }
  }
})
const threadCreationUrl = computed(() => {
  return `/main/social/message_for_group_form.inc.php?view_panel=1&user_friend=1&group_id=${groupId.value}&action=add_message_group`
})
</script>
