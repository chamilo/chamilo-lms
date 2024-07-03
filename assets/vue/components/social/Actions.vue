<template>
  <div class="text-grey-8 q-gutter-xs">
    <button
      v-if="enableFeedback"
      :loading="isLoading.like"
      :title="$t('Like')"
      class="gt-xs dense flat"
      @click="onLikeComment"
    >
      <i class="mdi mdi-heart-plus mdi-24px"></i>
      {{ socialPost.countFeedbackLikes }}
    </button>
    <button
      v-if="enableFeedback && !disableDislike"
      :loading="isLoading.dislike"
      :title="$t('Dislike')"
      class="gt-xs dense flat"
      @click="onDisikeComment"
    >
      <i class="mdi mdi-heart-remove mdi-24px"></i>
      {{ socialPost.countFeedbackDislikes }}
    </button>
    <button
      v-if="isOwner"
      :loading="isLoading.delete"
      :title="$t('Delete')"
      class="gt-xs dense flat"
      @click="onDeleteComment"
    >
      <i class="mdi mdi-delete mdi-24px"></i>
    </button>
  </div>
</template>

<script>
import { reactive } from "vue"
import { usePlatformConfig } from "../../store/platformConfig"
import socialService from "../../services/socialService"

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
    const platformConfigStore = usePlatformConfig()

    const isLoading = reactive({
      like: false,
      dislike: false,
      delete: false,
    })

    function onLikeComment() {
      isLoading.like = true

      socialService
        .sendPostLike(props.socialPost["@id"])
        .then((like) => {
          props.socialPost.countFeedbackLikes = like.countFeedbackLikes
          props.socialPost.countFeedbackDislikes = like.countFeedbackDislikes
        })
        .finally(() => (isLoading.like = false))
    }

    function onDisikeComment() {
      isLoading.dislike = true

      socialService
        .sendPostDislike(props.socialPost["@id"])
        .then((like) => {
          props.socialPost.countFeedbackLikes = like.countFeedbackLikes
          props.socialPost.countFeedbackDislikes = like.countFeedbackDislikes
        })
        .finally(() => (isLoading.dislike = false))
    }

    function onDeleteComment() {
      isLoading.delete = true

      socialService
        .delete(props.socialPost["@id"])
        .then(() => emit("post-deleted", props.socialPost))
        .finally(() => (isLoading.delete = false))
    }

    const enableFeedback = "true" === platformConfigStore.getSetting("social.social_enable_messages_feedback")
    const disableDislike = "true" === platformConfigStore.getSetting("social.disable_dislike_option")

    return {
      enableFeedback,
      disableDislike,
      isLoading,
      onLikeComment,
      onDisikeComment,
      onDeleteComment,
    }
  },
}
</script>
