<template>
  <Dialog
    v-model:visible="visible"
    modal
    :header="t('Comments')"
    :style="{ width: '600px' }"
    @hide="onHide"
  >
    <div class="space-y-4">
      <div class="bg-gray-100 p-3 rounded">
        <h4 class="font-bold text-md">{{ props.item.title }}</h4>
        <div
          class="text-sm text-gray-700 prose max-w-none"
          v-html="props.item.description"
        ></div>
      </div>

      <Textarea
        v-model="comment"
        :placeholder="t('Write your comment...')"
        class="w-full"
        rows="5"
      />

      <div class="flex flex-col gap-2">
        <label>{{ t("Attach file (optional)") }}</label>
        <input
          type="file"
          @change="handleFileUpload"
        />
      </div>

      <div class="flex items-center gap-2">
        <BaseCheckbox
          id="sendmail"
          v-model="sendMail"
          :label="t('Send mail to student')"
          name=""
        />
      </div>

      <div class="flex justify-end gap-2">
        <Button
          :label="t('Cancel')"
          class="p-button-text"
          @click="close"
        />
        <Button
          :label="t('Send')"
          @click="submit"
        />
      </div>
    </div>
    <div
      v-if="comments.length"
      class="mt-6 border-t pt-4 space-y-4 max-h-[300px] overflow-auto"
    >
      <div
        v-for="comment in comments"
        :key="comment['@id']"
        class="bg-gray-10 border rounded p-3 space-y-2"
      >
        <div class="flex justify-between items-center">
          <span class="font-semibold text-sm">
            {{ comment.user?.fullName || comment.user?.fullname || "Unknown User" }}
          </span>
          <span class="text-gray-50 text-xs">
            {{ formatDate(comment.sentAt) }}
          </span>
        </div>
        <p class="text-gray-90 whitespace-pre-line text-sm">
          {{ comment.comment }}
        </p>
        <div
          v-if="comment.file && comment.downloadUrl"
          class="flex items-center gap-1 text-sm"
        >
          <i class="pi pi-paperclip text-gray-50"></i>
          <a
            :href="comment.downloadUrl"
            target="_blank"
            class="text-blue-50 underline break-all"
          >
            {{ comment.file }}
          </a>
        </div>
      </div>
    </div>
  </Dialog>
</template>

<script setup>
import { ref, watch } from "vue"
import { useNotification } from "../../composables/notification"
import { useI18n } from "vue-i18n"
import Textarea from "primevue/textarea"
import Button from "primevue/button"
import Dialog from "primevue/dialog"
import BaseCheckbox from "../basecomponents/BaseCheckbox.vue"
import cStudentPublicationService from "../../services/cstudentpublication"

const props = defineProps({
  modelValue: Boolean,
  item: Object,
})

const emit = defineEmits(["update:modelValue", "commentSent"])

const visible = ref(false)
const comment = ref("")
const sendMail = ref(false)
const selectedFile = ref(null)
const { t } = useI18n()
const notification = useNotification()

watch(
  () => props.modelValue,
  async (newVal) => {
    visible.value = newVal
    if (newVal) {
      comment.value = ""
      sendMail.value = false
      selectedFile.value = null
      comments.value = await cStudentPublicationService.loadComments(props.item.iid)
    }
  },
)

const comments = ref([])

function formatDate(dateStr) {
  if (!dateStr) return ""
  const now = new Date()
  const date = new Date(dateStr)
  const diffMs = date - now
  const diffMinutes = Math.round(diffMs / 60000)

  const rtf = new Intl.RelativeTimeFormat("en", { numeric: "auto" })
  return rtf.format(diffMinutes, "minute")
}

function onHide() {
  emit("update:modelValue", false)
}

function close() {
  emit("update:modelValue", false)
}

function handleFileUpload(event) {
  selectedFile.value = event.target.files[0] || null
}

async function submit() {
  if (!comment.value.trim()) {
    notification.showErrorNotification(t("Comment is required"))
    return
  }

  try {
    const formData = new FormData()
    if (selectedFile.value) {
      formData.append("uploadFile", selectedFile.value)
    }
    formData.append("comment", comment.value)

    await cStudentPublicationService.uploadComment(
      props.item.iid,
      getResourceNodeId(props.item.resourceNode),
      formData,
      sendMail.value,
    )

    notification.showSuccessNotification(t("Comment added successfully"))

    comments.value = await cStudentPublicationService.loadComments(props.item.iid)
    comment.value = ""
    selectedFile.value = null
  } catch (error) {
    notification.showErrorNotification(error)
  }
}

function getResourceNodeId(resourceNode) {
  if (!resourceNode) return 0

  if (typeof resourceNode === "object" && "id" in resourceNode) {
    return parseInt(resourceNode.id, 10)
  }

  const idString = typeof resourceNode === "string" ? resourceNode : resourceNode["@id"]
  if (!idString || typeof idString !== "string") return 0

  const match = idString.match(/\/(\d+)$/)
  return match ? parseInt(match[1], 10) : 0
}
</script>
