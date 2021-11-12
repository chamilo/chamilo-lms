<template>
  <q-item bordered>
    <q-item-section avatar top>
      <q-avatar>
        <img :src="comment.sender.illustrationUrl">
      </q-avatar>
    </q-item-section>

    <q-item-section>
      <q-item-label class="font-bold" lines="1">{{ comment.sender.username }}</q-item-label>
      <q-item-label v-html="comment.content" />
      <q-item-label caption>{{ $filters.relativeDatetime(comment.sendDate) }}</q-item-label>
    </q-item-section>

    <q-item-section side top>
      <q-btn
        v-if="enableFeedback"
        :label="comment.countFeedbackLikes"
        :title="$t('Like')"
        dense
        flat
        icon="mdi-heart-plus"
      />
      <q-btn
        v-if="enableFeedback && !disableDislike"
        :label="comment.countFeedbackDislikes"
        :title="$t('Dislike')"
        dense
        flat
        icon="mdi-heart-remove"
      />
      <q-btn
        v-if="isOwner"
        :title="$t('Delete comment')"
        dense
        flat
        icon="delete"
        @click="deleteComment"
      />
    </q-item-section>
  </q-item>
</template>

<script>
import {useStore} from "vuex";
import {ref} from "vue";

export default {
  name: "WallComment",
  props: {
    comment: {
      type: Object,
      required: true
    }
  },
  emits: ['deleted'],
  setup(props, context) {
    const store = useStore();

    const currentUser = store.getters['security/getUser'];

    function deleteComment() {
      store
        .dispatch('socialpost/del', props.comment)
        .then(() => context.emit('deleted'));
    }

    const enableFeedback = ref(window.config['social.social_enable_messages_feedback'] === 'true');
    const disableDislike = ref(window.config['social.disable_dislike_option'] === 'true');

    return {
      deleteComment,
      isOwner: currentUser['@id'] === props.comment.sender['@id'],
      enableFeedback,
      disableDislike,
    };
  }
}
</script>
