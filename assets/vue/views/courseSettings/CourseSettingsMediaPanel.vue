<template>
  <div>
    <div
      v-if="mode === 'picture'"
      class="rounded-lg border border-gray-25 bg-gray-10 p-4"
    >
      <div class="mb-4 flex items-center gap-2 font-semibold text-gray-800">
        <BaseIcon icon="file-image" />
        <span>{{ t("Course picture") }}</span>
      </div>

      <div class="grid gap-5 lg:grid-cols-[1fr_320px]">
        <div class="space-y-4">
          <CoursePictureUploader
            :key="pictureUploaderKey"
            :endpoint="pictureEndpoint"
            @error="handleUploaderError"
            @uploaded="handlePictureUploaded"
          />

          <div
            v-if="integrations.ai?.canGeneratePicture"
            class="space-y-3"
          >
            <BaseButton
              icon="robot"
              :label="t('Generate with AI')"
              type="primary"
              @click="showAiPrompt = !showAiPrompt"
            />

            <div
              v-if="showAiPrompt"
              class="rounded-lg border border-gray-25 bg-white p-4"
            >
              <BaseTextArea
                id="course_picture_ai_prompt"
                v-model="aiPrompt"
                :label="t('Describe the course picture to generate')"
                name="course_picture_ai_prompt"
                rows="3"
              />
              <div class="mt-3 flex justify-end">
                <BaseButton
                  icon="robot"
                  :is-loading="busyAction === 'generate-picture'"
                  :label="t('Generate picture')"
                  type="success"
                  @click="generatePicture"
                />
              </div>
            </div>
          </div>
        </div>

        <div class="rounded-lg border border-gray-25 bg-white p-3">
          <div class="mb-3 flex items-center justify-between gap-2">
            <span class="font-semibold text-gray-800">{{ t("Preview") }}</span>
            <BaseButton
              v-if="media.hasCustomPicture"
              icon="delete"
              :is-loading="busyAction === 'delete-picture'"
              :label="t('Delete picture')"
              only-icon
              type="danger"
              @click="deletePicture"
            />
          </div>

          <img
            v-if="media.pictureUrl"
            :alt="t('Course picture')"
            class="aspect-video w-full rounded-lg border border-gray-25 object-cover"
            :src="media.pictureUrl"
          />
          <div
            v-else
            class="flex aspect-video items-center justify-center rounded-lg border border-dashed border-gray-40 bg-gray-10 text-gray-500"
          >
            <BaseIcon
              icon="file-image"
              size="large"
            />
          </div>
        </div>
      </div>
    </div>

    <div
      v-else-if="mode === 'watermark' && media.watermarkEnabled"
      class="rounded-lg border border-gray-25 bg-gray-10 p-4"
    >
      <div class="mb-4 flex items-center gap-2 font-semibold text-gray-800">
        <BaseIcon icon="file-image" />
        <span>{{ t("PDF watermark") }}</span>
      </div>

      <div class="grid gap-4 md:grid-cols-[240px_1fr]">
        <div>
          <img
            v-if="media.watermarkExists"
            :alt="t('PDF watermark')"
            class="max-h-48 rounded-lg border border-gray-25 bg-white object-contain"
            :src="media.watermarkUrl"
          />
          <p
            v-else
            class="rounded-lg border border-dashed border-gray-40 bg-white p-6 text-center text-gray-500"
          >
            {{ t("No watermark image") }}
          </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
          <input
            ref="watermarkInput"
            accept="image/jpeg,image/png,image/gif,image/webp"
            class="hidden"
            name="watermark_file"
            type="file"
            @change="uploadWatermark"
          />
          <BaseButton
            icon="upload"
            :is-loading="busyAction === 'upload-watermark'"
            :label="t('Upload watermark')"
            type="success"
            @click="watermarkInput?.click()"
          />
          <BaseButton
            v-if="media.watermarkExists"
            icon="delete"
            :is-loading="busyAction === 'delete-watermark'"
            :label="t('Delete watermark')"
            type="danger"
            @click="deleteWatermark"
          />
        </div>
      </div>
    </div>

    <div
      v-else-if="mode === 'legal' && integrations.courseLegal?.enabled"
      class="rounded-lg border border-gray-25 bg-gray-10 p-4"
    >
      <div class="mb-4 flex items-center gap-2 font-semibold text-gray-800">
        <BaseIcon icon="file-text" />
        <span>{{ t("Course legal agreement file") }}</span>
      </div>
      <div class="flex flex-wrap items-center gap-2">
        <input
          ref="legalFileInput"
          class="hidden"
          name="course_legal_file"
          type="file"
          @change="uploadLegalFile"
        />
        <BaseButton
          icon="upload"
          :is-loading="busyAction === 'upload-legal-file'"
          :label="t('Upload agreement file')"
          type="success"
          @click="legalFileInput?.click()"
        />
        <BaseButton
          v-if="media.courseLegalFileExists"
          icon="download"
          :label="media.courseLegalFileName || t('Download agreement file')"
          type="primary"
          :to-url="legalFileUrl"
        />
        <BaseButton
          v-if="media.courseLegalFileExists"
          icon="delete"
          :is-loading="busyAction === 'delete-legal-file'"
          :label="t('Delete agreement file')"
          type="danger"
          @click="deleteLegalFile"
        />
      </div>
    </div>

    <div
      v-else-if="
        mode === 'certificate' && integrations.customCertificate?.enabled && values.customcertificate_mode === 'course'
      "
      class="rounded-lg border border-gray-25 bg-gray-10 p-4"
    >
      <div class="mb-4 flex items-center gap-2 font-semibold text-gray-800">
        <BaseIcon icon="gradebook" />
        <span>{{ t("Custom certificate images") }}</span>
      </div>
      <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div
          v-for="field in certificateMediaFields"
          :key="field.key"
          class="rounded-lg border border-gray-25 bg-white p-4"
        >
          <p class="mb-3 font-semibold text-gray-700">{{ t(field.label) }}</p>
          <img
            v-if="values[`customcertificate_${field.key}`]"
            :alt="t(field.label)"
            class="mb-3 h-28 w-full rounded-lg border border-gray-25 object-contain"
            :src="certificateMediaUrl(field.key)"
          />
          <div class="flex flex-wrap gap-2">
            <input
              :ref="(element) => registerCertificateInput(field.key, element)"
              accept="image/jpeg,image/png,image/gif,image/webp"
              class="hidden"
              :name="`customcertificate_${field.key}`"
              type="file"
              @change="uploadCertificateMedia(field.key, $event)"
            />
            <BaseButton
              icon="upload"
              only-icon
              size="small"
              :label="t('Upload')"
              type="success"
              @click="certificateInputs[field.key]?.click()"
            />
            <BaseButton
              v-if="values[`customcertificate_${field.key}`]"
              icon="delete"
              only-icon
              size="small"
              :label="t('Delete')"
              type="danger"
              @click="deleteCertificateMedia(field.key)"
            />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from "vue"
