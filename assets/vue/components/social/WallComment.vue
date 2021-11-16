<template>
  <q-item>
    <q-item-section avatar top>
      <q-avatar>
        <img :src="comment.sender.illustrationUrl">
      </q-avatar>
    </q-item-section>

    <q-item-section top>
      <q-item-label lines="1">
        <span class="text-weight-medium">{{ comment.sender.fullName }}</span>
      </q-item-label>
      <q-item-label v-html="comment.content" />
      <q-item-label
        :title="$filters.abbreviatedDatetime(comment.sendDate)"
        caption
      >
        {{ $filters.relativeDatetime(comment.sendDate) }}
      </q-item-label>
    </q-item-section>

    <q-item-section side top>
      <div class="text-grey-8 q-gutter-xs">
        <q-btn
          v-if="enableFeedback"
          :label="comment.countFeedbackLikes"
          :title="$t('Like')"
          class="gt-xs"
          dense
          flat
          icon="mdi-heart-plus"
          size="12px"
        />
        <q-btn
          v-if="enableFeedback && !disableDislike"
          :label="comment.countFeedbackDislikes"
          :title="$t('Dislike')"
          class="gt-xs"
          dense
          flat
          icon="mdi-heart-remove"
          size="12px"
        />
        <q-btn
          v-if="isOwner"
          :loading="isLoading"
          :title="$t('Delete comment')"
          class="gt-xs"
          dense
          flat
          icon="delete"
          size="12px"
          @click="deleteComment"
        />
      </div>
    </q-item-section>
  </q-item>
</template>

<script>
import {useStore} from "vuex";
import {computed, ref} from "vue";
import axios from "axios";

export default {
  name: "WallComment",
  props: {
    comment: {
      type: Object,
      required: true
    }
  },
  emits: ['comment-deleted'],
  setup(props, context) {
    const store = useStore();

    const isLoading = ref(false);
    const currentUser = store.getters['security/getUser'];

    function deleteComment() {
      isLoading.value = true;

      axios
        .delete(props.comment['@id'])
        .then(() => context.emit('comment-deleted', props.comment))
        .finally(() => isLoading.value = false)
      ;
    }

    const enableFeedback = ref(window.config['social.social_enable_messages_feedback'] === 'true');
    const disableDislike = ref(window.config['social.disable_dislike_option'] === 'true');

    return {
      deleteComment,
      isOwner: computed(() => currentUser['@id'] === props.comment.sender['@id']),
      enableFeedback,
      disableDislike,
      isLoading,
    };
  }
}
</script>
