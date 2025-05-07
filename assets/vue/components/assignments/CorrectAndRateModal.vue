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
        <label>{{ t("Score") }}</label>

        <template v-if="!forceStudentView">
          <input
            type="number"
            v-model.number="qualification"
            class="input border p-2 rounded"
            min="0"
            step="0.1"
          />
        </template>

        <template v-else>
          <span class="border p-2 rounded bg-gray-100 text-sm">
            {{ qualification ?? t("Not graded yet") }}
          </span>
        </template>
      </div>

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
import { useRoute } from "vue-router"
import { useSecurityStore } from "../../store/securityStore"

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
const qualification = ref(null)
const route = useRoute()
const parentResourceNodeId = parseInt(route.params.node)
const securityStore = useSecurityStore()
const isEditor = securityStore.isCourseAdmin || securityStore.isTeacher
const isStudentView = route.query.isStudentView === "true"
const forceStudentView = !isEditor || isStudentView

watch(
  () => props.modelValue,
  async (newVal) => {
    visible.value = newVal
    if (newVal) {
      comment.value = ""
      sendMail.value = false
      selectedFile.value = null
      qualification.value = props.item.qualification ?? null
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
    formData.append("qualification", qualification.value ?? "")

    await cStudentPublicationService.uploadComment(props.item.iid, parentResourceNodeId, formData, sendMail.value)

    notification.showSuccessNotification(t("Comment added successfully"))

    comments.value = await cStudentPublicationService.loadComments(props.item.iid)
    comment.value = ""
    selectedFile.value = null
  } catch (error) {
    notification.showErrorNotification(error)
  }
}
</script>
