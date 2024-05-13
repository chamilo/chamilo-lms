<template>
  <div>
    <SocialWallPostForm v-if="!hidePostForm"  @post-created="refreshPosts" class="mb-6" />
    <SocialWallPostList ref="postListRef" class="mb-6" />
  </div>
</template>

<script setup>
import { ref } from 'vue';
import SocialWallPostForm from "../../components/social/SocialWallPostForm.vue"
import SocialWallPostList from "../../components/social/SocialWallPostList.vue"

const props = defineProps({
  hidePostForm: {
    type: Boolean,
    default: false
  }
});

const postListRef = ref(null);

function refreshPosts() {
  if (postListRef.value) {
    postListRef.value.refreshPosts();
  }
}
</script>
