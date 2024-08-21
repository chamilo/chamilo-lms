<template>
  <BaseCard plain>
    <form>
      <BaseTinyEditor
        v-model="content"
        :editor-id="'content-editor'"
        :required="true"
        :title="textPlaceholder"
        :editor-config="editorConfig"
        class="mb-2"
      />

      <div class="mb-2">
        <BaseCheckbox
          v-if="allowCreatePromoted && !isPromotedPage"
          id="is-promoted"
          v-model="isPromoted"
          :label="$t('Mark as promoted message')"
          name="is-promoted"
        />
        <div
          v-if="isPromotedPage"
          class="text-info"
        >
          {{ $t("All messages here are automatically marked as promoted.") }}
        </div>
      </div>

      <div class="flex mb-2">
        <BaseFileUpload
          id="post-file"
          :label="t('File upload')"
          accept="image"
          size="small"
          @file-selected="selectedFile = $event"
        />

        <BaseButton
          :label="$t('Post')"
          class="ml-auto"
          icon="send"
          size="small"
          type="primary"
          @click="sendPost"
        />
      </div>
    </form>
  </BaseCard>
</template>

<script setup>
import { computed, inject, onMounted, reactive, ref, toRefs, watch } from "vue"
import { SOCIAL_TYPE_PROMOTED_MESSAGE, SOCIAL_TYPE_WALL_POST } from "./constants"
import useVuelidate from "@vuelidate/core"
import { required } from "@vuelidate/validators"
import { useI18n } from "vue-i18n"
import BaseCard from "../basecomponents/BaseCard.vue"
import BaseButton from "../basecomponents/BaseButton.vue"
import BaseFileUpload from "../basecomponents/BaseFileUpload.vue"
import BaseCheckbox from "../basecomponents/BaseCheckbox.vue"
import { useRoute } from "vue-router"
import { useSecurityStore } from "../../store/securityStore"
import socialService from "../../services/socialService"
import { useNotification } from "../../composables/notification"
import BaseTinyEditor from "../basecomponents/BaseTinyEditor.vue"

const emit = defineEmits(["post-created"])
const securityStore = useSecurityStore()
const { t } = useI18n()
const route = useRoute()
const { showErrorNotification } = useNotification()
const isPromotedPage = computed(() => {
  return route.query.filterType === "promoted"
})
const user = inject("social-user")
const selectedFile = ref(null)
const postState = reactive({
  content: "",
  attachment: null,
  isPromoted: false,
  textPlaceholder: "",
})
const { content, attachment, isPromoted, textPlaceholder } = toRefs(postState)

const v$ = useVuelidate(
  {
    content: { required },
  },
  postState,
)

watch(
  () => user.value,
  () => {
    showTextPlaceholder()
    showCheckboxPromoted()
  },
)

watch(
  isPromotedPage,
  (newVal) => {
    if (newVal) {
      postState.isPromoted = true
    }
  },
  { immediate: true },
)

onMounted(() => {
  showTextPlaceholder()
  showCheckboxPromoted()
})

function showTextPlaceholder() {
  postState.textPlaceholder =
    securityStore.user["@id"] === user.value["@id"]
      ? t("What are you thinking about?")
      : t("Write something to {0}", [user.value.fullName])
}

const allowCreatePromoted = ref(false)

function showCheckboxPromoted() {
  allowCreatePromoted.value = securityStore.isAdmin && securityStore.user["@id"] === user.value["@id"]
}

async function sendPost() {
  v$.value.$touch()
  if (!postState.content.trim()) {
    return
  }

  if (v$.value.$error) {
    return
  }

  if (isPromotedPage.value) {
    postState.isPromoted = true
  }

  try {
    const post = await socialService.createPost({
      content: postState.content,
      type: postState.isPromoted ? SOCIAL_TYPE_PROMOTED_MESSAGE : SOCIAL_TYPE_WALL_POST,
      sender: securityStore.user["@id"],
      userReceiver: securityStore.user["@id"] === user.value["@id"] ? null : user.value["@id"],
    })

    if (selectedFile.value) {
      const formData = new FormData()
      let idUrl = post["@id"]
      let parts = idUrl.split("/")
      let socialPostId = parts[parts.length - 1]
      formData.append("file", selectedFile.value)
      formData.append("messageId", socialPostId)

      await socialService.addAttachment(formData)
    }

    postState.content = ""
    postState.attachment = null
    postState.isPromoted = false
    postState.isPromoted = isPromotedPage.value
    v$.value.$reset()
    emit("post-created")
  } catch (error) {
    console.error("There was an error creating the post:", error)
    showErrorNotification("There was an error creating the post")
  }
}

// Editor configuration
const editorConfig = computed(() => ({
  height: 300,
  plugins: "link code",
  toolbar: "undo redo | formatselect | bold italic | alignleft aligncenter alignright | code",
}))
</script>
