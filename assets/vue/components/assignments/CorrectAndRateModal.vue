<template>
  <Dialog
    v-model:visible="visible"
    modal
    :header="t('Comments')"
    :style="{ width: '900px', maxWidth: '95vw' }"
    @hide="onHide"
  >
    <div class="space-y-5">
      <!-- Assignment header -->
      <div class="rounded-lg border bg-gray-10 p-4">
        <h4 class="text-base font-semibold text-gray-900">
          {{ props.item.publicationParent?.title || t("Original assignment") }}
        </h4>
        <div
          class="prose max-w-none text-sm text-gray-700 mt-2"
          v-html="safeParentDescription"
        />
      </div>

      <!-- Student submission (text) -->
      <div
        v-if="flags.allowText && props.item.description"
        class="rounded-lg border bg-white p-4"
      >
        <div class="flex items-center justify-between gap-2 mb-2">
          <h5 class="text-sm font-semibold text-gray-900">
            {{ t("Student's submission") }}
          </h5>

          <span
            v-if="submissionHasFile"
            class="inline-flex items-center gap-2 rounded-full border bg-gray-10 px-3 py-1 text-xs text-gray-700"
            :title="submissionFilename"
          >
            <i class="mdi mdi-paperclip text-gray-600"></i>
            <span class="truncate max-w-[280px]">{{ submissionFilename }}</span>
          </span>
        </div>

        <iframe
          v-if="isFullHtmlDocument"
          class="w-full min-h-[260px] border border-gray-200 rounded bg-white"
          sandbox=""
          :srcdoc="submissionSrcDocSafe"
        />

        <div
          v-else-if="isHtmlFragment"
          class="prose max-w-none text-sm text-gray-50"
          v-html="submissionHtmlSafe"
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
        class="rounded-lg border bg-white"
      >
        <!-- Header -->
        <div class="flex items-center justify-between gap-3 border-b px-4 py-3">
          <div class="flex items-center gap-2">
            <i class="mdi mdi-robot-outline text-gray-700 text-lg" />
            <div class="leading-tight">
              <div class="text-sm font-semibold text-gray-900">{{ t("AI feedback") }}</div>
              <div class="text-xs text-gray-500">
                {{ t("AI can draft feedback and a suggested score before you send it.") }}
              </div>
            </div>
          </div>

          <Button
            :label="aiBusy ? t('Generate') + '…' : t('Generate')"
            :disabled="!aiCanGenerate"
            icon="mdi mdi-lightning-bolt"
            class="p-button-sm"
            @click="runAiTaskGrader"
          />
        </div>

        <!-- Body -->
        <div class="p-4 space-y-4">
          <!-- What will be sent -->
          <div class="rounded-lg border bg-gray-10 p-3 text-xs text-gray-700">
            <div class="flex items-center gap-2 font-medium text-gray-800 mb-2">
              <i class="mdi mdi-information text-gray-600"></i>
              <span>{{ t("What will be sent to the AI") }}</span>
            </div>

            <ul class="list-disc pl-5 space-y-1">
              <li>{{ t("Assignment title + instructions") }}</li>
              <li v-if="submissionHasText">{{ t("Student submission (text)") }}</li>
              <li v-else>{{ t("Student submission (text): none") }}</li>

              <li v-if="submissionHasFile">
                {{ t("Student attachment") }}: <b>{{ submissionFilename }}</b>
                <span v-if="submissionFileSupportedForAi"> ({{ t("supported") }})</span>
                <span v-else>({{ t("not supported for document analysis") }})</span>
              </li>
              <li v-else>{{ t("Student attachment: none") }}</li>
            </ul>
          </div>

          <!-- Blocking / warning -->
          <div
            v-if="aiBlockingReason"
            class="rounded-lg border border-red-200 bg-red-50 p-3 text-xs text-red-800"
          >
            {{ aiBlockingReason }}
          </div>

          <div
            v-else-if="aiWarning"
            class="rounded-lg border border-yellow-200 bg-yellow-50 p-3 text-xs text-yellow-900"
          >
            {{ aiWarning }}
          </div>

          <!-- Provider + Prompt -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="md:col-span-1">
              <label class="block text-xs font-semibold text-gray-700 mb-1">{{ t("AI provider") }}</label>

              <select
                v-model="aiProvider"
                class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm"
                :disabled="aiBusy || providerOptions.length === 0"
              >
                <option
                  v-for="p in providerOptions"
                  :key="p.key"
                  :value="p.key"
                >
                  {{ p.label }}
                </option>
              </select>

              <p
                v-if="providerOptions.length === 0"
                class="text-xs text-red-600 mt-1"
              >
                {{ t("No text AI providers available.") }}
              </p>
            </div>

            <div class="md:col-span-2">
              <div class="flex items-center justify-between gap-2 mb-1">
                <label class="block text-xs font-semibold text-gray-700">
                  {{ t("Prompt") }}
                </label>

                <span
                  v-if="aiUsedMode"
                  class="text-xs text-gray-500"
                >
                  {{ t("Mode") }}: <b class="text-gray-700">{{ aiUsedMode }}</b>
                </span>
              </div>

              <Textarea
                v-model="aiPrompt"
                class="w-full"
                rows="4"
                :disabled="aiBusy"
                @input="aiPromptDirty = true"
              />

              <div class="mt-1 flex flex-wrap items-center justify-between gap-2">
                <p class="text-xs text-gray-500">
                  {{ t("Tip") }}: {{ t("You can edit the prompt before asking for feedback.") }}
                </p>

                <p
                  v-if="aiLastError"
                  class="text-xs text-red-700"
                >
                  {{ aiLastError }}
                </p>
              </div>
            </div>
          </div>

          <!-- Result -->
          <div
            v-if="aiFeedback"
            class="rounded-lg border bg-white p-3 space-y-2"
          >
            <div class="flex items-center justify-between gap-2">
              <label class="text-xs font-semibold text-gray-700">
                {{ t("Result (you can apply it to the comment)") }}
              </label>

              <span
                v-if="aiSuggestedScore !== null"
                class="inline-flex items-center gap-2 rounded-full border bg-gray-10 px-3 py-1 text-xs text-gray-700"
              >
                <span>{{ t("Suggested score") }}:</span>
                <b class="text-gray-900">{{ aiSuggestedScore }}</b>
              </span>
            </div>

            <Textarea
              v-model="aiFeedback"
              class="w-full resize-none"
              rows="7"
              :disabled="aiBusy"
            />

            <div class="flex flex-wrap items-center gap-2 pt-1">
              <Button
                :label="t('Apply to comment')"
                icon="mdi mdi-arrow-down"
                class="p-button-sm"
                :disabled="aiBusy || !aiFeedback.trim()"
                @click="applyAiFeedbackToComment"
              />

              <Button
                :label="t('Clear')"
                icon="mdi mdi-close"
                class="p-button-sm p-button-text"
                :disabled="aiBusy"
                @click="clearAiFeedback"
              />

              <Button
                v-if="aiSuggestedScore !== null && !forceStudentView"
                :label="t('Apply score')"
                icon="mdi mdi-check"
                class="p-button-sm p-button-secondary"
                type="button"
                :disabled="aiBusy"
                @click.stop.prevent="applyAiScore"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- Teacher comment -->
      <div class="space-y-2">
        <Textarea
          id="assignment-comment"
          v-model="comment"
          :placeholder="t('Write your comment...')"
          class="w-full"
          rows="5"
        />

        <!-- AI-assisted flag for this correction (comment/file) -->
        <div
          v-if="canShowAiAssistedToggle"
          class="flex flex-wrap items-center gap-2 rounded-lg border bg-gray-10 px-3 py-2"
        >
          <BaseCheckbox
            id="ai-assisted-flag"
            :modelValue="aiAssistedRaw"
            :label="t('AI-assisted')"
            name=""
            @update:modelValue="onAiAssistedUserToggle"
          />

          <span class="text-xs text-gray-600">
            {{ t("This decision is saved with the correction (ExtraField) and cannot be changed later.") }}
          </span>
        </div>
      </div>

      <!-- Score -->
      <div class="flex flex-col gap-2">
        <label
          for="qualification"
          class="text-sm font-medium text-gray-100"
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
          <span class="border p-2 rounded bg-gray-10 text-sm">
            {{ qualification ?? t("Not graded yet") }}
          </span>
        </template>
      </div>

      <!-- Attach correction file -->
      <div class="flex flex-col gap-2">
        <label class="text-sm font-medium text-gray-900">
          {{ t("Attach file (optional)") }}
        </label>

        <input
          id="assignment-attach-correction"
          type="file"
          @change="handleFileUpload"
        />

        <small class="text-xs text-gray-600">
          {{ t("Optional correction attachment (this is not the student's submission).") }}
        </small>
      </div>

      <!-- Send mail -->
      <div class="flex items-center gap-2">
        <BaseCheckbox
          v-if="props.flags.allowText"
          id="sendmail"
          v-model="sendMail"
          :label="t('Send mail to student')"
          name=""
        />
      </div>

      <!-- Actions -->
      <div class="flex justify-end gap-2 pt-1">
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

    <!-- Comments history -->
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
          <div class="flex items-center gap-2">
            <span class="font-semibold text-sm">
              {{ commentItem.user?.fullName || commentItem.user?.fullname || "Unknown User" }}
            </span>

            <span
              v-if="commentItem.ai_assisted"
              class="inline-flex items-center gap-1 rounded-full border border-gray-200 bg-gray-10 px-2 py-0.5 text-xs text-gray-700"
              :title="t('AI-assisted')"
            >
              <span aria-hidden="true">🤖</span>
              <span>{{ t("AI") }}</span>
            </span>
          </div>

          <span class="text-gray-500 text-xs">
            {{ relativeDatetime(commentItem.sentAt) }}
          </span>
        </div>

        <p class="text-gray-900 whitespace-pre-line text-sm">
          {{ commentItem.comment }}
        </p>
        <div
          v-if="commentItem.file && commentItem.downloadUrl"
          class="flex items-center gap-1 text-sm"
        >
          <i class="mdi mdi-paperclip text-gray-600"></i>
          <a
            :href="commentItem.downloadUrl"
            target="_blank"
            class="text-blue-600 underline break-all"
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
import DOMPurify from "dompurify"

