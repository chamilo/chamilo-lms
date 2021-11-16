<template>
  <q-form class="q-gutter-md p-4 pt-0">
    <q-input
      v-model="comment"
      :label="$t('Write new comment')"
      autogrow
    />
    <div class="row justify-end">
      <q-btn
        :label="$t('Post')"
        :loading="isLoading"
        icon="send"
        @click="sendComment"
      />
    </div>
  </q-form>
</template>

<script>
import {ref} from "vue";
import {useStore} from "vuex";
import axios from "axios";
import {ENTRYPOINT} from "../../config/entrypoint";
import {SOCIAL_TYPE_WALL_COMMENT} from "./constants";

export default {
  name: "WallCommentForm",
  props: {
    post: {
      type: Object,
      required: true,
    }
  },
  emits: ["comment-posted"],
  setup(props, {emit}) {
    const store = useStore();

    const currentUser = store.getters['security/getUser'];

    const comment = ref('');
    const isLoading = ref(false);

    function sendComment() {
      isLoading.value = true;

      axios
        .post(ENTRYPOINT + 'social_posts', {
          content: comment.value,
          type: SOCIAL_TYPE_WALL_COMMENT,
          sender: currentUser['@id'],
          parent: props.post['@id'],
        })
        .then(response => {
          emit('comment-posted', response.data);

          comment.value = '';
        })
        .finally(() => {
          isLoading.value = false
        })
      ;
    }

    return {
      sendComment,
      comment,
      isLoading,
    };
  }
}
</script>
