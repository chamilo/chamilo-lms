<template>
  <section class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h2 class="text-h2 font-semibold text-gray-90">
          {{ t(title || defaultTitle) }}
        </h2>
        <p class="mt-1 text-body-2 text-gray-60">
          {{ t(descriptionText) }}
        </p>
      </div>
      <BaseButton
        :label="t('Back to Tests tool')"
        icon="back"
        type="secondary"
        :route="{ name: 'ExerciseList', params: route.params, query: getContextParams() }"
      />
    </div>

    <div
      v-if="errorMessage"
      class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"
    >
      {{ errorMessage }}
    </div>

    <div
      v-if="successMessage"
      class="rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-700"
    >
      <p class="font-semibold">{{ successMessage }}</p>
      <p
        v-if="importedQuestionCount > 0"
        class="mt-1"
      >
        {{ t("Imported questions") }}: {{ importedQuestionCount }}
      </p>
      <p
        v-if="skippedQuestionCount > 0"
        class="mt-1"
      >
        {{ t("Skipped questions") }}: {{ skippedQuestionCount }}
      </p>
      <div
        v-if="createdExerciseId"
        class="mt-3 flex flex-wrap gap-2"
      >
        <BaseButton
          :label="t('Edit questions')"
          icon="edit"
          type="primary"
          :route="{
            name: 'ExerciseQuestions',
            params: { ...route.params, exerciseId: createdExerciseId },
            query: getContextParams(),
          }"
        />
        <BaseButton
          :label="t('Configure')"
          icon="settings"
          type="secondary"
          :route="{
            name: 'ExerciseEdit',
            params: { ...route.params, exerciseId: createdExerciseId },
            query: getContextParams(),
          }"
        />
      </div>
    </div>

    <form
      class="rounded-xl border border-gray-20 bg-white p-6 shadow-sm"
      @submit.prevent="submitImport"
    >
      <div class="space-y-4">
        <BaseInputText
          v-if="isAikenImport"
          id="aiken-total-weight"
          v-model="totalWeight"
          :help-text="t('Aiken import distributes this total weight across imported questions unless a question defines SCORE.')"
          :label="t('Total weight')"
          name="totalWeight"
        />

        <div
          v-if="isExcelImport"
          class="space-y-3 rounded-xl border border-gray-20 bg-gray-10 p-4"
        >
          <BaseCheckbox
            id="excel-use-custom-score"
            v-model="useCustomScore"
            :label="t('Use custom score for all questions')"
            name="useCustomScore"
          />
          <div
            v-if="useCustomScore"
            class="grid gap-3 md:grid-cols-2"
          >
            <BaseInputText
              id="excel-correct-score"
              v-model="correctScore"
              :label="t('Correct score')"
              name="correctScore"
            />
            <BaseInputText
              id="excel-incorrect-score"
              v-model="incorrectScore"
              :label="t('Incorrect score')"
              name="incorrectScore"
            />
          </div>
        </div>

        <div class="space-y-2">
          <label class="text-body-2 font-semibold text-gray-90">
            {{ t("File") }}
          </label>
          <BaseFileUpload
            :accept="acceptedFileTypes"
            :label="t('Choose file')"
            @file-selected="selectFile"
          />
          <p class="text-caption text-gray-50">
            {{ t(fileHelpText) }}
          </p>
        </div>

        <div class="flex flex-wrap gap-2">
          <BaseButton
            :disabled="!selectedFile || isLoading"
            :is-loading="isLoading"
            :is-submit="true"
            :label="t('Upload')"
            icon="file-upload"
            type="success"
          />
          <BaseButton
            v-if="isExcelImport && actionUrls.excelTemplate"
            :label="t('Download the Excel Template')"
            icon="file-excel"
            type="primary"
            :to-url="actionUrls.excelTemplate"
          />
        </div>
      </div>
    </form>

    <div class="rounded-xl border border-blue-100 bg-blue-50 p-4 text-sm text-blue-700">
      <p class="font-semibold">{{ t(sampleTitle) }}</p>
      <pre class="mt-3 overflow-x-auto whitespace-pre-wrap rounded-lg bg-white p-4 text-xs text-gray-800">{{ sample }}</pre>
    </div>

    <div
      v-if="rowErrors.length > 0"
      class="rounded-xl border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-800"
    >
      <p class="font-semibold">{{ t("Import warnings") }}</p>
      <ul class="mt-2 list-disc space-y-1 pl-5">
        <li
          v-for="(rowError, index) in rowErrors"
          :key="index"
        >
          {{ rowError }}
        </li>
      </ul>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseCheckbox from "../../components/basecomponents/BaseCheckbox.vue"
