<template>
  <WallPost
    v-for="socialPost in postList"
    :key="socialPost.id"
    :post="socialPost"
  />

  <Loading :visible="isLoading" />
</template>

<script>
import {SOCIAL_TYPE_WALL_POST} from "./constants";

import WallPost from "./WallPost";
import {useStore} from "vuex";
import {inject, onMounted, ref, watch} from "vue";
import Loading from "../Loading";

export default {
  name: "WallPostList",
  components: {Loading, WallPost},
  setup() {
    const user = inject('social-user');
    const store = useStore();

    const postList = ref([]);
    const isLoading = ref(false);

    async function listPosts() {
      if (!user.value['@id']) {
        return;
      }

      isLoading.value = true;

      store.state.socialpost.resetList = true;

      try {
        postList.value = await store.dispatch('socialpost/findAll', {
          type: SOCIAL_TYPE_WALL_POST,
          sender: user.value['@id'],
          'order[sendDate]': 'desc',
          groupReceiver: null
        });
      } catch (e) {
        postList.value = [];
      }

      isLoading.value = false;
    }

    watch(() => user.value, listPosts)

    onMounted(listPosts)

    return {
      postList,
      isLoading
    }
  }
}
</script>
