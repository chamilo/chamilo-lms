<template>
  <WallPost
    v-for="socialPost in postList"
    :key="socialPost.id"
    :post="socialPost"
  />

  <Loading :visible="isLoading" />
</template>

<script>
import WallPost from "./WallPost";
import {inject, onMounted, ref, watch} from "vue";
import Loading from "../Loading";
import axios from "axios";
import {ENTRYPOINT} from "../../config/entrypoint";

export default {
  name: "WallPostList",
  components: {Loading, WallPost},
  setup() {
    const user = inject('social-user');

    const postList = ref([]);
    const isLoading = ref(false);

    function listPosts() {
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
          postList.value = response.data['hydra:member'];
        })
        .catch(() => {
          postList.value = [];
        })
        .finally(() => {
          isLoading.value = false;
        });
    }

    watch(() => user.value, () => {listPosts()});

    onMounted(listPosts)

    return {
      postList,
      isLoading
    }
  }
}
</script>