import BaseFileUpload from "../../components/basecomponents/BaseFileUpload.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import exerciseService from "../../services/exerciseService"

const props = defineProps({
  importType: {
    type: String,
    default: "aiken",
  },
})

const { t } = useI18n()
const route = useRoute()

const title = ref("")
const csrfToken = ref("")
const sample = ref("")
const actionUrls = ref({})
const totalWeight = ref("20")
const useCustomScore = ref(false)
const correctScore = ref("")
const incorrectScore = ref("")
const selectedFile = ref(null)
const isLoading = ref(false)
const errorMessage = ref("")
const successMessage = ref("")
const rowErrors = ref([])
const createdExerciseId = ref(null)
const importedQuestionCount = ref(0)
const skippedQuestionCount = ref(0)

const isAikenImport = computed(() => props.importType === "aiken")
const isExcelImport = computed(() => props.importType === "excel")
const isQti2Import = computed(() => props.importType === "qti2")
const defaultTitle = computed(() => {
  if (isExcelImport.value) {
    return "Import quiz from Excel"
  }

  if (isQti2Import.value) {
    return "Import exercises QTI2"
  }

  return "Import Aiken quiz"
})
const descriptionText = computed(() => {
  if (isExcelImport.value) {
    return "Upload an Excel file that follows the Chamilo quiz template."
  }

  if (isQti2Import.value) {
    return "Upload a QTI2 ZIP file to create a new test with its questions."
  }

  return "Upload an Aiken text file to create a new test with its questions."
})
const acceptedFileTypes = computed(() => {
  if (isExcelImport.value) {
    return ".xls,.xlsx,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
  }

  if (isQti2Import.value) {
    return ".zip,application/zip"
  }

  return ".txt,.zip,text/plain,application/zip"
})
const fileHelpText = computed(() => {
  if (isExcelImport.value) {
    return "You must upload a .xls or .xlsx file"
  }

  if (isQti2Import.value) {
    return "You must upload a .zip file"
  }

  return "You must upload a .txt or .zip file"
})
const sampleTitle = computed(() => {
  if (isExcelImport.value) {
    return "Excel format example"
  }

  if (isQti2Import.value) {
    return "QTI2 import notes"
  }

  return "Aiken format example"
})

function getQueryValue(value) {
  return Array.isArray(value) ? value[0] : value
}

function getContextParams() {
  return {
    cid: getQueryValue(route.query.cid),
    sid: getQueryValue(route.query.sid),
    gid: getQueryValue(route.query.gid),
  }
}

function selectFile(file) {
  selectedFile.value = file || null
  errorMessage.value = ""
}

async function loadImportData() {
  isLoading.value = true
  errorMessage.value = ""

  try {
    const response = await exerciseService.getExerciseQuestionImport(props.importType, getContextParams())
    title.value = response.title || defaultTitle.value
    csrfToken.value = response.csrfToken || ""
    sample.value = response.sample || ""
    actionUrls.value = response.actionUrls || {}
  } catch (error) {
    console.error("Error loading exercise question import data", error)
    errorMessage.value = t("Could not load import data")
  } finally {
    isLoading.value = false
  }
}

async function submitImport() {
  if (!selectedFile.value) {
    errorMessage.value = t("A file is required")
    return
  }

  isLoading.value = true
  errorMessage.value = ""
  successMessage.value = ""
  rowErrors.value = []

  const formData = new FormData()
  formData.append("submittedCsrfToken", csrfToken.value)
  formData.append("file", selectedFile.value)

  if (isAikenImport.value) {
    formData.append("totalWeight", totalWeight.value)
  }

  if (isExcelImport.value) {
    formData.append("useCustomScore", useCustomScore.value ? "1" : "0")
    formData.append("correctScore", correctScore.value)
    formData.append("incorrectScore", incorrectScore.value)
  }

  try {
    const response = await exerciseService.importExerciseQuestions(props.importType, formData, getContextParams())
    successMessage.value = t(response.message || defaultSuccessMessage())
    createdExerciseId.value = response.exerciseId || null
    importedQuestionCount.value = Number(response.importedQuestionCount || 0)
    skippedQuestionCount.value = Number(response.skippedQuestionCount || 0)
    rowErrors.value = Array.isArray(response.errors) ? response.errors : []
  } catch (error) {
    console.error("Error importing exercise questions", error)
    errorMessage.value = error?.response?.data?.detail || error?.response?.data?.["hydra:description"] || t("Could not import questions")
  } finally {
    isLoading.value = false
  }
}

function defaultSuccessMessage() {
  if (isExcelImport.value || isQti2Import.value) {
    return "File imported"
  }

  return "Aiken quiz imported"
}

onMounted(loadImportData)
</script>
