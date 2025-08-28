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
        <h4 class="font-bold text-md">{{ props.item.publicationParent?.title || t("Original assignment") }}</h4>
        <div
          class="text-sm text-gray-700 prose max-w-none"
          v-html="props.item.publicationParent?.description"
        />
      </div>

      <div
        v-if="flags.allowText && props.item.description"
        class="bg-white border p-3 rounded"
      >
        <h5 class="font-semibold text-sm">{{ t("Student's submission") }}</h5>
        <div
          class="text-sm text-gray-800 whitespace-pre-wrap"
          v-text="props.item.description"
        />
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
            :max="maxQualification"
            step="0.1"
          />
          <template v-if="maxQualification">
            <span class="text-xs text-gray-600"> {{ t("Max score") }}: {{ maxQualification }} </span>
          </template>
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
          v-if="props.flags.allowText"
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
          :disabled="submitting"
          @click="submit"
        />
      </div>
    </div>
    <div
      v-if="comments.length"
      class="mt-6 border-t pt-4 space-y-4 max-h-[300px] overflow-auto"
    >
      <div
        v-for="commentItem in comments"
        :key="commentItem['@id']"
        class="bg-gray-10 border rounded p-3 space-y-2"
      >
        <div class="flex justify-between items-center">
          <span class="font-semibold text-sm">
            {{ commentItem.user?.fullName || commentItem.user?.fullname || "Unknown User" }}
          </span>
          <span class="text-gray-50 text-xs">
            {{ relativeDatetime(commentItem.sentAt) }}
          </span>
        </div>
        <p class="text-gray-90 whitespace-pre-line text-sm">
          {{ commentItem.comment }}
        </p>
        <div
          v-if="commentItem.file && commentItem.downloadUrl"
          class="flex items-center gap-1 text-sm"
        >
          <i class="pi pi-paperclip text-gray-50"></i>
          <a
            :href="commentItem.downloadUrl"
            target="_blank"
            class="text-blue-50 underline break-all"
          >
            {{ commentItem.file }}
          </a>
        </div>
      </div>
    </div>
  </Dialog>
</template>

<script setup>
import { ref, watch, computed } from "vue"
import { useNotification } from "../../composables/notification"
import { useI18n } from "vue-i18n"
import { useFormatDate } from "../../composables/formatDate"
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
  flags: {
    type: Object,
    default: () => ({ allowText: true }),
  },
})

const emit = defineEmits(["update:modelValue", "commentSent"])

const { t } = useI18n()
const notification = useNotification()
const visible = ref(false)
const comment = ref("")
const sendMail = ref(false)
const selectedFile = ref(null)
const qualification = ref(null)
const submitting = ref(false)
const route = useRoute()
const parentResourceNodeId = parseInt(route.params.node)
const securityStore = useSecurityStore()
const isEditor = securityStore.isCourseAdmin || securityStore.isTeacher
const isStudentView = route.query.isStudentView === "true"
const forceStudentView = !isEditor || isStudentView

const { relativeDatetime } = useFormatDate()
const comments = ref([])
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

const maxQualification = computed(() => props.item?.publicationParent?.qualification ?? null)

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
  if (submitting.value) return
  submitting.value = true

  const trimmed = comment.value.trim()
  const hasComment = trimmed.length > 0
  const hasFile = !!selectedFile.value
  const hasQualificationChange = qualification.value !== props.item.qualification

  if (!hasComment && !hasFile && !hasQualificationChange) {
    notification.showErrorNotification(t("Please add a comment, a grade or a file"))
    submitting.value = false
    return
  }

  if (!hasComment && !hasFile && hasQualificationChange) {
    try {
      await cStudentPublicationService.updateScore(props.item.iid, qualification.value)
      notification.showSuccessNotification(t("Score updated successfully"))
      emit("commentSent")
      close()
    } catch (e) {
      notification.showErrorNotification(e)
    } finally {
      submitting.value = false
    }
    return
  }

  try {
    const formData = new FormData()
    formData.append("submissionId", props.item.iid)
    formData.append("qualification", qualification.value ?? "")

    if (selectedFile.value) {
      formData.append("uploadFile", selectedFile.value)
    }

    if (hasComment) {
      formData.append("comment", trimmed)
    }

    await cStudentPublicationService.uploadComment(props.item.iid, parentResourceNodeId, formData, sendMail.value)

    notification.showSuccessNotification(t("Comment added successfully"))
    comments.value = await cStudentPublicationService.loadComments(props.item.iid)
    comment.value = ""
    selectedFile.value = null
    emit("commentSent")
  } catch (e) {
    notification.showErrorNotification(e)
  } finally {
    submitting.value = false
  }
}
</script>
