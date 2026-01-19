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
                :key="p"
                :value="p"
              >
                {{ p }}
              </option>
            </select>

            <p
              v-if="providers.length === 0"
              class="text-xs text-danger mt-1"
            >
              No text AI providers available.
            </p>
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
              :disabled="isBusy || !canEditGlossary"
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
          :disabled="isBusy || !canEditGlossary"
          @input="promptDirty = true"
        />
        <p class="text-xs text-gray-60 mt-2">
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
import { useCidReq } from "../../composables/cidReq"
import { RESOURCE_LINK_PUBLISHED } from "../../constants/entity/resourcelink"
import glossaryService from "../../services/glossaryService"
import { useNotification } from "../../composables/notification"
import { useSecurityStore } from "../../store/securityStore"
import { checkIsAllowedToEdit } from "../../composables/userPermissions"
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
const { course, session } = storeToRefs(cidReqStore)

const { cid, sid } = useCidReq()

const isAllowedToEdit = ref(false)

const canEditGlossary = computed(() => {
  const inSession = !!route.query.sid
  const basePermission = isAllowedToEdit.value || (securityStore.isCurrentTeacher && !inSession)
  return basePermission && !platform.isStudentViewActive
})

const n = ref(15)
const providers = ref([])
const aiProvider = ref("")
const prompt = ref("")
const promptDirty = ref(false)

const generatedText = ref(null)
const importReport = ref(null)
const importProgress = ref(null)
const isBusy = ref(false)

const parentResourceNodeId = ref(Number(route.params.node))

const resourceLinkList = ref(
  JSON.stringify([
    {
      sid,
      cid,
      visibility: RESOURCE_LINK_PUBLISHED,
    },
  ]),
)

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

  // Remove surrounding quotes (straight + smart quotes)
  s = s.replace(/^[\s"'“”‘’]+/, "").replace(/[\s"'“”‘’]+$/, "")

  // Remove surrounding markdown wrappers (**term**, __term__, `term`)
  // Repeat a couple of times to handle nested wrappers.
  for (let k = 0; k < 2; k++) {
    s = s.replace(/^\*\*(.+)\*\*$/, "$1").trim()
    s = s.replace(/^__(.+)__$/, "$1").trim()
    s = s.replace(/^`(.+)`$/, "$1").trim()
    s = s.replace(/^[\s"'“”‘’]+/, "").replace(/[\s"'“”‘’]+$/, "")
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

  return s.trim()
}

/**
 * Parse:
 * Term line
 * Definition lines...
 * blank line between items
 */
function parseGlossaryTerms(text) {
  const lines = String(text ?? "").split(/\r?\n/)
  const items = []
  const seen = new Set()

  let i = 0
  while (i < lines.length) {
    while (i < lines.length && lines[i].trim() === "") i++
    if (i >= lines.length) break

    const rawTerm = lines[i].trim()
    i++

    const defLines = []
    while (i < lines.length && lines[i].trim() !== "") {
      defLines.push(lines[i])
      i++
    }

    const term = normalizeTermTitle(rawTerm)
    const definitionRaw = cleanupDefinitionText(defLines.join("\n").trim())

    if (term && definitionRaw) {
      const key = term.toLowerCase()
      if (seen.has(key)) {
        continue
      }
      seen.add(key)

      // Store definition as safe HTML, preserve line breaks
      const safe = escapeHtml(definitionRaw).replace(/\n/g, "<br>")
      items.push({ title: term, description: safe })
    }
  }

  return items
}

async function loadProviders() {
  try {
    const res = await glossaryService.getTextProviders()
    providers.value = res.providers || []
    aiProvider.value = providers.value[0] || ""
  } catch (e) {
    console.error("[GlossaryGenerateTerms] Error loading AI providers:", e)
    providers.value = []
  }
}

async function applyDefaultPrompt(force = false) {
  try {
    const res = await glossaryService.getDefaultPrompt({
      cid: route.query.cid,
      sid: route.query.sid,
      n: n.value,
      language: locale.value || "en",
    })

    if ((force || !promptDirty.value) && res?.prompt) {
      prompt.value = res.prompt
      if (force) {
        promptDirty.value = false
      }
    }
  } catch (e) {
    console.error("[GlossaryGenerateTerms] Error loading default prompt:", e)

    if (force || !promptDirty.value) {
      const title = course.value?.title || course.value?.name || ""
      prompt.value = `Generate ${n.value} glossary terms for a course on '${title}', each term on a single line, with its definition on the next line and one blank line between each term. Do not add any other formatting for the title nor for the definition.`
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
      ai_provider: aiProvider.value,
      tool: "glossary",
    })

    generatedText.value = res.text || ""
    notification.showSuccessNotification(t("Generate glossary terms"))
  } catch (e) {
    console.error("[GlossaryGenerateTerms] Error generating glossary terms:", e)
    notification.showErrorNotification("AI glossary generation failed.")
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

onMounted(async () => {
  isAllowedToEdit.value = await checkIsAllowedToEdit(true, true, true)

  await loadProviders()
  await applyDefaultPrompt(false)
})
</script>
