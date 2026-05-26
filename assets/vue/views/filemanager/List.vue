<template>
  <div
    v-if="!isLoading"
    class="overflow-hidden rounded-2xl border border-gray-25 bg-white shadow-sm"
  >
    <div class="border-b border-gray-25 bg-support-2 px-4 py-4">
      <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
          <h2 class="text-xl font-semibold text-gray-90">
            {{ isTinyPicker ? t("File manager") : t("My files") }}
          </h2>
          <p class="mt-1 text-caption text-gray-50">
            {{
              isTinyPicker
                ? t("Select a file to insert into the editor.")
                : t("Manage your personal files and course documents.")
            }}
          </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
          <span
            v-if="isTinyPicker"
            class="inline-flex rounded-full bg-support-1 px-3 py-1 text-tiny font-semibold uppercase tracking-wide text-support-4"
          >
            {{ pickerTypeLabel }}
          </span>

          <span
            class="inline-flex rounded-full bg-gray-15 px-3 py-1 text-tiny font-semibold uppercase tracking-wide text-gray-90"
          >
            {{ activeTabLabel }}
          </span>
        </div>
      </div>
    </div>

    <div class="border-b border-gray-25 bg-white px-4 pt-4">
      <div class="flex flex-wrap gap-2">
        <button
          :class="tabButtonClass('personalFiles')"
          class="rounded-t-xl border-b-2 px-4 py-2.5 text-body-2 font-semibold transition"
          @click="changeTab('personalFiles')"
        >
          {{ t("My files") }}
        </button>

        <button
          v-if="showDocumentsTab"
          :class="tabButtonClass('documents')"
          class="rounded-t-xl border-b-2 px-4 py-2.5 text-body-2 font-semibold transition"
          @click="changeTab('documents')"
        >
          {{ t("Documents") }}
        </button>
      </div>
    </div>

    <div class="bg-white p-4 md:p-5">
      <div
        v-if="activeTab === 'personalFiles'"
        class="min-h-[180px]"
      >
        <PersonalFiles />
      </div>

      <div
        v-if="activeTab === 'documents' && showDocumentsTab"
        class="min-h-[180px]"
      >
        <CourseDocuments />
      </div>
    </div>
  </div>

  <div
    v-else
    class="overflow-hidden rounded-2xl border border-gray-25 bg-white shadow-sm"
  >
    <div class="animate-pulse space-y-4 p-6">
      <div class="h-6 w-40 rounded bg-gray-20" />
      <div class="flex gap-2">
        <div class="h-10 w-28 rounded-xl bg-gray-20" />
        <div class="h-10 w-28 rounded-xl bg-gray-20" />
      </div>
      <div class="h-40 rounded-2xl bg-gray-15" />
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref, watch, computed, provide } from "vue"
import { useRoute, useRouter } from "vue-router"
import PersonalFiles from "../../components/filemanager/PersonalFiles.vue"
import CourseDocuments from "../../components/filemanager/CourseDocuments.vue"
import { useIsAllowedToEdit } from "../../composables/userPermissions"
import { useI18n } from "vue-i18n"
import { useCidReqStore } from "../../store/cidReq"
import { storeToRefs } from "pinia"
import { pickUrlForTinyMce } from "../../utils/tinyPickerBridge"

const PICKER_CONTEXT_STORAGE_KEY = "chamilo_filemanager_tinymce_picker_context"

const route = useRoute()
const router = useRouter()

function normalizePickerType(raw) {
  const value = String(raw || "")
    .trim()
    .toLowerCase()

  if (value === "images" || value === "image") return "images"
  if (value === "media" || value === "video" || value === "audio") return "media"
  return "files"
}

function readStoredPickerContext() {
  try {
    const raw = sessionStorage.getItem(PICKER_CONTEXT_STORAGE_KEY)
    if (!raw) return null

    const parsed = JSON.parse(raw)
    if (!parsed || typeof parsed !== "object") return null

    return parsed
  } catch {
    return null
  }
}

function writeStoredPickerContext(context) {
  try {
    sessionStorage.setItem(PICKER_CONTEXT_STORAGE_KEY, JSON.stringify(context))
  } catch {
    // Ignore storage errors.
  }
}

function buildStoredPickerContext(query) {
  const picker = String(query?.picker || "")
  const cbId = String(query?.cbId || "")
  const type = normalizePickerType(query?.type)
  const tab = String(query?.tab || "personalFiles")
  const returnTo = String(query?.returnTo || "FileManagerList")
  const loadNode = String(query?.loadNode || "1")
  const parentResourceNodeId = String(query?.parentResourceNodeId || query?.parent || "")

  return {
    picker,
    cbId,
    type,
    tab,
    returnTo,
    loadNode,
    parentResourceNodeId,
    parent: parentResourceNodeId,
  }
}

function sanitizeQuery(query) {
  const clean = {}

  Object.entries(query || {}).forEach(([key, value]) => {
    if (value === undefined || value === null || value === "") {
      return
    }

    clean[key] = value
  })

  return clean
}

