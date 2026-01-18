<template>
  <Dialog
    v-model:visible="visible"
    modal
    :header="t('Comments')"
    :style="{ width: '700px' }"
    @hide="onHide"
  >
    <div class="space-y-4">
      <div class="bg-gray-10 p-3 rounded">
        <h4 class="font-bold text-md">
          {{ props.item.publicationParent?.title || t("Original assignment") }}
        </h4>
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

        <iframe
          v-if="isFullHtmlDocument"
          class="w-full min-h-[260px] border border-gray-200 rounded bg-white"
          sandbox=""
          :srcdoc="submissionSrcDoc"
        />

        <div
          v-else-if="isHtmlFragment"
          class="text-sm text-gray-50 prose max-w-none"
          v-html="submissionHtml"
        />

        <div
          v-else
          class="text-sm text-gray-50 whitespace-pre-wrap"
          v-text="submissionText"
        />
      </div>

      <!-- AI Task Grader -->
      <div
        v-if="canUseAiTaskGrader"
        class="bg-white border rounded p-3 space-y-3"
      >
        <div class="flex items-center justify-between gap-2">
          <div class="flex items-center gap-2">
            <i class="mdi mdi-robot-outline text-gray-600 text-lg" />
            <span class="font-semibold text-sm">{{ t("AI feedback") }}</span>
          </div>

          <Button
            :label="aiBusy ? t('Generate') + '…' : t('Generate')"
            :disabled="!aiCanGenerate"
            icon="pi pi-bolt"
            class="p-button-sm"
            @click="runAiTaskGrader"
          />
        </div>

        <!-- What AI will analyze -->
        <div class="text-xs text-gray-600 space-y-1">
          <div class="font-medium text-gray-700">What will be sent to the AI:</div>
          <ul class="list-disc pl-5 space-y-1">
            <li>Assignment title + instructions</li>
            <li v-if="submissionHasText">Student submission (text)</li>
            <li v-else>Student submission (text): none</li>

            <li v-if="submissionHasFile">
              Student attachment: <b>{{ submissionFilename }}</b>
              <span v-if="submissionFileSupportedForAi"> (supported)</span>
              <span v-else> (not supported for document analysis)</span>
            </li>
            <li v-else>Student attachment: none</li>
          </ul>
        </div>

        <!-- Warnings / guidance -->
        <div
          v-if="aiBlockingReason"
          class="rounded border border-red-200 bg-red-50 p-2 text-xs text-red-800"
        >
          {{ aiBlockingReason }}
        </div>

        <div
          v-else-if="aiWarning"
          class="rounded border border-yellow-200 bg-yellow-50 p-2 text-xs text-yellow-900"
        >
          {{ aiWarning }}
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
          <div class="md:col-span-1">
            <label class="block text-xs font-medium mb-1">AI provider</label>
            <select
              v-model="aiProvider"
              class="w-full rounded border border-gray-300 px-3 py-2 text-sm"
              :disabled="aiBusy || providers.length === 0"
            >
              <option
                v-for="p in providers"
                :key="p"
                :value="p"
              >
                {{ p }}
              </option>
            </select>
            <p
              v-if="providers.length === 0"
              class="text-xs text-red-600 mt-1"
            >
              No text AI providers available.
            </p>
          </div>

          <div class="md:col-span-2">
            <label class="block text-xs font-medium mb-1">
              {{ t("Prompt") }}
            </label>
            <Textarea
              v-model="aiPrompt"
              class="w-full"
              rows="3"
              :disabled="aiBusy"
              @input="aiPromptDirty = true"
            />
            <p class="text-xs text-gray-500 mt-1">
              {{ t("Tip") }}: {{ t("You can edit the prompt before asking for feedback.") }}
            </p>

            <div
              v-if="aiUsedMode"
              class="text-xs text-gray-600 mt-1"
            >
              Mode used: <b>{{ aiUsedMode }}</b>
            </div>

            <div
              v-if="aiLastError"
              class="text-xs text-red-700 mt-1"
            >
              {{ aiLastError }}
            </div>
          </div>
        </div>

        <div
          v-if="aiFeedback"
          class="space-y-2"
        >
          <label class="block text-xs font-medium">
            {{ t("Result (you can apply it to the comment)") }}
          </label>

          <Textarea
            v-model="aiFeedback"
            class="w-full resize-none"
            rows="6"
            :disabled="aiBusy"
          />

          <div class="flex flex-wrap items-center gap-2">
            <Button
              :label="t('Apply to comment')"
              icon="pi pi-arrow-down"
              class="p-button-sm"
              :disabled="aiBusy || !aiFeedback.trim()"
              @click="applyAiFeedbackToComment"
            />

            <Button
              :label="t('Clear')"
              icon="pi pi-times"
              class="p-button-sm p-button-text"
              :disabled="aiBusy"
              @click="clearAiFeedback"
            />

            <span
              v-if="aiSuggestedScore !== null"
              class="text-xs text-gray-600"
            >
              {{ t("Suggested score") }}: <b>{{ aiSuggestedScore }}</b>
            </span>

            <Button
              v-if="aiSuggestedScore !== null && !forceStudentView"
              :label="t('Apply score')"
              icon="pi pi-check"
              class="p-button-sm p-button-secondary"
              type="button"
              :disabled="aiBusy"
              @click.stop.prevent="applyAiScore"
            />
          </div>
        </div>
      </div>

      <Textarea
        id="assignment-comment"
        v-model="comment"
        :placeholder="t('Write your comment...')"
        class="w-full"
        rows="5"
      />

      <div class="flex flex-col gap-2">
        <label
          for="qualification"
          class="text-sm font-medium"
        >
          {{ t("Score") }}
        </label>

        <template v-if="!forceStudentView">
          <InputNumber
            v-model="qualification"
            inputId="qualification"
            class="w-full"
            :min="0"
            :max="maxQualification ?? undefined"
            :step="0.1"
            :minFractionDigits="0"
            :maxFractionDigits="1"
            :useGrouping="false"
            :locale="primeLocale"
          />

          <small
            v-if="maxHelpText"
            class="text-xs text-gray-50"
          >
            {{ maxHelpText }}
          </small>
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
          id="assignment-attach-correction"
          type="file"
          @change="handleFileUpload"
        />
        <small class="text-xs text-gray-50">
          Optional correction attachment (this is not the student's submission).
        </small>
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
          id="assignment-cancel"
          @click="close"
        />
        <Button
          :label="t('Send')"
          :disabled="submitting"
          id="assignment-send"
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
import InputNumber from "primevue/inputnumber"

// Settings gating (same style as glossary)
import { usePlatformConfig } from "../../store/platformConfig"
import { useCourseSettings } from "../../store/courseSettingStore"
import { useCidReqStore } from "../../store/cidReq"
import { storeToRefs } from "pinia"

const props = defineProps({
  modelValue: Boolean,
  item: Object,
  flags: {
    type: Object,
    default: () => ({ allowText: true }),
  },
})

const emit = defineEmits(["update:modelValue", "commentSent"])

const { t, locale } = useI18n()
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

// ----------------------------------
// Submission rendering helpers
// ----------------------------------
const submissionRaw = computed(() => String(props.item?.description || ""))

const isFullHtmlDocument = computed(() => {
  const s = submissionRaw.value.trim().toLowerCase()
  return s.startsWith("<!doctype html") || s.startsWith("<html")
})

const isHtmlFragment = computed(() => {
  const s = submissionRaw.value
  return !isFullHtmlDocument.value && /<\/?[a-z][\s\S]*>/i.test(s)
})

const submissionSrcDoc = computed(() => submissionRaw.value || "")
const submissionHtml = computed(() => submissionRaw.value || "")
const submissionText = computed(() => submissionRaw.value || "")

// ----------------------------------
// Student attachment detection (best-effort)
// ----------------------------------
// NOTE: Different APIs can expose the filename differently. We try common fields.
// Keep this defensive to avoid breaking existing behavior.
const submissionFilename = computed(() => {
  const v =
    props.item?.title ||
    props.item?.file ||
    props.item?.fileName ||
    props.item?.filename ||
    props.item?.documentName ||
    props.item?.originalName ||
    ""
  return String(v || "").trim()
})

const submissionExt = computed(() => {
  const name = submissionFilename.value
  const idx = name.lastIndexOf(".")
  if (idx <= 0) return ""
  return name.substring(idx + 1).toLowerCase()
})

const submissionHasFile = computed(() => submissionFilename.value.length > 0 && submissionExt.value.length > 0)

const submissionHasText = computed(() => {
  const txt = submissionRaw.value
  return typeof txt === "string" && txt.trim().length > 0
})

// Keep this list aligned with backend isSupportedForDocumentProcess().
// We intentionally keep it short in UI for clarity.
const AI_SUPPORTED_DOC_EXTS = ["pdf", "txt", "md", "markdown", "html", "htm", "json", "xml", "yaml", "yml", "csv"]

const AI_IMAGE_EXTS = ["jpg", "jpeg", "png", "gif", "webp", "bmp", "tif", "tiff"]

const submissionFileIsImage = computed(() => AI_IMAGE_EXTS.includes(submissionExt.value))
const submissionFileSupportedForAi = computed(() => AI_SUPPORTED_DOC_EXTS.includes(submissionExt.value))

// ----------------------------------
// Score helpers
// ----------------------------------
const primeLocale = computed(() => {
  const l = String(locale.value || "en").toLowerCase()
  if (l.startsWith("fr")) return "fr-FR"
  if (l.startsWith("es")) return "es-ES"
  if (l.startsWith("pt")) return "pt-BR"
  return "en-US"
})

const maxQualification = computed(() => {
  const raw = props.item?.publicationParent?.qualification
  if (raw === null || raw === undefined || raw === "") return null

  const n = Number(raw)
  if (!Number.isFinite(n) || n <= 0) return null

  return n
})

const maxHelpText = computed(() => {
  if (maxQualification.value === null) return null
  return `${t("Max score")}: ${maxQualification.value}`
})

// ----------------------------------
// AI Task Grader state
// ----------------------------------
const platform = usePlatformConfig()
const courseSettingsStore = useCourseSettings()
const cidReqStore = useCidReqStore()
const { course, session } = storeToRefs(cidReqStore)

const providers = ref([])
const aiProvider = ref("")
const aiPrompt = ref("")
const aiPromptDirty = ref(false)
const aiFeedback = ref("")
const aiSuggestedScore = ref(null)
const aiBusy = ref(false)

// Extra UI/debug helpers (do not affect existing logic)
const aiUsedMode = ref("") // "text" | "document" (from backend response)
const aiLastError = ref("") // last backend/provider error message

const aiHelpersEnabled = computed(() => {
  return String(platform.getSetting("ai_helpers.enable_ai_helpers")) === "true"
})

const taskGraderEnabled = computed(() => {
  const v = courseSettingsStore?.getSetting?.("task_grader")
  return String(v) === "true"
})

const canUseAiTaskGrader = computed(() => {
  return !!(!forceStudentView && aiHelpersEnabled.value && taskGraderEnabled.value)
})

const aiBlockingReason = computed(() => {
  // Block generation if there is nothing usable for AI:
  // - No text submission
  // - And file exists but is not supported for document processing (images, docx, etc.)
  if (!canUseAiTaskGrader.value) return ""

  const hasText = submissionHasText.value
  const hasFile = submissionHasFile.value

  if (hasText) return ""
  if (!hasFile) {
    return "AI cannot generate feedback because the student submission contains no text and no attachment was detected."
  }

  if (submissionFileIsImage.value) {
    return "AI cannot generate feedback because the student submission is an image. Ask the student to upload a PDF or paste text."
  }

  if (!submissionFileSupportedForAi.value) {
    return `AI cannot generate feedback because the attached file type (.${submissionExt.value}) is not supported. Ask the student to upload a PDF or paste text.`
  }

  return ""
})

const aiWarning = computed(() => {
  if (!canUseAiTaskGrader.value) return ""

  // Non-blocking warnings
  if (submissionHasFile.value && submissionFileSupportedForAi.value) {
    return "Tip: PDF works best for document analysis. Text files are accepted but may provide less context than a well-formatted PDF."
  }

  if (submissionHasText.value && submissionHasFile.value && !submissionFileSupportedForAi.value) {
    return `Note: Attachment (.${submissionExt.value}) will not be analyzed as a document. Only the text submission will be used.`
  }

  return ""
})

const aiCanGenerate = computed(() => {
  if (!canUseAiTaskGrader.value) return false
  if (aiBusy.value) return false
  if (providers.value.length === 0) return false
  if (!aiProvider.value) return false
  if (!aiPrompt.value.trim()) return false
  if (aiBlockingReason.value) return false
  return true
})

async function loadCourseSettingsIfPossible() {
  const courseId = course.value?.id
  const sessionId = session.value?.id

  if (!courseId) return

  try {
    await courseSettingsStore.loadCourseSettings(courseId, sessionId)
  } catch (err) {
    console.error("[Assignments][AI] loadCourseSettings FAILED:", err)
  }
}

async function loadAiProviders() {
  try {
    const res = await cStudentPublicationService.getAiTextProviders()
    providers.value = res?.providers || []
    aiProvider.value = providers.value[0] || ""
  } catch (err) {
    console.warn("[Assignments][AI] Failed to load providers", err)
    providers.value = []
    aiProvider.value = ""
  }
}

async function loadDefaultAiPrompt() {
  try {
    const res = await cStudentPublicationService.getAiTaskGraderDefaultPrompt(props.item?.iid, {
      language: locale.value || "en",
    })

    if (!aiPromptDirty.value && res?.prompt) {
      aiPrompt.value = res.prompt
    }
  } catch (err) {
    console.warn("[Assignments][AI] Failed to load default prompt", err)
    if (!aiPromptDirty.value) {
      aiPrompt.value =
        "You are an assignment grader.\nProvide constructive feedback and actionable improvements.\nAt the end, add a final line exactly like: SCORE: <number> (0 to N/A).\nReturn plain text only."
    }
  }
}

async function runAiTaskGrader() {
  if (!canUseAiTaskGrader.value || aiBusy.value) return

  // Front-end safety check (backend still validates)
  if (aiBlockingReason.value) {
    notification.showErrorNotification(aiBlockingReason.value)
    return
  }

  aiBusy.value = true
  aiSuggestedScore.value = null
  aiLastError.value = ""
  aiUsedMode.value = ""

  try {
    const res = await cStudentPublicationService.aiTaskGrade(props.item?.iid, {
      ai_provider: aiProvider.value,
      language: locale.value || "en",
      mode: "auto",
      prompt: aiPrompt.value,
    })

    if (!res?.success) {
      const msg = String(res?.message || "AI task grading failed.")
      aiLastError.value = msg
      notification.showErrorNotification(msg)
      return
    }

    aiFeedback.value = String(res.feedback || "").trim()
    aiSuggestedScore.value = res.suggestedScore ?? null
    aiUsedMode.value = String(res.mode || "").trim()

    notification.showSuccessNotification(t("Generate"))
  } catch (err) {
    console.warn("[Assignments][AI] Task grader request failed", err)
    aiLastError.value = "AI task grading failed."
    notification.showErrorNotification("AI task grading failed.")
  } finally {
    aiBusy.value = false
  }
}

function applyAiFeedbackToComment() {
  const txt = aiFeedback.value.trim()
  if (!txt) return
  comment.value = txt
}

function applyAiScore() {
  const v = Number(aiSuggestedScore.value)

  console.log("[Assignments][AI] Apply score clicked", {
    suggestedScore: aiSuggestedScore.value,
    parsed: v,
  })

  if (!Number.isFinite(v)) {
    notification.showErrorNotification("Invalid suggested score from AI.")
    return
  }

  qualification.value = v
  notification.showSuccessNotification(t("Score updated successfully"))

  // Focus the score input (best effort)
  requestAnimationFrame(() => {
    const el = document.getElementById("qualification")
    el?.scrollIntoView?.({ behavior: "smooth", block: "center" })
    el?.focus?.()
  })
}

function clearAiFeedback() {
  aiFeedback.value = ""
  aiSuggestedScore.value = null
  aiUsedMode.value = ""
  aiLastError.value = ""
}

// ----------------------------------
// Modal lifecycle
// ----------------------------------
watch(
  () => props.modelValue,
  async (newVal) => {
    visible.value = newVal

    if (!newVal) return

    comment.value = ""
    sendMail.value = false
    selectedFile.value = null
    aiFeedback.value = ""
    aiSuggestedScore.value = null
    aiUsedMode.value = ""
    aiLastError.value = ""
    aiPromptDirty.value = false

    qualification.value =
      props.item?.qualification === null || props.item?.qualification === undefined || props.item?.qualification === ""
        ? null
        : Number(props.item.qualification)

    comments.value = await cStudentPublicationService.loadComments(props.item.iid)

    // Load settings/providers/prompt only when the modal opens
    await loadCourseSettingsIfPossible()

    if (canUseAiTaskGrader.value) {
      await loadAiProviders()
      await loadDefaultAiPrompt()
    }
  },
)

// If course/session changes while modal is open, refresh course settings.
watch(
  () => [course.value?.id, session.value?.id],
  async () => {
    if (!visible.value) return
    await loadCourseSettingsIfPossible()
  },
)

function onHide() {
  emit("update:modelValue", false)
}

function close() {
  emit("update:modelValue", false)
}

function handleFileUpload(event) {
  selectedFile.value = event.target.files?.[0] || null
}

async function submit() {
  if (submitting.value) return
  submitting.value = true

  const trimmed = comment.value.trim()
  const hasComment = trimmed.length > 0
  const hasFile = !!selectedFile.value

  const currentQ =
    qualification.value === null || qualification.value === undefined || qualification.value === ""
      ? null
      : Number(qualification.value)

  const originalQ =
    props.item?.qualification === null || props.item?.qualification === undefined || props.item?.qualification === ""
      ? null
      : Number(props.item.qualification)

  const hasQualificationChange = currentQ !== originalQ

  if (!hasComment && !hasFile && !hasQualificationChange) {
    notification.showErrorNotification(t("Please add a comment, a grade or a file"))
    submitting.value = false
    return
  }

  if (!hasComment && !hasFile && hasQualificationChange) {
    try {
      await cStudentPublicationService.updateScore(props.item.iid, currentQ)
      notification.showSuccessNotification(t("Score updated successfully"))
      emit("commentSent")
      close()
    } catch (e) {
      console.warn("[Assignments][CorrectAndRateModal] Failed to update score", e)
      notification.showErrorNotification(e)
    } finally {
      submitting.value = false
    }
    return
  }

  try {
    const formData = new FormData()
    formData.append("submissionId", props.item.iid)
    formData.append("qualification", currentQ === null ? "" : String(currentQ))

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
    close()
  } catch (e) {
    console.warn("[Assignments][CorrectAndRateModal] Failed to submit comment/grade/file", e)
    notification.showErrorNotification(e)
  } finally {
    submitting.value = false
  }
}
</script>