// Settings gating
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
const parentResourceNodeId = parseInt(route.params.node ?? "0", 10)
const securityStore = useSecurityStore()
const isEditor = securityStore.isCourseAdmin || securityStore.isTeacher
const isStudentView = route.query.isStudentView === "true"
const forceStudentView = !isEditor || isStudentView

const { relativeDatetime } = useFormatDate()
const comments = ref([])
const aiAssistedRaw = ref(false)
const aiAssistedDirty = ref(false)
const canShowAiAssistedToggle = computed(() => !forceStudentView)

function onAiAssistedUserToggle(val) {
  aiAssistedDirty.value = true
  aiAssistedRaw.value = !!val
}

function autoMarkAiAssisted() {
  if (!aiAssistedDirty.value) {
    aiAssistedRaw.value = true
  }
}

// ----------------------------
// Sanitization helpers
// ----------------------------
const sanitizeHtml = (html, options = {}) => {
  return DOMPurify.sanitize(html ?? "", {
    ADD_ATTR: ["target", "rel"],
    ...options,
  })
}

const safeParentDescription = computed(() => {
  return sanitizeHtml(props.item?.publicationParent?.description ?? "")
})

// ----------------------------
// Submission rendering helpers
// ----------------------------
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

const submissionSrcDocSafe = computed(() => {
  return sanitizeHtml(submissionSrcDoc.value || "", { WHOLE_DOCUMENT: true })
})