const storedContext = ref(readStoredPickerContext())
const activeTab = ref(String(route.query.tab || storedContext.value?.tab || "personalFiles"))
const { isAllowedToEdit } = useIsAllowedToEdit()
const isLoading = ref(true)
const { t } = useI18n()

const cidReqStore = useCidReqStore()
const { course } = storeToRefs(cidReqStore)

const isTinyPicker = computed(() => {
  const queryPicker = String(route.query.picker || "")
  if (queryPicker === "tinymce") {
    return true
  }

  return storedContext.value?.picker === "tinymce" && !!storedContext.value?.cbId
})

const pickerType = computed(() => {
  const rawType = route.query.type || (isTinyPicker.value ? storedContext.value?.type : "")
  return normalizePickerType(rawType)
})

const cbId = computed(() => {
  return String(route.query.cbId || (isTinyPicker.value ? storedContext.value?.cbId || "" : ""))
})

const showDocumentsTab = computed(() => {
  return isAllowedToEdit.value && !!course.value
})

const pickerTypeLabel = computed(() => {
  if (pickerType.value === "images") return t("Images")
  if (pickerType.value === "media") return t("Media")
  return t("Files")
})

const activeTabLabel = computed(() => {
  return activeTab.value === "documents" ? t("Documents") : t("My files")
})

function tabButtonClass(tab) {
  const isActive = activeTab.value === tab

  if (isActive) {
    return "border-primary bg-support-2 text-primary"
  }

  return "border-transparent bg-transparent text-gray-50 hover:border-gray-25 hover:bg-gray-10 hover:text-gray-90"
}

function buildNormalizedQuery(tabOverride = null) {
  const baseStored = storedContext.value || {}
  const nextTab = String(tabOverride || route.query.tab || baseStored.tab || "personalFiles")
  const nextType = normalizePickerType(route.query.type || baseStored.type)
  const nextPicker = String(route.query.picker || baseStored.picker || "")
  const nextCbId = String(route.query.cbId || baseStored.cbId || "")
  const nextReturnTo = String(
    route.query.returnTo || baseStored.returnTo || (nextPicker === "tinymce" ? "FileManagerList" : ""),
  )
  const nextLoadNode = String(route.query.loadNode || baseStored.loadNode || "1")
  const nextParent = String(
    route.query.parentResourceNodeId || route.query.parent || baseStored.parentResourceNodeId || "",
  )

  return sanitizeQuery({
    ...route.query,
    type: nextType,
    tab: nextTab,
    picker: nextPicker || undefined,
    cbId: nextCbId || undefined,
    returnTo: nextReturnTo || undefined,
    loadNode: nextLoadNode,
    parentResourceNodeId: nextParent || undefined,
    parent: nextParent || undefined,
  })
}

function persistPickerContextFromQuery() {
  const nextQuery = buildNormalizedQuery()
  if (String(nextQuery.picker || "") !== "tinymce") {
    return
  }

  const nextStored = buildStoredPickerContext(nextQuery)
  storedContext.value = nextStored
  writeStoredPickerContext(nextStored)
}

function changeTab(tab) {
  activeTab.value = tab
  const nextQuery = buildNormalizedQuery(tab)

  if (String(nextQuery.picker || "") === "tinymce") {
    const nextStored = buildStoredPickerContext(nextQuery)
    storedContext.value = nextStored
    writeStoredPickerContext(nextStored)
  }

  router.replace({ query: nextQuery })
}

watch(
  () => route.query,
  (newQuery) => {
    const nextTab = String(newQuery.tab || storedContext.value?.tab || "personalFiles")
    if (nextTab !== activeTab.value) {
      activeTab.value = nextTab
    }

    if (String(newQuery.picker || "") === "tinymce") {
      const nextStored = buildStoredPickerContext(newQuery)
      storedContext.value = nextStored
      writeStoredPickerContext(nextStored)
    }
  },
  { deep: true },
)

provide("chamiloTinyPickerContext", {
  isTinyPicker,
  pickerType,
  cbId,
  pick(url) {
    pickUrlForTinyMce(url, { cbId: cbId.value, close: true, logPrefix: "[FILEMANAGER PICKER]" })
  },
})

onMounted(async () => {
  persistPickerContextFromQuery()

  const nextQuery = buildNormalizedQuery()

  const currentType = String(route.query.type || "")
    .trim()
    .toLowerCase()
  const currentTab = String(route.query.tab || "")
  const currentPicker = String(route.query.picker || "")
  const currentCbId = String(route.query.cbId || "")

  const needsReplace =
    currentType !== nextQuery.type ||
    currentTab !== nextQuery.tab ||
    (isTinyPicker.value && currentPicker !== String(nextQuery.picker || "")) ||
    (isTinyPicker.value && currentCbId !== String(nextQuery.cbId || ""))

  if (needsReplace) {
    await router.replace({ query: nextQuery })
  }

  activeTab.value = String(nextQuery.tab || "personalFiles")
  isLoading.value = false
})
</script>
