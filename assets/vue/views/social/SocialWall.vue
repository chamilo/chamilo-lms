<template>
  <div>
    <SocialWallPostForm @post-created="refreshPosts" class="mb-6" />
    <SocialWallPostList ref="postListRef" class="mb-6" />
  </div>
</template>

<script setup>
import { ref } from 'vue';
import SocialWallPostForm from "../../components/social/SocialWallPostForm.vue"
import SocialWallPostList from "../../components/social/SocialWallPostList.vue"

const postListRef = ref(null);

function refreshPosts() {
  if (postListRef.value) {
    postListRef.value.refreshPosts();
  }
}
</script>
