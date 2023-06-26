<template>
  <div>
    <SocialWallPost
      v-for="socialPost in postList"
      :key="socialPost.id"
      :post="socialPost"
      @post-deleted="onPostDeleted($event)"
    />

    <Loading :visible="isLoading" />
  </div>
</template>

<script setup>
import SocialWallPost from "./SocialWallPost.vue";
import {inject, onMounted, reactive, ref, watch} from "vue";
import Loading from "../Loading";
import axios from "axios";
import {ENTRYPOINT} from "../../config/entrypoint";

const user = inject('social-user');

const postList = reactive([]);
const isLoading = ref(false);

watch(() => user.value, () => {listPosts()});

onMounted(listPosts)

function listPosts() {
  postList.splice(0, postList.length);

  if (!user.value['@id']) {
    return;
  }

  isLoading.value = true;

  axios
    .get(ENTRYPOINT + 'social_posts', {
      params: {
        socialwall_wallOwner: user.value['id'],
        'order[sendDate]': 'desc',
      }
    })
    .then(response => {
      postList.push(...response.data['hydra:member']);
    })
    .finally(() => {
      isLoading.value = false;
    });
}

function onPostDeleted(event) {
  const index = postList.findIndex(post => post['@id'] === event['@id']);

  if (index >= 0) {
    postList.splice(index, 1);
  }
}
</script>
