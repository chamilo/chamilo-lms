<template>
  <div>
    <div class="discussions-header">
      <h2>Discussions</h2>
      <a
        :href="threadCreationUrl"
        class="btn btn-primary ajax create-thread-btn"
        role="button"
      >
        <i class="pi pi-plus"></i> Create thread
      </a>
    </div>
    <div class="discussion-item" v-for="discussion in discussions" :key="discussion.id">
      <div class="discussion-content">
        <div class="discussion-title">{{ discussion.title }}</div>
        <div class="discussion-details">
          <i class="mdi mdi-message-reply-text icon"></i>
          <span>{{ discussion.replies }} Replies</span>
          <i class="mdi mdi-clock-outline icon"></i>
          <span>Created {{ discussion.created }}</span>
        </div>
      </div>
      <div class="discussion-author">
        <i class="mdi mdi-account-circle-outline author-avatar-icon"></i>
        <span class="author-name">{{ discussion.author.name }}</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watchEffect, computed } from 'vue'
import { useRoute } from 'vue-router'
import BaseButton from "../basecomponents/BaseButton.vue"

const route = useRoute()
//const discussions = ref([])
const groupId = route.query.group_id
// Simulated discussion data
const discussions = ref([
  {
    id: '1',
    title: 'topic 001',
    replies: 0,
    creationDate: new Date().toISOString(),
    author: {
      name: 'John Doe',
      //avatar: 'path/to/avatar.jpg',
    },
  },
  {
    id: '2',
    title: 'thread 001',
    replies: 0,
    creationDate: new Date().toISOString(),
    author: {
      name: 'Jane Smith',
      //avatar: 'path/to/avatar.jpg',
    },
  },
  // ... other discussions
])
const threadCreationUrl = computed(() => {
  const groupId = route.query.group_id || 'default-group-id'
  return `/main/social/message_for_group_form.inc.php?view_panel=1&user_friend=1&group_id=${groupId}&action=add_message_group`
})
/*
watchEffect(() => {
  const groupId = route.query.group_id
  if (groupId) {
    // discussions.value = fetchDiscussions(groupId)
  }
});*/
</script>