const submissionHtmlSafe = computed(() => {
  return sanitizeHtml(submissionHtml.value || "")
})

// ----------------------------
// Student attachment detection (best-effort)
// ----------------------------
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

// Keep this list aligned with backend document support.
const AI_SUPPORTED_DOC_EXTS = ["pdf", "txt", "md", "markdown", "html", "htm", "json", "xml", "yaml", "yml", "csv"]
const AI_IMAGE_EXTS = ["jpg", "jpeg", "png", "gif", "webp", "bmp", "tif", "tiff"]

const submissionFileIsImage = computed(() => AI_IMAGE_EXTS.includes(submissionExt.value))
const submissionFileSupportedForAi = computed(() => AI_SUPPORTED_DOC_EXTS.includes(submissionExt.value))

// ----------------------------
// Score helpers
// ----------------------------
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

// ----------------------------
// AI Task Grader state
// ----------------------------
const platform = usePlatformConfig()
const courseSettingsStore = useCourseSettings()
const cidReqStore = useCidReqStore()
const { course, session } = storeToRefs(cidReqStore)

const providers = ref([])
const aiProvider = ref("") // provider key
const aiPrompt = ref("")
const aiPromptDirty = ref(false)
const aiFeedback = ref("")
const aiSuggestedScore = ref(null)
const aiBusy = ref(false)

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

