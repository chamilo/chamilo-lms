<template>
  <LayoutFormGeneric>
    <template #header>
      <BaseIcon icon="robot" />
      {{ t("Generate glossary terms") }}
    </template>

    <div class="space-y-5">
      <!-- Permission / helper -->
      <div class="rounded-2xl border border-gray-25 bg-white p-4 text-sm">
        <div
          v-if="!canEditGlossary"
          class="text-danger"
        >
          {{ t("You do not have permission to generate glossary terms.") }}
        </div>

        <div
          v-else
          class="space-y-2"
        >
          <div class="flex flex-wrap items-center gap-2">
            <span
              class="inline-flex items-center gap-2 rounded-full border border-gray-25 bg-gray-15 px-3 py-1 text-xs"
            >
              <span class="font-semibold">1</span> {{ t("Generate") }}
            </span>
            <i
              class="mdi mdi-arrow-right text-gray-40 text-base"
              aria-hidden="true"
            ></i>
            <span
              class="inline-flex items-center gap-2 rounded-full border border-gray-25 bg-gray-15 px-3 py-1 text-xs"
            >
              <span class="font-semibold">2</span> {{ t("Import") }}
            </span>
          </div>

          <p class="text-gray-60 text-xs">
            Generate glossary suggestions with AI, review/edit the result, then import it into the course glossary.
          </p>
        </div>
      </div>

      <!-- Settings -->
      <div class="rounded-2xl border border-gray-25 bg-white p-4">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
          <div class="md:col-span-1">
            <label class="block text-sm font-medium mb-1">
              {{ t("Generate") }}
            </label>

            <div class="flex items-center gap-2">
              <input
                v-model.number="n"
                type="number"
                min="1"
                max="200"
                class="w-full rounded-xl border border-gray-25 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-25"
                :disabled="isBusy || !canEditGlossary"
              />
              <button
                type="button"
                class="shrink-0 rounded-xl border border-gray-25 px-3 py-2 text-sm hover:bg-gray-15 disabled:opacity-50 disabled:cursor-not-allowed"
                :disabled="isBusy || !canEditGlossary"
                @click="resetToDefaults"
                title="Reset prompt to default"
              >
                <i
                  class="mdi mdi-refresh"
                  aria-hidden="true"
                ></i>
              </button>
            </div>

            <p class="text-xs text-gray-60 mt-1">1–200</p>
          </div>

          <div class="md:col-span-2">
            <label class="block text-sm font-medium mb-1">AI provider</label>

            <select
              v-model="aiProvider"
              class="w-full rounded-xl border border-gray-25 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-25 disabled:opacity-50 disabled:cursor-not-allowed"
              :disabled="isBusy || !canEditGlossary || providers.length === 0"
            >
              <option
                v-for="p in providers"
                :key="p.key"
                :value="p.key"
              >
                {{ p.label }}
              </option>
            </select>

            <p
              v-if="providers.length === 0"
              class="text-xs text-danger mt-1"
            >
              {{ noProviderMessage }}
            </p>
          </div>
        </div>
      </div>

      <!-- Document source -->
      <div class="rounded-2xl border border-gray-25 bg-white p-4">
        <div class="space-y-3">
          <div>
            <label class="block text-sm font-medium mb-1">
              {{ t("Source document") }}
            </label>

            <select
              v-model="selectedResourceFileId"
              class="w-full rounded-xl border border-gray-25 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-25 disabled:opacity-50 disabled:cursor-not-allowed"
              :disabled="isBusy || !canEditGlossary"
            >
              <option value="0">
                {{ t("Use the current course context") }}
              </option>
              <option
                v-for="doc in documentSources"
                :key="doc.resource_file_id"
                :value="String(doc.resource_file_id)"
              >
                {{ doc.title }} — {{ doc.filename }}
              </option>
            </select>

            <p
              v-if="documentSourcesLoading"
              class="text-xs text-gray-60 mt-1"
            >
              {{ t("Loading documents...") }}
            </p>
            <p
              v-else-if="documentSources.length === 0"
              class="text-xs text-gray-60 mt-1"
            >
              {{ t("No compatible PDF or TXT document was found in this course.") }}
            </p>
            <p
              v-else
              class="text-xs text-gray-60 mt-1"
            >
              {{ t("Select a PDF or TXT document to generate terms only from that document.") }}
            </p>
          </div>

          <div
            v-if="selectedDocument"
            class="rounded-xl border border-yellow-300 bg-yellow-50 p-3 text-sm text-yellow-800"
          >
            <div class="font-semibold">
              {{ t("Confidentiality warning") }}
            </div>
            <div class="mt-1">
              {{
                t(
                  "If the selected AI provider is not a sovereign service, the selected document content may be sent to an external service. Do not continue with confidential information unless this is allowed by your organization.",
                )
              }}
            </div>
          </div>
        </div>
      </div>

      <!-- Prompt -->
      <div class="rounded-2xl border border-gray-25 bg-white p-4">
        <div class="flex items-center justify-between gap-3 mb-2">
          <label class="block text-sm font-medium">Prompt</label>

          <div class="flex items-center gap-2">
            <button
              type="button"
              class="rounded-xl border border-gray-25 px-3 py-2 text-xs hover:bg-gray-15 disabled:opacity-50 disabled:cursor-not-allowed"
              :disabled="isBusy || !canEditGlossary || !!selectedDocument"
              @click="applyDefaultPrompt(true)"
              title="Restore default prompt"
            >
              Restore default
            </button>
          </div>
        </div>

        <textarea
          v-model="prompt"
          rows="6"
          class="w-full rounded-xl border border-gray-25 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-25 disabled:opacity-50 disabled:cursor-not-allowed"
          :disabled="isBusy || !canEditGlossary || !!selectedDocument"
          @input="promptDirty = true"
        />
        <p
          v-if="selectedDocument"
          class="text-xs text-gray-60 mt-2"
        >
          {{ t("When a document is selected, the prompt is fixed so the generation uses only that document content.") }}
        </p>
        <p
          v-else
          class="text-xs text-gray-60 mt-2"
        >
          Tip: keep the requested format (term line, definition next line, blank line between items) for best imports.
        </p>

        <div class="mt-4 flex flex-wrap gap-2">
          <BaseButton
            :label="t('Back')"
            icon="back"
            type="black"
            :disabled="isBusy"
            @click="goBack"
          />
          <BaseButton
            :label="t('Generate')"
            icon="send"
            type="success"
            :disabled="!canGenerate"
            @click="runGeneration"
          />
        </div>
      </div>

      <!-- Result -->
      <div
        v-if="generatedText !== null"
        class="rounded-2xl border border-gray-25 bg-white p-4"
      >
        <div class="flex items-center justify-between gap-3 mb-2">
          <label class="block text-sm font-medium"> Result (edit before import) </label>

          <span class="text-xs text-gray-60"> Detected terms: {{ detectedCount }} </span>
        </div>

        <textarea
          v-model="generatedText"
          rows="12"
          class="w-full rounded-xl border border-gray-25 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-25 disabled:opacity-50 disabled:cursor-not-allowed"
          :disabled="isBusy || !canEditGlossary"
        />

        <div class="mt-4 flex flex-wrap items-center gap-2">
          <BaseButton
            :label="t('Import')"
            icon="import"
            type="success"
            :disabled="!canImport"
            @click="importTerms"
          />

          <span
            v-if="importProgress"
            class="text-xs text-gray-60"
          >
            Importing {{ importProgress.current }}/{{ importProgress.total }}…
          </span>
        </div>

        <div
          v-if="importReport"
          class="mt-4 rounded-2xl border border-gray-25 bg-gray-15 p-4 text-sm"
        >
          <div class="grid grid-cols-1 gap-2 md:grid-cols-3">
            <div><b>Created:</b> {{ importReport.created }}</div>
            <div><b>Skipped:</b> {{ importReport.skipped }}</div>
            <div><b>Errors:</b> {{ importReport.errors.length }}</div>
          </div>

          <div
            v-if="importReport.errors.length"
            class="mt-3"
          >
            <ul class="list-disc ml-5 space-y-1">
              <li
                v-for="(e, idx) in importReport.errors"
                :key="idx"
              >
                {{ e }}
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </LayoutFormGeneric>
</template>
<script setup>
import { computed, onMounted, ref, watch } from "vue"
import { useRoute, useRouter } from "vue-router"
import { useI18n } from "vue-i18n"
import LayoutFormGeneric from "../../components/layout/LayoutFormGeneric.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import { RESOURCE_LINK_PUBLISHED } from "../../constants/entity/resourcelink"
import glossaryService from "../../services/glossaryService"
import { useNotification } from "../../composables/notification"
import { useSecurityStore } from "../../store/securityStore"
import { useIsAllowedToEdit } from "../../composables/userPermissions"
import { usePlatformConfig } from "../../store/platformConfig"
import { useCidReqStore } from "../../store/cidReq"
import { storeToRefs } from "pinia"

