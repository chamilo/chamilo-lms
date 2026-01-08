<template>
  <form
    class="mt-3"
    @submit.prevent="sendComment"
  >
    <div class="grid grid-cols-1 sm:grid-cols-[1fr_auto] gap-2 items-start">
      <div class="w-full">
        <label class="sr-only">{{ t("Write new comment") }}</label>

        <textarea
          v-model="comment"
          :placeholder="t('Write new comment')"
          class="w-full min-h-[96px] resize-y rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 placeholder:text-gray-500 focus:outline-none focus:ring-2 focus:ring-primary"
          rows="4"
          @input="error = ''"
        />

        <p
          v-if="error"
          class="mt-1 text-sm text-danger"
        >
          {{ error }}
        </p>
      </div>

      <BaseButton
        :label="t('Post')"
        icon="send"
        size="small"
        type="primary"
        :disabled="isLoading"
        class="sm:self-end"
        @click="sendComment"
      />
    </div>
  </form>
</template>

<script setup>
import { ref } from "vue"
import axios from "axios"
import { ENTRYPOINT } from "../../config/entrypoint"
import { SOCIAL_TYPE_WALL_COMMENT } from "./constants"
import { useI18n } from "vue-i18n"
import BaseButton from "../basecomponents/BaseButton.vue"
import { useSecurityStore } from "../../store/securityStore"

const securityStore = useSecurityStore()
const { t } = useI18n()

const props = defineProps({
  post: {
    type: Object,
    required: true,
  },
})

const emit = defineEmits(["comment-posted"])

const comment = ref("")
const error = ref("")
const isLoading = ref(false)

function sendComment() {
  if (!comment.value.trim()) {
    error.value = t("The comment is required")
    return
  }
  isLoading.value = true

  axios
    .post(ENTRYPOINT + "social_posts", {
      content: comment.value,
      type: SOCIAL_TYPE_WALL_COMMENT,
      sender: securityStore.user["@id"],
      parent: props.post["@id"],
    })
    .then((response) => {
      emit("comment-posted", response.data)
      comment.value = ""
      error.value = ""
    })
    .finally(() => {
      isLoading.value = false
    })
}
</script>