// Normalize providers from API:
// - Old format: ["openai", "mistral"]
// - New format: [{key,label}, ...]
const providerOptions = computed(() => {
  const raw = Array.isArray(providers.value) ? providers.value : []
  return raw
    .map((p) => {
      if (typeof p === "string") {
        const s = p.trim()
        return s ? { key: s, label: s } : null
      }
      const key = String(p?.key || p?.name || p?.id || "").trim()
      const label = String(p?.label || p?.name || p?.key || key).trim()
      if (!key) return null
      return { key, label: label || key }
    })
    .filter(Boolean)
})

const aiBlockingReason = computed(() => {
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
  if (providerOptions.value.length === 0) return false
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
    aiProvider.value = providerOptions.value[0]?.key || ""
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

  // Front-end safety check (backend still validates).
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

    // Auto-mark only if user did not manually change the checkbox.
    autoMarkAiAssisted()

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

  // Auto-mark only if user did not manually change the checkbox.
  autoMarkAiAssisted()
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

  // Auto-mark only if user did not manually change the checkbox.
  autoMarkAiAssisted()

  // Focus the score input (best effort).
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

// ----------------------------
// Modal lifecycle
// ----------------------------
watch(
  () => props.modelValue,
  async (newVal) => {
    visible.value = newVal
    if (!newVal) return

    comment.value = ""
    sendMail.value = false
    selectedFile.value = null

    // Reset AI-related state each time the modal opens.
    aiFeedback.value = ""
    aiSuggestedScore.value = null
    aiUsedMode.value = ""
    aiLastError.value = ""
    aiPromptDirty.value = false

    // Reset AI-assisted decision for this new correction.
    aiAssistedRaw.value = false
    aiAssistedDirty.value = false

    qualification.value =
      props.item?.qualification === null || props.item?.qualification === undefined || props.item?.qualification === ""
        ? null
        : Number(props.item.qualification)

    comments.value = await cStudentPublicationService.loadComments(props.item.iid)

    // Load settings/providers/prompt only when the modal opens.
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
      const formData = new FormData()
      formData.append("submissionId", props.item.iid)
      formData.append("qualification", currentQ === null ? "" : String(currentQ))
      formData.append("ai_assisted_raw", aiAssistedRaw.value ? "1" : "0")

      await cStudentPublicationService.uploadComment(props.item.iid, parentResourceNodeId, formData, false)

      notification.showSuccessNotification(t("Score updated successfully"))
      emit("commentSent")
      close()
    } catch (e) {
      console.warn("[Assignments][CorrectAndRateModal] Failed to update score through correction endpoint", e)
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

    formData.append("ai_assisted_raw", aiAssistedRaw.value ? "1" : "0")

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
