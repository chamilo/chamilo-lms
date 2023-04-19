<template>
  <div class="text-grey-8 q-gutter-xs">
    <q-btn
      v-if="enableFeedback"
      :label="socialPost.countFeedbackLikes"
      :loading="isLoading.like"
      :title="$t('Like')"
      class="gt-xs"
      dense
      flat
      icon="mdi-heart-plus"
      size="12px"
      @click="onLikeComment"
    />
    <q-btn
      v-if="enableFeedback && !disableDislike"
      :label="socialPost.countFeedbackDislikes"
      :loading="isLoading.dislike"
      :title="$t('Dislike')"
      class="gt-xs"
      dense
      flat
      icon="mdi-heart-remove"
      size="12px"
      @click="onDisikeComment"
    />
    <q-btn
      v-if="isOwner"
      :loading="isLoading.delete"
      :title="$t('Delete')"
      class="gt-xs"
      dense
      flat
      icon="delete"
      size="12px"
      @click="onDeleteComment"
    />
  </div>
</template>

<script>
import { reactive, ref } from "vue";
import axios from "axios";
import { usePlatformConfig } from "../../store/platformConfig";

export default {
  name: "WallActions",
  props: {
    isOwner: {
      type: Boolean,
      default: false,
    },
    socialPost: {
      type: Object,
      required: true,
    },
  },
  emits: ["post-deleted"],
  setup(props, { emit }) {
    const platformConfigStore = usePlatformConfig();

    const isLoading = reactive({
      like: false,
      dislike: false,
      delete: false,
    });

    function onLikeComment() {
      isLoading.like = true;

      axios
        .post(props.socialPost["@id"] + "/like", {})
        .then(({ data }) => {
          props.socialPost.countFeedbackLikes = data.countFeedbackLikes;
          props.socialPost.countFeedbackDislikes = data.countFeedbackDislikes;
        })
        .finally(() => (isLoading.like = false));
    }

    function onDisikeComment() {
      isLoading.dislike = true;

      axios
        .post(props.socialPost["@id"] + "/dislike", {})
        .then(({ data }) => {
          props.socialPost.countFeedbackLikes = data.countFeedbackLikes;
          props.socialPost.countFeedbackDislikes = data.countFeedbackDislikes;
        })
        .finally(() => (isLoading.dislike = false));
    }

    function onDeleteComment() {
      isLoading.delete = true;

      axios
        .delete(props.socialPost["@id"])
        .then(() => emit("post-deleted", props.socialPost))
        .finally(() => (isLoading.delete = false));
    }

    const enableFeedback =
      "true" ===
      platformConfigStore.getSetting("social.social_enable_messages_feedback");
    const disableDislike =
      "true" ===
      platformConfigStore.getSetting("social.disable_dislike_option");

    return {
      enableFeedback,
      disableDislike,
      isLoading,
      onLikeComment,
      onDisikeComment,
      onDeleteComment,
    };
  },
};
</script>
