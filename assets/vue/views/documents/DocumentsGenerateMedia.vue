<template>
  <BaseToolbar>
    <BaseButton
      :label="t('Back')"
      icon="back"
      type="black"
      @click="router.back()"
    />
  </BaseToolbar>

  <div class="p-4 space-y-4">
    <h2 class="text-xl font-semibold">{{ t("Generate media") }}</h2>

    <!-- Permission / settings gate -->
    <div
      v-if="!canEdit"
      class="p-3 rounded border border-gray-200 text-sm"
    >
      {{ t("You do not have permission to generate AI media in this course.") }}
    </div>

    <div
      v-else-if="!aiHelpersEnabled"
      class="p-3 rounded border border-gray-200 text-sm"
    >
      {{ t("AI helpers are disabled at platform level.") }}
    </div>

    <div
      v-else-if="!imageGeneratorEnabled && !videoGeneratorEnabled"
      class="p-3 rounded border border-gray-200 text-sm"
    >
      {{ t("AI media generation is disabled in course settings.") }}
    </div>

    <div
      v-else-if="isLoadingCaps"
      class="text-sm text-gray-600"
    >
      {{ t("Loading...") }}
    </div>

    <div
      v-else-if="!canUseAnyType"
      class="p-3 rounded border border-gray-200 text-sm"
    >
      {{ t("No AI media providers available.") }}
    </div>

    <div
      v-else
      class="space-y-4"
    >
      <!-- Type (image/video) -->
      <div
        v-if="typeOptions.length > 1"
        class="space-y-1"
      >
        <label class="font-semibold text-sm">{{ t("Type") }}</label>
        <Dropdown
          v-model="selectedType"
          :options="typeOptions"
          optionLabel="label"
          optionValue="value"
          class="w-full"
        />
      </div>

      <!-- Provider -->
      <div
        v-if="providerOptions.length > 1"
        class="space-y-1"
      >
        <label class="font-semibold text-sm">{{ t("Provider") }}</label>
        <Dropdown
          v-model="selectedProvider"
          :options="providerOptions"
          optionLabel="label"
          optionValue="value"
          :placeholder="t('Select a provider')"
          class="w-full"
        />
      </div>

      <!-- Folder -->
      <div class="space-y-1">
        <label class="font-semibold text-sm">{{ t("Destination folder") }}</label>
        <Dropdown
          v-model="selectedFolderId"
          :options="folders"
          optionLabel="label"
          optionValue="value"
          :placeholder="t('Select a folder')"
          class="w-full"
        />
      </div>

      <!-- Name -->
      <div class="space-y-1">
        <label class="font-semibold text-sm">{{ t("File name") }}</label>
        <InputText
          v-model="fileName"
          class="w-full"
          :placeholder="t('Example: generated_media')"
        />
        <p class="text-xs text-gray-600">
          {{ t("The name is not added to the prompt; it is only used for saving the file.") }}
        </p>
      </div>

      <!-- Prompt -->
      <div class="space-y-1">
        <label class="font-semibold text-sm">{{ t("Prompt") }}</label>
        <textarea
          v-model="prompt"
          class="w-full border border-gray-300 rounded px-3 py-2 min-h-[140px]"
          :placeholder="t('Describe what you want to generate...')"
        />
      </div>

      <!-- Actions -->
      <div class="flex flex-wrap items-center gap-2">
        <BaseButton
          :label="isGenerating ? t('Generating...') : t('Generate')"
          icon="robot"
          type="primary"
          :disabled="!canGenerate || isGenerating || isSaving || isPollingVideoJob"
          @click="generate"
        />

        <BaseButton
          v-if="hasGeneratedResult"
          :label="isSaving ? t('Saving...') : t('Save to Documents')"
          icon="check"
          type="secondary"
          :disabled="!canAccept || isSaving || isGenerating || isPollingVideoJob"
          @click="acceptAndSave"
        />

        <span
          v-if="statusMessage"
          class="text-sm text-gray-700"
        >
          {{ statusMessage }}
        </span>
      </div>

      <!-- Video job status -->
      <div
        v-if="selectedType === 'video' && isPollingVideoJob"
        class="p-3 rounded border border-blue-200 bg-blue-50 text-sm"
      >
        <div class="font-semibold">{{ t("Video generation is in progress") }}</div>
        <div class="mt-1">
          {{ t("Job") }}: <code>{{ videoJobId }}</code>
          <span v-if="videoJobStatus">
            — {{ t("Status") }}: <strong>{{ videoJobStatus }}</strong></span
          >
        </div>
      </div>

      <!-- Preview -->
      <div
        v-if="previewUrl"
        class="space-y-2"
      >
        <h3 class="font-semibold">{{ t("Preview") }}</h3>

        <img
          v-if="selectedType === 'image'"
          :src="previewUrl"
          class="max-w-full rounded border border-gray-200"
          alt="Generated preview"
        />

        <video
          v-else
          :src="previewUrl"
          class="max-w-full rounded border border-gray-200"
          controls
        />
      </div>

      <!-- Revised prompt -->
      <div
        v-if="revisedPrompt"
        class="space-y-1"
      >
        <h3 class="font-semibold">{{ t("Modified prompt") }}</h3>
        <textarea
          :value="revisedPrompt"
          class="w-full border border-gray-300 rounded px-3 py-2 min-h-[120px] bg-gray-50"
          readonly
        />
      </div>

      <!-- Saved -->
      <div
        v-if="savedIri"
        class="p-3 rounded border border-green-200 bg-green-50 text-sm"
      >
        <div class="font-semibold">{{ t("Saved in Documents") }}</div>
        <div class="mt-1">
          {{ t("Document IRI") }}: <code>{{ savedIri }}</code>
        </div>
        <div class="mt-3">
          <BaseButton
            :label="t('Go to folder')"
            icon="folder-open"
            type="secondary"
            @click="goToFolder"
          />
        </div>
      </div>

      <!-- Provider used -->
      <div
        v-if="providerUsed"
        class="text-xs text-gray-600"
      >
        {{ t("Provider used") }}: <strong>{{ providerLabel(providerUsed) }}</strong>
      </div>

      <!-- Video note -->
      <div
        v-if="hasGeneratedResult && selectedType === 'video' && !canAccept && !isPollingVideoJob"
        class="p-3 rounded border border-yellow-200 bg-yellow-50 text-sm"
      >
        <div class="font-semibold">{{ t("This video result cannot be saved yet") }}</div>
        <div class="mt-1">
          {{
            t(
              "Saving is only supported when the provider returns base64 content. If you only received a URL, saving is disabled to avoid downloading in the browser.",
            )
          }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue"
import axios from "axios"
import { useRoute, useRouter } from "vue-router"
import { useI18n } from "vue-i18n"
import { useCidReq } from "../../composables/cidReq"
import { RESOURCE_LINK_PUBLISHED } from "../../constants/entity/resourcelink"
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import { usePlatformConfig } from "../../store/platformConfig"
import { useCourseSettings } from "../../store/courseSettingStore"
import { useSecurityStore } from "../../store/securityStore"
import { checkIsAllowedToEdit } from "../../composables/userPermissions"

const route = useRoute()
const router = useRouter()
const { t } = useI18n()
const { cid, sid, gid } = useCidReq()

const platformConfig = usePlatformConfig()
const courseSettingsStore = useCourseSettings()
const securityStore = useSecurityStore()

const isLoadingCaps = ref(false)

const hasImage = ref(false)
const hasVideo = ref(false)

const providersByType = ref({ image: [], video: [] })
const selectedType = ref("image")
const selectedProvider = ref(null)

const folders = ref([])
const selectedFolderId = ref(null)

const fileName = ref("")
const prompt = ref("")
const revisedPrompt = ref("")
const previewUrl = ref("")
const savedIri = ref("")
const providerUsed = ref("")

// Generated result kept in memory until teacher accepts it.
const generatedResult = ref(null)

const isGenerating = ref(false)
const isSaving = ref(false)
const statusMessage = ref("")

const rawCanEdit = ref(false)
const canEdit = computed(() => !!rawCanEdit.value)

// Video polling state (OpenAI video is job-based).
const isPollingVideoJob = ref(false)
const videoJobId = ref("")
const videoJobStatus = ref("")
let videoPollTimeoutHandle = null
let videoPollAttempts = 0

// Polling config (best effort defaults).
const VIDEO_POLL_INTERVAL_MS = 3000
const VIDEO_POLL_MAX_ATTEMPTS = 80

// Platform + course settings (same pattern as LP generator)
const aiHelpersEnabled = computed(() => {
  const v = String(platformConfig.getSetting("ai_helpers.enable_ai_helpers"))
  return v === "true"
})

const imageGeneratorEnabled = computed(() => {
  const v = String(courseSettingsStore?.getSetting?.("image_generator"))
  return v === "true"
})

const videoGeneratorEnabled = computed(() => {
  const v = String(courseSettingsStore?.getSetting?.("video_generator"))
  return v === "true"
})

function providerLabel(code) {
  const c = String(code || "")
    .toLowerCase()
    .trim()

  const map = {
    openai: "OpenAI",
    deepseek: "DeepSeek",
    grok: "Grok",
    mistral: "Mistral",
    gemini: "Gemini",
  }

  return map[c] || c.toUpperCase()
}

function normalizeProviderList(input) {
  // capabilities.types.image => string[]
  if (!Array.isArray(input)) return []
  return input
    .map((p) => String(p || "").trim())
    .filter(Boolean)
    .map((code) => ({
      code,
      name: providerLabel(code),
    }))
}

const canUseImage = computed(() => {
  return canEdit.value && aiHelpersEnabled.value && imageGeneratorEnabled.value && hasImage.value
})

const canUseVideo = computed(() => {
  return canEdit.value && aiHelpersEnabled.value && videoGeneratorEnabled.value && hasVideo.value
})

const canUseAnyType = computed(() => canUseImage.value || canUseVideo.value)

const typeOptions = computed(() => {
  const opts = []
  if (canUseImage.value) opts.push({ label: t("Image"), value: "image" })
  if (canUseVideo.value) opts.push({ label: t("Video"), value: "video" })
  return opts
})

const providerOptions = computed(() => {
  const list = providersByType.value?.[selectedType.value] || []
  return list.map((p) => ({ label: p.name, value: p.code }))
})

watch(
  () => selectedType.value,
  () => {
    // Reset generation state when switching type to avoid accidental saves.
    stopVideoPolling("Type changed by user.")
    generatedResult.value = null
    previewUrl.value = ""
    revisedPrompt.value = ""
    providerUsed.value = ""
    savedIri.value = ""
    statusMessage.value = ""

    const list = providersByType.value?.[selectedType.value] || []
    selectedProvider.value = list[0]?.code ?? null
  },
)

watch(
  () => selectedProvider.value,
  () => {
    // Provider changes should cancel any existing polling to avoid mixing providers/jobs.
    if (isPollingVideoJob.value) {
      stopVideoPolling("Provider changed by user.")
      statusMessage.value = t("Video polling was stopped because the provider changed.")
    }
  },
)

const canGenerate = computed(() => {
  return (
    canUseAnyType.value &&
    !!selectedType.value &&
    !!selectedProvider.value &&
    !!selectedFolderId.value &&
    !!fileName.value?.trim() &&
    !!prompt.value?.trim()
  )
})

const hasGeneratedResult = computed(() => !!generatedResult.value)

const canAccept = computed(() => {
  // We only support saving when we have base64 content.
  // This avoids frontend downloading URLs.
  if (!generatedResult.value) return false
  if (!generatedResult.value.is_base64) return false
  if (!generatedResult.value.content) return false
  return true
})

function normalizeResourceNodeId(value) {
  if (value == null) return null
  if (typeof value === "number") return value

  if (typeof value === "string") {
    const iriMatch = value.match(/\/api\/resource_nodes\/(\d+)/)
    if (iriMatch) return Number(iriMatch[1])
    if (/^\d+$/.test(value)) return Number(value)
  }

  return null
}

function sanitizeFilenameBase(name) {
  // Keep it predictable for filesystem + UX.
  const raw = String(name || "").trim()
  if (!raw) return "generated_media"

  return (
    raw
      .replace(/[\\/:"*?<>|]+/g, "_") // Windows forbidden chars + slashes
      .replace(/\s+/g, "_")
      .replace(/_+/g, "_")
      .replace(/^_+|_+$/g, "")
      .slice(0, 80) || "generated_media"
  )
}

function guessExtension(contentType, type) {
  const ct = String(contentType || "").toLowerCase()

  if (type === "image") {
    if (ct.includes("png")) return "png"
    if (ct.includes("jpeg") || ct.includes("jpg")) return "jpg"
    if (ct.includes("webp")) return "webp"
    if (ct.includes("gif")) return "gif"
    return "png"
  }

  if (ct.includes("webm")) return "webm"
  if (ct.includes("mp4")) return "mp4"
  return "mp4"
}

function ensureFilenameWithExtension(name, ext) {
  const base = sanitizeFilenameBase(name)
  if (/\.[a-z0-9]+$/i.test(base)) return base
  return `${base}.${ext}`
}

function base64ToFile(base64, filename, mime) {
  const binary = atob(base64)
  const bytes = new Uint8Array(binary.length)

  for (let i = 0; i < binary.length; i++) {
    bytes[i] = binary.charCodeAt(i)
  }

  const blob = new Blob([bytes], { type: mime })
  return new File([blob], filename, { type: mime })
}

function buildResourceLinkList() {
  return JSON.stringify([{ gid, sid, cid, visibility: RESOURCE_LINK_PUBLISHED }])
}

async function saveToDocuments(file, type) {
  const formData = new FormData()

  // Must match CreateDocumentFileAction schema.
  formData.append("uploadFile", file)

  // Title without extension usually looks better in Documents listings.
  const titleNoExt = String(file.name).replace(/\.[^/.]+$/i, "")
  formData.append("title", titleNoExt)
  formData.append("filetype", "file")

  formData.append("parentResourceNodeId", String(selectedFolderId.value))
  formData.append("resourceLinkList", buildResourceLinkList())
  formData.append("fileExistsOption", "rename")

  const response = await axios.post("/api/documents", formData, {
    headers: { "Content-Type": "multipart/form-data" },
  })

  const data = response?.data || {}
  savedIri.value = String(data?.["@id"] || data?.id || "")

  return data
}

async function fetchFolders(nodeId = null) {
  const startId = normalizeResourceNodeId(nodeId ?? route.params.node ?? route.query.node)

  // In this route (/resources/document/:node/) startId should exist, but keep a safe fallback.
  const safeStart = startId || null
  const foldersList = safeStart ? [{ label: t("Documents"), value: safeStart }] : []

  if (!safeStart) {
    console.warn("[AI Media] No valid start node id found for folders.")
    return foldersList
  }

  try {
    const queue = [{ id: safeStart, path: "" }]
    const maxDepth = 5
    let depth = 0

    while (queue.length > 0 && depth < maxDepth) {
      const current = queue.shift()
      const currentNodeId = normalizeResourceNodeId(current?.id)

      if (!currentNodeId) {
        depth++
        continue
      }

      const response = await axios.get("/api/documents", {
        params: {
          loadNode: 1,
          filetype: ["folder"],
          "resourceNode.parent": currentNodeId,
          cid,
          sid,
          gid,
          page: 1,
          itemsPerPage: 200,
        },
      })

      const members = response.data?.["hydra:member"] || []
      for (const folder of members) {
        const folderNodeId =
          normalizeResourceNodeId(folder?.resourceNode?.id) ?? normalizeResourceNodeId(folder?.resourceNodeId)

        if (!folderNodeId) continue

        const fullPath = `${current.path}/${folder.title}`.replace(/^\/+/, "")
        foldersList.push({ label: fullPath, value: folderNodeId })
        queue.push({ id: folderNodeId, path: fullPath })
      }

      depth++
    }
  } catch (e) {
    console.error("[AI Media] Failed to fetch folders:", e)
  }

  return foldersList
}

async function loadCapabilities() {
  isLoadingCaps.value = true

  try {
    const { data } = await axios.get("/ai/capabilities")

    // Raw availability from backend (providers exist).
    hasImage.value = !!data?.has?.image
    hasVideo.value = !!data?.has?.video

    providersByType.value = {
      image: normalizeProviderList(data?.types?.image),
      video: normalizeProviderList(data?.types?.video),
    }

    // Pick a type that is actually usable under settings + capabilities.
    const usableImage = canUseImage.value && providersByType.value.image.length > 0
    const usableVideo = canUseVideo.value && providersByType.value.video.length > 0

    if (!usableImage && usableVideo) {
      selectedType.value = "video"
    } else {
      selectedType.value = "image"
    }

    const list = providersByType.value?.[selectedType.value] || []
    selectedProvider.value = list[0]?.code ?? null
  } catch (e) {
    console.error("[AI Media] Failed to load capabilities:", e)
    hasImage.value = false
    hasVideo.value = false
    providersByType.value = { image: [], video: [] }
  } finally {
    isLoadingCaps.value = false
  }
}
function stopVideoPolling(reason = "") {
  if (videoPollTimeoutHandle) {
    clearTimeout(videoPollTimeoutHandle)
    videoPollTimeoutHandle = null
  }

  if (isPollingVideoJob.value) {
    console.info("[AI Media] Video polling stopped.", reason ? `Reason: ${reason}` : "")
  }

  isPollingVideoJob.value = false
  videoPollAttempts = 0
  videoJobId.value = ""
  videoJobStatus.value = ""
}

async function pollVideoJobOnce(jobId, providerCode) {
  // Server-side polling endpoint.
  // Expected: { success: true, text?: string, result: { id, status, is_base64, content, url, content_type, error? } }.
  const response = await axios.get(`/ai/video_job/${encodeURIComponent(jobId)}`, {
    params: {
      ai_provider: providerCode || null,
    },
  })

  return response?.data
}

async function pollVideoJob(jobId, providerCode) {
  if (!isPollingVideoJob.value) return

  videoPollAttempts += 1
  if (videoPollAttempts > VIDEO_POLL_MAX_ATTEMPTS) {
    console.warn("[AI Media] Video polling reached maximum attempts.")
    statusMessage.value = t("Video generation is taking too long. Please try again later.")
    stopVideoPolling("Max attempts reached.")
    return
  }

  try {
    const data = await pollVideoJobOnce(jobId, providerCode)

    if (!data?.success) {
      const msg = String(data?.text || "")
      console.warn("[AI Media] Video job status request failed:", msg)
      statusMessage.value = msg || t("Failed to check video status.")
      scheduleNextVideoPoll(jobId, providerCode)
      return
    }

    const result = data?.result || {}
    const status = String(result.status || "")
    videoJobStatus.value = status

    // Prefer a provider/server error message when available.
    const serverError = String(result.error || data?.text || "").trim()

    // Update stored result (job-based).
    generatedResult.value = {
      ...(generatedResult.value || {}),
      id: jobId,
      status,
      content: String(result.content || ""),
      url: String(result.url || ""),
      is_base64: !!result.is_base64,
      content_type: String(result.content_type || "video/mp4"),
      error: serverError || "",
    }

    if (isTerminalVideoStatus(status)) {
      if (isSuccessVideoStatus(status)) {
        const isBase64 = !!result.is_base64
        const content = String(result.content || "")
        const url = String(result.url || "")
        const contentType = String(result.content_type || "video/mp4")

        if (isBase64 && content) {
          previewUrl.value = `data:${contentType};base64,${content}`
          statusMessage.value = t("Video generated successfully. You can now save it.")
        } else if (!isBase64 && url) {
          previewUrl.value = url
          statusMessage.value = t(
            "Video generated successfully, but saving is disabled because the provider returned a URL.",
          )
        } else {
          statusMessage.value = t("Video generation completed, but no playable content was returned.")
        }
      } else {
        statusMessage.value = serverError ? serverError : t("Video generation failed.")
      }

      stopVideoPolling("Terminal status reached.")
      return
    }

    statusMessage.value = t("Waiting for the video to be ready...")
    scheduleNextVideoPoll(jobId, providerCode)
  } catch (e) {
    console.error("[AI Media] Video polling failed:", e)
    statusMessage.value = t("Failed to check video status.")
    scheduleNextVideoPoll(jobId, providerCode)
  }
}

function isTerminalVideoStatus(status) {
  const s = String(status || "")
    .toLowerCase()
    .trim()
  return ["completed", "succeeded", "done", "failed", "canceled", "cancelled", "error"].includes(s)
}

function isSuccessVideoStatus(status) {
  const s = String(status || "")
    .toLowerCase()
    .trim()
  return ["completed", "succeeded", "done"].includes(s)
}

function scheduleNextVideoPoll(jobId, providerCode) {
  videoPollTimeoutHandle = setTimeout(async () => {
    await pollVideoJob(jobId, providerCode)
  }, VIDEO_POLL_INTERVAL_MS)
}

function startVideoPolling(jobId, providerCode) {
  stopVideoPolling("Restart polling with a new job.")
  isPollingVideoJob.value = true
  videoJobId.value = String(jobId || "")
  videoJobStatus.value = ""
  videoPollAttempts = 0

  console.info("[AI Media] Video polling started.", `Job: ${jobId}`, providerCode ? `Provider: ${providerCode}` : "")
  scheduleNextVideoPoll(jobId, providerCode)
}

async function generate() {
  stopVideoPolling("New generation requested.")
  statusMessage.value = ""
  revisedPrompt.value = ""
  previewUrl.value = ""
  savedIri.value = ""
  providerUsed.value = ""
  generatedResult.value = null
  videoJobId.value = ""
  videoJobStatus.value = ""

  if (!canGenerate.value) {
    statusMessage.value = t("Please complete all fields.")
    return
  }

  // Extra guard if user toggled type manually.
  if (selectedType.value === "image" && !canUseImage.value) {
    statusMessage.value = t("Image generation is not enabled for this course.")
    return
  }
  if (selectedType.value === "video" && !canUseVideo.value) {
    statusMessage.value = t("Video generation is not enabled for this course.")
    return
  }

  isGenerating.value = true

  try {
    const endpoint = selectedType.value === "video" ? "/ai/generate_video" : "/ai/generate_image"

    const { data } = await axios.post(
      endpoint,
      {
        n: 1,
        language: "en",
        prompt: prompt.value,
        tool: "document",
        ai_provider: selectedProvider.value,
      },
      { headers: { "Content-Type": "application/json" } },
    )

    if (!data?.success) {
      const msg = String(data?.text || "")
      console.warn("[AI Media] Generation failed:", msg)
      statusMessage.value = msg ? msg : t("Generation failed.")
      return
    }

    providerUsed.value = String(data?.provider_used || selectedProvider.value || "")
    const result = data?.result || {}

    const content = String(result.content || "")
    const url = String(result.url || "")
    const id = String(result.id || "")
    const status = String(result.status || "")
    const isBase64 = !!result.is_base64
    const contentType = String(result.content_type || (selectedType.value === "video" ? "video/mp4" : "image/png"))
    revisedPrompt.value = String(result.revised_prompt || "")

    generatedResult.value = {
      id: id || null,
      status: status || null,
      content,
      url,
      is_base64: isBase64,
      content_type: contentType,
    }

    // Preview logic:
    // - Image: backend should return base64 (preferred).
    // - Video: can be base64, url, or job-based id/status.
    if (isBase64 && content) {
      previewUrl.value = `data:${contentType};base64,${content}`
      statusMessage.value = t("Generated successfully. Review the preview and save when ready.")
      return
    }

    if (!isBase64 && url) {
      previewUrl.value = url
      statusMessage.value = t("Generated successfully. Saving is disabled because the provider returned a URL.")
      return
    }

    // Job-based video: start polling until we get content or url.
    if (selectedType.value === "video" && id) {
      statusMessage.value = t("Video generation started. Waiting for the result...")
      startVideoPolling(id, providerUsed.value || selectedProvider.value)
      return
    }

    console.warn("[AI Media] AI returned an empty or unsupported payload:", result)
    statusMessage.value = t("AI returned an empty response.")
    generatedResult.value = null
  } catch (e) {
    console.error("[AI Media] Generate failed:", e)
    statusMessage.value = t("Generation failed.")
  } finally {
    isGenerating.value = false
  }
}

async function acceptAndSave() {
  statusMessage.value = ""

  if (!generatedResult.value) {
    statusMessage.value = t("Nothing to save yet.")
    return
  }

  // We only save base64 results to avoid frontend downloading URLs.
  if (!generatedResult.value.is_base64 || !generatedResult.value.content) {
    statusMessage.value = t("This provider did not return base64 content. Please try another provider.")
    return
  }

  isSaving.value = true

  try {
    const ext = guessExtension(generatedResult.value.content_type, selectedType.value)
    const finalName = ensureFilenameWithExtension(fileName.value, ext)

    const mime = generatedResult.value.content_type || (selectedType.value === "video" ? "video/mp4" : "image/png")

    const file = base64ToFile(generatedResult.value.content, finalName, mime)

    const savedDoc = await saveToDocuments(file, selectedType.value)

    // Build a human-readable path for success message.
    const folderLabel =
      folders.value.find((f) => Number(f.value) === Number(selectedFolderId.value))?.label || t("Documents")

    // Prefer backend title because it may have renamed due to conflicts.
    const savedTitle =
      String(savedDoc?.resourceNode?.title || savedDoc?.title || "").trim() ||
      String(file.name).replace(/\.[^/.]+$/i, "")

    const savedPath = `${folderLabel}/${savedTitle}`.replace(/^\/+/, "")

    // Redirect to folder list and show toast there (via query params).
    router.push({
      name: "DocumentsList",
      params: { node: selectedFolderId.value },
      query: {
        ...route.query,
        cid,
        sid,
        gid,
        ai_saved: "1",
        ai_saved_path: savedPath,
        ai_saved_iri: String(savedDoc?.["@id"] || ""),
      },
    })
  } catch (e) {
    console.error("[AI Media] Save failed:", e)
    statusMessage.value = t("Saving failed.")
  } finally {
    isSaving.value = false
  }
}

function goToFolder() {
  router.push({
    name: "DocumentsList",
    params: { node: selectedFolderId.value },
    query: { ...route.query, cid, sid, gid },
  })
}

onMounted(async () => {
  // Load course settings (same pattern as LP generator usage).
  try {
    await courseSettingsStore.loadCourseSettings(cid, sid)
  } catch (e) {
    console.error("[AI Media] loadCourseSettings failed:", e)
  }

  // Permission check (teacher/admin).
  try {
    let allowed = await checkIsAllowedToEdit(true, true, true, false)
    const roles = securityStore.user?.roles ?? []

    if (!allowed && Array.isArray(roles) && (roles.includes("ROLE_ADMIN") || roles.includes("ROLE_GLOBAL_ADMIN"))) {
      allowed = true
    }

    rawCanEdit.value = !!allowed
  } catch (e) {
    console.error("[AI Media] Permission check failed:", e)
    rawCanEdit.value = false
  }

  // Only load capabilities if the feature is usable by settings + permissions.
  if (canEdit.value && aiHelpersEnabled.value && (imageGeneratorEnabled.value || videoGeneratorEnabled.value)) {
    await loadCapabilities()
  }

  folders.value = await fetchFolders()
  selectedFolderId.value = normalizeResourceNodeId(route.params.node) || folders.value[0]?.value || null

  // Ensure selected type is valid when only one is available.
  if (typeOptions.value.length === 1) {
    selectedType.value = typeOptions.value[0].value
  }
})

onBeforeUnmount(() => {
  // Prevent orphan polling timers when leaving the page.
  stopVideoPolling("Component unmounted.")
})
</script>
