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

    <q-item-section
      v-if="isOwner"
      side
      top
    >
      <q-btn
        :aria-label="$t('Delete comment')"
        dense
        icon="delete"
        @click="deleteComment"
      />
    </q-item-section>
  </q-item>
</template>

<script>
import {useStore} from "vuex";

export default {
  name: "SocialNetworkPostComment",
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
        .dispatch('message/del', props.comment)
        .then(() => context.emit('deleted'));
    }

    return {
      deleteComment,
      isOwner: currentUser['@id'] === props.comment.sender['@id'],
    };
  }
}
</script>
