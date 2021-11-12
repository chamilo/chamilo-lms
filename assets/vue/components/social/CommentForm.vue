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
        icon="send"
        @click="sendComment"
      />
    </div>
  </q-form>
</template>

<script>
import {ref} from "vue";
import {useStore} from "vuex";
import {MESSAGE_TYPE_WALL} from "../message/constants";

export default {
  name: "WallCommentForm",
  props: {
    post: {
      type: Object,
      required: true,
    }
  },
  setup(props) {
    const store = useStore();

    const currentUser = store.getters['security/getUser'];

    const comment = ref('');

    async function sendComment() {
      await store.dispatch('message/create', {
        title: 'Comment',
        content: comment.value,
        msgType: MESSAGE_TYPE_WALL,
        sender: currentUser['@id'],
        parent: props.post['@id'],
      });

      comment.value = '';
    }

    return {
      sendComment,
      comment
    };
  }
}
</script>