const route = useRoute()
const router = useRouter()
const { t, locale } = useI18n()
const notification = useNotification()
const securityStore = useSecurityStore()
const platform = usePlatformConfig()

const cidReqStore = useCidReqStore()
const { course } = storeToRefs(cidReqStore)

const { isAllowedToEdit } = useIsAllowedToEdit({ tutor: true, coach: true, sessionCoach: true })

const canEditGlossary = computed(() => {
  const inSession = !!route.query.sid
  const basePermission = isAllowedToEdit.value || (securityStore.isCurrentTeacher && !inSession)
  return basePermission && !platform.isStudentViewActive
})

const n = ref(15)

/**
 * Providers are normalized to:
 * [{ key: "openai", label: "openai (gpt-4o)" }, ...]
 */
const textProviders = ref([])
const documentProcessProviders = ref([])
const aiProvider = ref("") // Provider key only

const documentSources = ref([])
const documentSourcesLoading = ref(false)
const selectedResourceFileId = ref("0")

const selectedDocument = computed(() => {
  const id = String(selectedResourceFileId.value || "0")
  if ("0" === id) {
    return null
  }

  return documentSources.value.find((doc) => String(doc.resource_file_id) === id) || null
})

const selectedDocumentMode = computed(() => String(selectedDocument.value?.mode || ""))

