<template>
  <form class="flex flex-wrap items-start gap-2">
    <BaseInputText
      v-model="comment"
      class="grow mb-0"
      :label="$t('Write new comment')"
      :aria-placeholder="$t('Write new comment')"
      :is-invalid="error !== ''"
      :error-text="error"
    />
    <BaseButton
      :label="$t('Post')"
      class="ml-auto"
      type="primary"
      icon="send"
      size="small"
      @click="sendComment"
    />
  </form>
</template>

<script setup>
import {ref} from "vue"
import {useStore} from "vuex"
import axios from "axios"
import {ENTRYPOINT} from "../../config/entrypoint"
import {SOCIAL_TYPE_WALL_COMMENT} from "./constants"
import BaseInputText from "../basecomponents/BaseInputText.vue";
import {useI18n} from "vue-i18n";
import BaseButton from "../basecomponents/BaseButton.vue";

const store = useStore()
const {t} = useI18n()

const props = defineProps({
  post: {
    type: Object,
    required: true,
  }
})

const emit = defineEmits(['comment-posted'])

const currentUser = store.getters['security/getUser']
const comment = ref('')
const error = ref('')
const isLoading = ref(false)

function sendComment() {
  if (comment.value === '') {
    error.value = t('The comment is required')
    return
  }
  isLoading.value = true

  axios
    .post(ENTRYPOINT + 'social_posts', {
      content: comment.value,
      type: SOCIAL_TYPE_WALL_COMMENT,
      sender: currentUser['@id'],
      parent: props.post['@id'],
    })
    .then(response => {
      emit('comment-posted', response.data)
      comment.value = ''
      error.value = ''
    })
    .finally(() => {
      isLoading.value = false
    })
}
</script>