import { useI18n } from "vue-i18n"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import BaseTextArea from "../../components/basecomponents/BaseTextArea.vue"
import courseSettingsService from "../../services/courseSettingsService"
import CoursePictureUploader from "./CoursePictureUploader.vue"

const { t } = useI18n()

const props = defineProps({
  courseId: { type: Number, required: true },
  mode: { type: String, required: true },
  integrations: { type: Object, required: true },
  media: { type: Object, required: true },
  params: { type: Object, required: true },
  values: { type: Object, required: true },
})

const emit = defineEmits(["refresh", "message", "error"])

const busyAction = ref("")
const aiPrompt = ref("")
const showAiPrompt = ref(false)
const watermarkInput = ref(null)
const legalFileInput = ref(null)
const certificateInputs = ref({})
const pictureUploaderKey = ref(0)

const certificateMediaFields = [
  { key: "logo_left", label: "Left logo" },
  { key: "logo_center", label: "Center logo" },
  { key: "logo_right", label: "Right logo" },
  { key: "seal", label: "Seal" },
  { key: "signature1", label: "Signature 1" },
  { key: "signature2", label: "Signature 2" },
  { key: "signature3", label: "Signature 3" },
  { key: "signature4", label: "Signature 4" },
  { key: "background", label: "Background" },
]

