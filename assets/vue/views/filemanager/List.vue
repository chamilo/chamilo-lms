<template>
  <div v-if="!isLoading">
    <div class="flex border-b border-gray-200">
      <button
        :class="{
          'border-blue-500 text-blue-600': activeTab === 'personalFiles',
          'border-transparent text-gray-600 hover:text-gray-800 hover:border-gray-300': activeTab !== 'personalFiles',
        }"
        class="px-4 py-2 -mb-px font-semibold border-b-2"
        @click="changeTab('personalFiles')"
      >
        {{ t("My files") }}
      </button>
      <button
        v-if="showDocumentsTab"
        :class="{
          'border-blue-500 text-blue-600': activeTab === 'documents',
          'border-transparent text-gray-600 hover:text-gray-800 hover:border-gray-300': activeTab !== 'documents',
        }"
        class="px-4 py-2 -mb-px font-semibold border-b-2"
        @click="changeTab('documents')"
      >
        {{ t("Documents") }}
      </button>
    </div>

    <div
      v-if="activeTab === 'personalFiles'"
      class="mt-4"
    >
      <PersonalFiles />
    </div>

    <div
      v-if="activeTab === 'documents' && showDocumentsTab"
      class="mt-4"
    >
      <CourseDocuments />
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref, watch, computed, provide } from "vue"
import { useRoute, useRouter } from "vue-router"
import PersonalFiles from "../../components/filemanager/PersonalFiles.vue"
import CourseDocuments from "../../components/filemanager/CourseDocuments.vue"
import { checkIsAllowedToEdit } from "../../composables/userPermissions"
import { useI18n } from "vue-i18n"
import { useCidReqStore } from "../../store/cidReq"
import { storeToRefs } from "pinia"
import { pickUrlForTinyMce } from "../../utils/tinyPickerBridge"

const route = useRoute()
const router = useRouter()

const activeTab = ref(String(route.query.tab || "personalFiles"))
const isAllowedToEdit = ref(false)
const isLoading = ref(true)
const { t } = useI18n()

const cidReqStore = useCidReqStore()
const { course } = storeToRefs(cidReqStore)
const courseIsSet = ref(false)

const isTinyPicker = computed(() => String(route.query.picker || "") === "tinymce")
const pickerType = computed(() => String(route.query.type || "files").toLowerCase())
const cbId = computed(() => String(route.query.cbId || ""))

const showDocumentsTab = computed(() => {
  // In TinyMCE picker mode, keep behavior simple:
  // show documents tab only when allowed + course exists.
  return isAllowedToEdit.value && courseIsSet.value
})

function changeTab(tab) {
  activeTab.value = tab
  router.replace({ query: { ...route.query, tab } })
}

watch(route, (newRoute) => {
  const nextTab = String(newRoute.query.tab || "personalFiles")
  if (nextTab !== activeTab.value) activeTab.value = nextTab
})

// Provide a shared picker context for children (PersonalFiles / CourseDocuments).
provide("chamiloTinyPickerContext", {
  isTinyPicker,
  pickerType,
  cbId,
  pick(url) {
    pickUrlForTinyMce(url, { cbId: cbId.value, close: true, logPrefix: "[FILEMANAGER PICKER]" })
  },
})

onMounted(async () => {
  isAllowedToEdit.value = await checkIsAllowedToEdit()
  courseIsSet.value = !!course.value

  // If opened as TinyMCE picker, default to personalFiles tab unless explicitly set.
  if (isTinyPicker.value && !route.query.tab) {
    activeTab.value = "personalFiles"
    router.replace({ query: { ...route.query, tab: "personalFiles" } })
  }

  isLoading.value = false
})
</script>
