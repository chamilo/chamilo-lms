<template>
  <section class="space-y-6">
    <SectionHeader :title="t('Import course progress')" />

    <BaseToolbar class="mb-4 border-b border-gray-25 bg-white">
      <template #start>
        <BaseButton
          icon="back"
          :label="t('Back')"
          only-icon
          size="large"
          type="primary-text"
          class="!flex !h-12 !w-12 !items-center !justify-center !rounded-xl !p-0 [&_.p-button-icon]:!text-2xl"
          :route="listRoute"
        />
      </template>
    </BaseToolbar>

    <div
      v-if="isLoading"
      class="rounded-xl border border-gray-20 bg-white p-6 text-center text-sm text-gray-600 shadow-sm"
      role="status"
    >
      {{ t("Loading...") }}
    </div>

    <div
      v-else-if="loadErrorMessage"
      class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"
      role="alert"
    >
      {{ loadErrorMessage }}
    </div>

    <form
      v-else
      class="space-y-6"
      novalidate
      @submit.prevent="importCourseProgress"
    >
      <div
        v-if="formErrorMessage"
        class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"
        role="alert"
        aria-live="polite"
      >
        {{ formErrorMessage }}
      </div>

      <BaseCard>
        <template #title>
          <div class="flex items-center gap-2">
            <BaseIcon
              icon="import"
              size="normal"
            />
            <span>{{ t("Import course progress") }}</span>
          </div>
        </template>

        <div class="space-y-5">
          <BaseFileUpload
            :key="fileInputKey"
            accept=".csv,text/csv"
            :label="t('Select file')"
            @fileSelected="selectFile"
          />
          <input
            :value="selectedFile?.name || ''"
            name="course_progress_file"
            type="hidden"
          />

          <BaseCheckbox
            id="course_progress_replace"
            v-model="replaceCurrentProgress"
            :label="t('Delete all course progress')"
            name="replace"
          />
        </div>
      </BaseCard>

      <div class="flex flex-wrap justify-end gap-2">
        <BaseButton
          icon="back"
          :label="t('Cancel')"
          type="plain"
          :route="listRoute"
        />
        <BaseButton
          icon="import"
          :is-loading="isImporting"
          :label="t('Import')"
          name="import_course_progress"
          type="success"
          is-submit
        />
      </div>
    </form>
  </section>
</template>

<script setup>
import { computed, onMounted, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseCard from "../../components/basecomponents/BaseCard.vue"
import BaseCheckbox from "../../components/basecomponents/BaseCheckbox.vue"
import BaseFileUpload from "../../components/basecomponents/BaseFileUpload.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import courseProgressService from "../../services/courseProgressService"
import { usePlatformConfig } from "../../store/platformConfig"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const platformConfigStore = usePlatformConfig()

const isLoading = ref(false)
const isImporting = ref(false)
const loadErrorMessage = ref("")
const formErrorMessage = ref("")
const selectedFile = ref(null)
const replaceCurrentProgress = ref(false)
const csrfToken = ref("")
const fileInputKey = ref(0)

const listRoute = computed(() => ({
  name: "CourseProgressList",
  params: { node: route.params.node },
  query: getContextParams(),
}))

function getQueryValue(value) {
  return Array.isArray(value) ? value[0] : value
}

function getContextParams() {
  const params = {
    cid: getQueryValue(route.query.cid),
  }
  const sid = Number(getQueryValue(route.query.sid) || 0)
  const gid = Number(getQueryValue(route.query.gid) || 0)

  if (sid > 0) {
    params.sid = sid
  }

  if (gid > 0) {
    params.gid = gid
  }

  if (Object.prototype.hasOwnProperty.call(route.query, "isStudentView")) {
    params.isStudentView = getQueryValue(route.query.isStudentView)
  }

  return params
}

function selectFile(file) {
  selectedFile.value = file
  formErrorMessage.value = ""
}

async function loadImportForm() {
  isLoading.value = true
  loadErrorMessage.value = ""
  formErrorMessage.value = ""

  try {
    const response = await courseProgressService.getList(getContextParams())

    if (!response.canManage || platformConfigStore.isStudentViewActive) {
      loadErrorMessage.value = t("Access denied")
      csrfToken.value = ""

      return
    }

    csrfToken.value = response.csrfToken || ""
  } catch (error) {
    console.error("Error loading course progress import form", error)
    loadErrorMessage.value =
      error?.response?.data?.detail || error?.response?.data?.["hydra:description"] || t("An error occurred")
  } finally {
    isLoading.value = false
  }
}

async function importCourseProgress() {
  if (isImporting.value) {
    return
  }

  formErrorMessage.value = ""

  if (!(selectedFile.value instanceof File) || !csrfToken.value) {
    formErrorMessage.value = t("Please fill all required fields")

    return
  }

  isImporting.value = true

  try {
    await courseProgressService.importCsv(
      selectedFile.value,
      replaceCurrentProgress.value,
      csrfToken.value,
      getContextParams(),
    )

    await router.push({
      ...listRoute.value,
      query: {
        ...listRoute.value.query,
        imported: 1,
      },
    })
  } catch (error) {
    console.error("Error importing course progress", error)
    formErrorMessage.value =
      error?.response?.data?.detail || error?.response?.data?.["hydra:description"] || t("An error occurred")
  } finally {
    isImporting.value = false
  }
}

onMounted(loadImportForm)

watch(
  () => platformConfigStore.isStudentViewActive,
  async () => {
    selectedFile.value = null
    replaceCurrentProgress.value = false
    fileInputKey.value += 1
    await loadImportForm()
  },
)
</script>