const pictureEndpoint = computed(() => {
  const query = new URLSearchParams(props.params).toString()

  return `/api/course-settings/picture${query ? `?${query}` : ""}`
})

const legalFileUrl = computed(() => courseSettingsService.courseLegalFileUrl(props.params))

function handlePictureUploaded() {
  pictureUploaderKey.value += 1
  emit("message", t("Course picture updated"))
  emit("refresh")
}

function handleUploaderError(error) {
  emit("error", error?.message || t("The course picture could not be uploaded"))
}

async function deletePicture() {
  await runAction("delete-picture", async () => {
    await courseSettingsService.deletePicture(props.params)
    emit("message", t("Course picture deleted"))
    emit("refresh")
  })
}

async function generatePicture() {
  if (!aiPrompt.value.trim()) {
    emit("error", t("Enter a description for the image"))

    return
  }

  await runAction("generate-picture", async () => {
    const response = await courseSettingsService.generatePicture(aiPrompt.value.trim(), props.courseId)
    const result = response?.result || {}
    const base64 = result.content || response?.text || ""
    if (!base64) {
      throw new Error(response?.text || t("The image generator returned an empty response"))
    }
    const file = base64ToFile(base64, result.content_type || "image/png", "generated-course-picture.png")
    await courseSettingsService.uploadPicture(file, props.params)
    aiPrompt.value = ""
    emit("message", t("Course picture generated"))
    emit("refresh")
  })
}

async function uploadWatermark(event) {
  const file = event.target.files?.[0]
  if (!file) return

  await runAction("upload-watermark", async () => {
    await courseSettingsService.uploadWatermark(file, props.params)
    event.target.value = ""
    emit("message", t("Watermark updated"))
    emit("refresh")
  })
}

async function deleteWatermark() {
  await runAction("delete-watermark", async () => {
    await courseSettingsService.deleteWatermark(props.params)
    emit("message", t("Watermark deleted"))
    emit("refresh")
  })
}

async function uploadLegalFile(event) {
  const file = event.target.files?.[0]
  if (!file) return

  await runAction("upload-legal-file", async () => {
    await courseSettingsService.uploadCourseLegalFile(file, props.params)
    event.target.value = ""
    emit("message", t("Agreement file updated"))
    emit("refresh")
  })
}

async function deleteLegalFile() {
  await runAction("delete-legal-file", async () => {
    await courseSettingsService.deleteCourseLegalFile(props.params)
    emit("message", t("Agreement file deleted"))
    emit("refresh")
  })
}

function registerCertificateInput(field, element) {
  if (element) {
    certificateInputs.value[field] = element
  }
}

async function uploadCertificateMedia(field, event) {
  const file = event.target.files?.[0]
  if (!file) return

  await runAction(`upload-certificate-${field}`, async () => {
    await courseSettingsService.uploadCertificateMedia(field, file, props.params)
    event.target.value = ""
    emit("message", t("Certificate image updated"))
    emit("refresh")
  })
}

async function deleteCertificateMedia(field) {
  await runAction(`delete-certificate-${field}`, async () => {
    await courseSettingsService.deleteCertificateMedia(field, props.params)
    emit("message", t("Certificate image deleted"))
    emit("refresh")
  })
}

function certificateMediaUrl(field) {
  return courseSettingsService.certificateMediaUrl(field, props.params)
}

async function runAction(action, callback) {
  busyAction.value = action
  try {
    await callback()
  } catch (error) {
    emit(
      "error",
      error?.response?.data?.detail || error?.response?.data?.message || error?.message || t("Unexpected error"),
    )
  } finally {
    busyAction.value = ""
  }
}

function base64ToFile(value, mimeType, filename) {
  const normalized = value.includes(",") ? value.split(",").pop() : value
  const binary = window.atob(normalized)
  const bytes = new Uint8Array(binary.length)

  for (let index = 0; index < binary.length; index += 1) {
    bytes[index] = binary.charCodeAt(index)
  }

  return new File([bytes], filename, { type: mimeType })
}
</script>
