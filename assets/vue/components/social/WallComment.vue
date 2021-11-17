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
          :loading="isLoading.like"
          :title="$t('Like')"
          class="gt-xs"
          dense
          flat
          icon="mdi-heart-plus"
          size="12px"
          @click="likeComment"
        />
        <q-btn
          v-if="enableFeedback && !disableDislike"
          :label="comment.countFeedbackDislikes"
          :loading="isLoading.dislike"
          :title="$t('Dislike')"
          class="gt-xs"
          dense
          flat
          icon="mdi-heart-remove"
          size="12px"
          @click="dislikeComment"
        />
        <q-btn
          v-if="isOwner"
          :loading="isLoading.delete"
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
import {computed, reactive, ref} from "vue";
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

    const isLoading = reactive({
      like: false,
      dislike: false,
      delete: false,
    });
    const currentUser = store.getters['security/getUser'];

    function deleteComment() {
      isLoading.delete = true;

      axios
        .delete(props.comment['@id'])
        .then(() => context.emit('comment-deleted', props.comment))
        .finally(() => isLoading.delete = false)
      ;
    }

    function likeComment() {
      isLoading.like = true;

      axios
        .post(props.comment['@id'] + '/like', {})
        .then(({data}) => {
          props.comment.countFeedbackLikes = data.countFeedbackLikes;
          props.comment.countFeedbackDislikes = data.countFeedbackDislikes;
        })
        .finally(() => isLoading.like = false)
      ;
    }

    function dislikeComment() {
      isLoading.dislike = true;

      axios
        .post(props.comment['@id'] + '/dislike', {})
        .then(({data}) => {
          props.comment.countFeedbackLikes = data.countFeedbackLikes;
          props.comment.countFeedbackDislikes = data.countFeedbackDislikes;
        })
        .finally(() => isLoading.dislike = false)
      ;
    }

    const enableFeedback = ref(window.config['social.social_enable_messages_feedback'] === 'true');
    const disableDislike = ref(window.config['social.disable_dislike_option'] === 'true');

    return {
      likeComment,
      dislikeComment,
      deleteComment,
      isOwner: computed(() => currentUser['@id'] === props.comment.sender['@id']),
      enableFeedback,
      disableDislike,
      isLoading,
    };
  }
}
</script>