const providers = computed(() => {
  if (selectedDocumentMode.value === "pdf") {
    return documentProcessProviders.value
  }

  return textProviders.value
})

const noProviderMessage = computed(() => {
  if (selectedDocumentMode.value === "pdf") {
    return t("No document-processing AI providers available.")
  }

  return t("No text AI providers available.")
})

const prompt = ref("")
const promptDirty = ref(false)

const generatedText = ref(null)
const importReport = ref(null)
const importProgress = ref(null)
const isBusy = ref(false)

const parentResourceNodeId = ref(Number(route.params.node))

// Course context derived server-side from the gated session course.
const resourceLinkList = ref(JSON.stringify([{ visibility: RESOURCE_LINK_PUBLISHED }]))

const canGenerate = computed(() => {
  return canEditGlossary.value && providers.value.length > 0 && prompt.value.trim().length > 0 && !isBusy.value
})

const detectedCount = computed(() => {
  const terms = parseGlossaryTerms(generatedText.value || "")
  return terms.length
})

const canImport = computed(() => {
  return canEditGlossary.value && !!generatedText.value && generatedText.value.trim().length > 0 && !isBusy.value
})

function goBack() {
  router.push({ name: "GlossaryList", query: route.query })
}

function escapeHtml(str) {
  return (str ?? "")
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#039;")
}

/**
 * Remove common markdown wrappers and surrounding quotes for titles.
 * Keeps the actual text only (no **bold**, no quotes).
 */
function normalizeTermTitle(input) {
  let s = String(input ?? "").trim()

  // Remove leading list markers like "-", "*", "•", "1.", "1)"
  s = s.replace(/^\s*(?:[-*•]+|\d+[.)])\s+/, "")

  // Remove trailing separators sometimes left by AI output.
  s = s.replace(/\s*[:：-]\s*$/, "")

  // Remove surrounding quotes (straight + smart quotes)
  s = s.replace(/^[\s"'“”‘’]+/, "").replace(/[\s"'“”‘’]+$/, "")

  // Remove surrounding markdown wrappers (**term**, __term__, `term`)
  // Repeat a couple of times to handle nested wrappers.
  for (let k = 0; k < 3; k++) {
    s = s.replace(/^\*\*(.+)\*\*$/, "$1").trim()
    s = s.replace(/^__(.+)__$/, "$1").trim()
    s = s.replace(/^`(.+)`$/, "$1").trim()
    s = s.replace(/^[\s"'“”‘’]+/, "").replace(/[\s"'“”‘’]+$/, "")
    s = s.replace(/\s*[:：-]\s*$/, "")
  }

  // Collapse whitespace
  s = s.replace(/\s+/g, " ").trim()

  return s
}

/**
 * Conservative inline markdown cleanup for definitions (remove **bold** / __bold__ / `code`)
 * while keeping most punctuation intact.
 */
function cleanupDefinitionText(input) {
  let s = String(input ?? "")

  s = s.replace(/\*\*(.+?)\*\*/g, "$1")
  s = s.replace(/__(.+?)__/g, "$1")
  s = s.replace(/`(.+?)`/g, "$1")
  s = s.replace(/\s+/g, " ")

  return s.trim()
}

function isLikelyAiPreamble(line) {
  const value = String(line ?? "")
    .trim()
    .toLowerCase()

  if (!value) {
    return true
  }

  return (
    value.startsWith("here are") ||
    value.startsWith("below are") ||
    value.startsWith("these are") ||
    value.startsWith("generated glossary") ||
    value.startsWith("new glossary terms") ||
    value.startsWith("glossary terms generated") ||
    value.startsWith("the following glossary") ||
    value.startsWith("i generated") ||
    value.startsWith("sure,")
  )
}

/**
 * Accept common AI output variants:
 * 1. **Term**: Definition
 * 1. "Term": Definition
 * - Term: Definition
 * Term: Definition
 */
function parseInlineGlossaryTerm(line) {
  let value = String(line ?? "").trim()

  if (!value || isLikelyAiPreamble(value)) {
    return null
  }

  value = value.replace(/^\s*(?:[-*•]+|\d+[.)])\s+/, "").trim()

  const separatorMatch = value.match(/\s*[:：]\s*/)
  const separatorIndex = separatorMatch?.index ?? -1
  if (separatorIndex <= 0) {
    return null
  }

  const rawTerm = value.slice(0, separatorIndex).trim()
  const rawDefinition = value.slice(separatorIndex + separatorMatch[0].length).trim()

  const term = normalizeTermTitle(rawTerm)
  const definition = cleanupDefinitionText(rawDefinition)

  if (!term || !definition) {
    return null
  }

  return { term, definition }
}

function addParsedGlossaryTerm(items, seen, term, definitionRaw) {
  const normalizedTerm = normalizeTermTitle(term)
  const normalizedDefinition = cleanupDefinitionText(definitionRaw)

  if (!normalizedTerm || !normalizedDefinition) {
    return
  }

  const key = normalizedTerm.toLowerCase()
  if (seen.has(key)) {
    return
  }

  seen.add(key)

  // Store definition as safe HTML, preserve line breaks
  const safe = escapeHtml(normalizedDefinition).replace(/\n/g, "<br>")
  items.push({ title: normalizedTerm, description: safe })
}

/**
 * Parse both the requested strict format:
 * Term line
 * Definition line
 *
 * and common AI variants such as:
 * 1. **Term**: Definition
 */
function parseGlossaryTerms(text) {
  const lines = String(text ?? "").split(/\r?\n/)
  const items = []
  const seen = new Set()

  let i = 0
  while (i < lines.length) {
    while (i < lines.length && lines[i].trim() === "") i++
    if (i >= lines.length) break

    const currentLine = lines[i].trim()

    if (isLikelyAiPreamble(currentLine)) {
      i++
      continue
    }

    const inlineItem = parseInlineGlossaryTerm(currentLine)
    if (inlineItem) {
      addParsedGlossaryTerm(items, seen, inlineItem.term, inlineItem.definition)
      i++
      continue
    }

    const rawTerm = currentLine
    i++

    const defLines = []
    while (i < lines.length && lines[i].trim() !== "") {
      const possibleNextItem = parseInlineGlossaryTerm(lines[i].trim())

      if (possibleNextItem && defLines.length > 0) {
        break
      }

      defLines.push(lines[i])
      i++

      if (possibleNextItem) {
        break
      }
    }

    const term = normalizeTermTitle(rawTerm)
    const definitionRaw = defLines.join("\n").trim()

    addParsedGlossaryTerm(items, seen, term, definitionRaw)
  }

  return items
}

function normalizeProviders(raw) {
  const values = Array.isArray(raw) ? raw : Object.entries(raw || {}).map(([key, label]) => ({ key, label }))

  return values
    .map((p) => {
      if (typeof p === "string") {
        const s = p.trim()
        return s ? { key: s, label: s } : null
      }

      if (p && typeof p === "object") {
        const key = String(p.key ?? p.name ?? "").trim()
        if (!key) return null
        const label = String(p.label ?? key).trim()
        return { key, label }
      }

      return null
    })
    .filter(Boolean)
}

function selectFirstAvailableProvider() {
  const options = providers.value || []
  const current = String(aiProvider.value || "").trim()

  if (current && options.some((p) => p.key === current)) {
    return
  }

  aiProvider.value = options[0]?.key || ""
}

async function loadProviders() {
  try {
    const res = await glossaryService.getTextProviders()
    textProviders.value = normalizeProviders(res?.providers || [])

    try {
      const capabilities = await glossaryService.getAiCapabilities()
      documentProcessProviders.value = normalizeProviders(
        capabilities?.providers?.document_process || capabilities?.types?.document_process || [],
      )
    } catch (e) {
      console.warn("[GlossaryGenerateTerms] Failed to load AI capabilities:", e)
      documentProcessProviders.value = []
    }

    selectFirstAvailableProvider()
  } catch (e) {
    console.error("[GlossaryGenerateTerms] Failed to load AI providers:", e)
    textProviders.value = []
    documentProcessProviders.value = []
    aiProvider.value = ""
  }
}

async function loadDocumentSources() {
  documentSourcesLoading.value = true

  try {
    const res = await glossaryService.getDocumentSources({
      cid: route.query.cid,
      sid: route.query.sid,
    })

    documentSources.value = Array.isArray(res?.documents) ? res.documents : []
  } catch (e) {
    console.error("[GlossaryGenerateTerms] Failed to load document sources:", e)
    documentSources.value = []
  } finally {
    documentSourcesLoading.value = false
  }
}

async function applyDefaultPrompt(force = false) {
  try {
    const res = await glossaryService.getDefaultPrompt({
      cid: route.query.cid,
      sid: route.query.sid,
      n: n.value,
      language: locale.value || "en",
      resource_file_id: selectedResourceFileId.value !== "0" ? Number(selectedResourceFileId.value) : undefined,
    })

    if ((force || !promptDirty.value) && res?.prompt) {
      prompt.value = res.prompt
      if (force) {
        promptDirty.value = false
      }
    }
  } catch (e) {
    console.error("[GlossaryGenerateTerms] Failed to load default prompt:", e)

    if (force || !promptDirty.value) {
      const title = course.value?.title || course.value?.name || ""
      const selectedTitle = selectedDocument.value?.title || selectedDocument.value?.filename || ""

      if (selectedTitle) {
        prompt.value = `Generate ${n.value} glossary terms exclusively from the document '${selectedTitle}', each term on a single line, with its definition on the next line and one blank line between each term. Do not use outside knowledge.`
      } else {
        prompt.value = `Generate ${n.value} glossary terms for a course on '${title}', each term on a single line, with its definition on the next line and one blank line between each term. Do not add any other formatting for the title nor for the definition.`
      }

      if (force) {
        promptDirty.value = false
      }
    }
  }
}

function resetToDefaults() {
  // Reset number and prompt to default suggestion (best effort)
  n.value = 15
  promptDirty.value = false
  applyDefaultPrompt(true)
}

async function runGeneration() {
  if (!canGenerate.value) return

  isBusy.value = true
  importReport.value = null
  importProgress.value = null

  try {
    const res = await glossaryService.generateGlossaryTerms({
      n: n.value,
      language: locale.value || "en",
      prompt: prompt.value,
      cid: route.query.cid,
      sid: route.query.sid,
      ai_provider: aiProvider.value, // Send provider key only
      tool: "glossary",
      resource_file_id: selectedResourceFileId.value !== "0" ? Number(selectedResourceFileId.value) : undefined,
      document_title: selectedDocument.value?.title || selectedDocument.value?.filename || undefined,
    })

    if (!res?.success) {
      notification.showErrorNotification(res?.text || "AI glossary generation failed.")
      return
    }

    generatedText.value = res.text || ""
    notification.showSuccessNotification(t("Generate glossary terms"))
  } catch (e) {
    console.error("[GlossaryGenerateTerms] AI glossary generation failed:", e)
    notification.showErrorNotification(e?.response?.data?.text || "AI glossary generation failed.")
  } finally {
    isBusy.value = false
  }
}

async function importTerms() {
  if (!canImport.value) return

  isBusy.value = true
  importReport.value = null

  const terms = parseGlossaryTerms(generatedText.value)
  if (terms.length === 0) {
    notification.showErrorNotification("No valid terms detected. Ensure each term is followed by its definition.")
    isBusy.value = false
    return
  }

  let created = 0
  let skipped = 0
  const errors = []

  importProgress.value = { current: 0, total: terms.length }

  for (const item of terms) {
    importProgress.value.current++

    const postData = {
      title: item.title,
      description: item.description,
      parentResourceNodeId: parentResourceNodeId.value,
      resourceLinkList: resourceLinkList.value,
      sid: route.query.sid,
      cid: route.query.cid,
      ai_assisted_raw: 1,
    }

    try {
      await glossaryService.createGlossaryTerm(postData)
      created++
    } catch (e) {
      skipped++
      const msg = e?.response?.data?.message || e?.message || "Unknown error"
      errors.push(`${item.title}: ${msg}`)
    }
  }

  importReport.value = { created, skipped, errors }
  importProgress.value = null

  if (created > 0) {
    notification.showSuccessNotification("Glossary terms imported.")
  } else {
    notification.showErrorNotification("No terms were imported.")
  }

  isBusy.value = false
}

watch(n, async () => {
  // Refresh default prompt when N changes (only if the user didn't customize prompt)
  await applyDefaultPrompt(false)
})

watch(
  () => selectedResourceFileId.value,
  async () => {
    generatedText.value = null
    importReport.value = null
    promptDirty.value = false
    selectFirstAvailableProvider()
    await applyDefaultPrompt(true)
  },
)

watch(
  () => providers.value,
  () => {
    selectFirstAvailableProvider()
  },
  { deep: true },
)

onMounted(async () => {
  await Promise.all([loadProviders(), loadDocumentSources()])
  await applyDefaultPrompt(false)
})
</script>
