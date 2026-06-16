<template>
  <section class="space-y-5">
    <div class="flex flex-wrap items-center gap-1 rounded-xl border border-gray-20 bg-white px-2 py-1 shadow-sm w-fit">
      <BaseButton
        class="exercise-category-toolbar__button"
        :label="t('Return to exercises list')"
        :route="{ name: 'ExerciseList', params: route.params, query: getContextParams() }"
        icon="back"
        only-icon
        size="small"
        type="primary-text"
      />
      <BaseButton
        class="exercise-category-toolbar__button"
        :label="t('Add category')"
        icon="folder-plus"
        only-icon
        size="small"
        type="success"
        @click="startCreate"
      />
      <BaseButton
        v-if="isQuestionCategoryPage && categories.length > 0"
        class="exercise-category-toolbar__button"
        :label="t('CSV export')"
        icon="export"
        only-icon
        size="small"
        type="primary-text"
        @click="exportQuestionCategoriesCsv"
      />
      <BaseButton
        v-if="isQuestionCategoryPage"
        class="exercise-category-toolbar__button"
        :label="t('Import from a CSV')"
        icon="import"
        only-icon
        size="small"
        type="primary-text"
        @click="startImport"
      />
    </div>

    <div class="border-b border-gray-20" />

    <section class="space-y-1">
      <h1 class="text-xl font-semibold text-gray-90">
        {{ pageTitle }}
      </h1>
      <p class="text-sm text-gray-600">
        {{ pageDescription }}
      </p>
    </section>

    <div
      v-if="errorMessage"
      class="rounded-xl border border-danger/30 bg-danger/10 p-4 text-sm text-danger"
    >
      {{ errorMessage }}
    </div>

    <div
      v-if="infoMessage"
      class="rounded-xl border border-info/30 bg-support-1 p-4 text-sm text-support-4"
    >
      {{ infoMessage }}
    </div>

    <form
      v-if="isFormVisible"
      class="rounded-xl border border-gray-20 bg-white p-4 shadow-sm"
      @submit.prevent="saveCategory"
    >
      <div class="grid gap-4 md:grid-cols-2">
        <BaseInputText
          id="exercise-category-title"
          v-model="form.title"
          :label="t('Category name')"
          name="categoryTitle"
        />
        <BaseTextArea
          id="exercise-category-description"
          v-model="form.description"
          :label="t('Category description')"
          name="categoryDescription"
        />
      </div>
      <div class="mt-4 flex flex-wrap gap-2">
        <BaseButton
          :is-loading="isSaving"
          :label="editingCategoryId ? t('Edit category') : t('Add category')"
          icon="save"
          :is-submit="true"
          type="success"
        />
        <BaseButton
          :label="t('Cancel')"
          icon="close"
          type="plain"
          @click="cancelForm"
        />
      </div>
    </form>

    <form
      v-if="isImportVisible && isQuestionCategoryPage"
      class="rounded-xl border border-gray-20 bg-white p-4 shadow-sm"
      @submit.prevent="importCategories"
    >
      <div class="grid gap-4 md:grid-cols-2">
        <div class="space-y-2">
          <label
            class="text-sm font-medium text-gray-700"
            for="exercise-category-csv-file"
          >
            {{ t("CSV file") }}
          </label>
          <input
            id="exercise-category-csv-file"
            accept=".csv,text/csv"
            class="block w-full rounded border border-gray-25 bg-white px-3 py-2 text-sm"
            name="csvFile"
            type="file"
            @change="selectImportFile"
          />
          <p class="text-xs text-gray-600">
            {{ t("Select a CSV file containing title and description columns.") }}
          </p>
        </div>
      </div>
      <div class="mt-4 flex flex-wrap gap-2">
        <BaseButton
          :is-loading="isImporting"
          :label="t('Import from a CSV')"
          icon="import"
          :is-submit="true"
          type="success"
        />
        <BaseButton
          :label="t('Cancel')"
          icon="close"
          type="plain"
          @click="cancelImport"
        />
      </div>
    </form>

    <BaseTable
      :is-loading="isLoading"
      :text-for-empty="t('No categories found')"
      :total-items="categories.length"
      :values="categories"
      data-key="id"
    >
      <Column
        :header="t('Title')"
        field="title"
      >
        <template #body="{ data }">
          <div class="space-y-1">
            <div class="font-semibold text-gray-90">
              {{ displayText(data.title, t('Untitled')) }}
            </div>
            <div
              v-if="displayText(data.description)"
              class="text-xs text-gray-600"
            >
              {{ displayText(data.description) }}
            </div>
          </div>
        </template>
      </Column>

      <Column :header="usageHeader">
        <template #body="{ data }">
          <span class="font-semibold text-gray-800">{{ data.usageCount || 0 }}</span>
        </template>
      </Column>

      <Column
        :header="t('Actions')"
        class="w-32"
      >
        <template #body="{ data }">
          <div class="flex justify-end gap-1">
            <BaseButton
              :label="t('Edit')"
              icon="edit"
              only-icon
              size="small"
              type="secondary-text"
              @click="startEdit(data)"
            />
            <BaseButton
              :disabled="isSaving"
              :label="t('Delete')"
              icon="delete"
              only-icon
              size="small"
              type="danger-text"
              @click="confirmDelete(data)"
            />
          </div>
        </template>
      </Column>
    </BaseTable>
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import BaseTextArea from "../../components/basecomponents/BaseTextArea.vue"
import { useConfirmation } from "../../composables/useConfirmation"
import exerciseService from "../../services/exerciseService"

const props = defineProps({
  categoryType: {
    type: String,
    required: true,
  },
})

const { t } = useI18n()
const route = useRoute()
const { requireConfirmation } = useConfirmation()

const categories = ref([])
const csrfToken = ref("")
const isLoading = ref(false)
const isSaving = ref(false)
const isImporting = ref(false)
const isFormVisible = ref(false)
const isImportVisible = ref(false)
const importFile = ref(null)
const errorMessage = ref("")
const infoMessage = ref("")
const editingCategoryId = ref(null)
const form = reactive({
  title: "",
  description: "",
})

const isQuestionCategoryPage = computed(() => props.categoryType === "question")
const pageTitle = computed(() => (isQuestionCategoryPage.value ? t("Question categories") : t("Exercise categories")))
const pageDescription = computed(() =>
  isQuestionCategoryPage.value
    ? t("Manage categories used to classify questions.")
    : t("Manage categories used to classify exercises."),
)
const usageHeader = computed(() => (isQuestionCategoryPage.value ? t("Questions") : t("Tests")))

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

async function loadCategories() {
  isLoading.value = true
  errorMessage.value = ""
  infoMessage.value = ""

  try {
    const response = await exerciseService.getExerciseCategories(props.categoryType, getContextParams())
    categories.value = Array.isArray(response.items) ? response.items : []
    csrfToken.value = response.csrfToken || ""
  } catch (error) {
    console.error("Error loading exercise categories", error)
    errorMessage.value = t("Could not load categories")
  } finally {
    isLoading.value = false
  }
}

function startCreate() {
  editingCategoryId.value = null
  form.title = ""
  form.description = ""
  isImportVisible.value = false
  infoMessage.value = ""
  isFormVisible.value = true
}

function startEdit(category) {
  editingCategoryId.value = Number(category.id || 0)
  form.title = displayText(category.title)
  form.description = displayText(category.description)
  isImportVisible.value = false
  infoMessage.value = ""
  isFormVisible.value = true
}

function cancelForm() {
  editingCategoryId.value = null
  form.title = ""
  form.description = ""
  isFormVisible.value = false
}

function startImport() {
  importFile.value = null
  isFormVisible.value = false
  isImportVisible.value = true
  errorMessage.value = ""
  infoMessage.value = ""
}

function cancelImport() {
  importFile.value = null
  isImportVisible.value = false
}

function exportQuestionCategoriesCsv() {
  const rows = [["title", "description"]]

  for (const category of categories.value) {
    rows.push([
      displayText(category.title),
      displayText(category.description),
    ])
  }

  const csvContent = rows.map((row) => row.map(escapeCsvValue).join(",")).join("\n")
  const blob = new Blob([`${csvContent}\n`], { type: "text/csv;charset=utf-8" })
  const link = document.createElement("a")
  link.href = URL.createObjectURL(blob)
  link.download = "question_categories.csv"
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  URL.revokeObjectURL(link.href)
}

function escapeCsvValue(value) {
  const stringValue = String(value || "")

  return `"${stringValue.replace(/"/g, '""')}"`
}

function selectImportFile(event) {
  const files = event?.target?.files
  importFile.value = files && files.length > 0 ? files[0] : null
}

function readFileAsText(file) {
  return new Promise((resolve, reject) => {
    const reader = new FileReader()
    reader.onload = () => resolve(String(reader.result || ""))
    reader.onerror = () => reject(reader.error)
    reader.readAsText(file)
  })
}

async function importCategories() {
  if (!importFile.value) {
    errorMessage.value = t("This field is required")
    return
  }

  isImporting.value = true
  errorMessage.value = ""
  infoMessage.value = ""

  try {
    const csvContent = await readFileAsText(importFile.value)
    const response = await exerciseService.saveExerciseCategoryAction(
      props.categoryType,
      {
        action: "import_csv",
        csvContent,
        submittedCsrfToken: csrfToken.value,
      },
      getContextParams(),
    )
    cancelImport()
    await loadCategories()
    const importedCount = Number(response.importedCount || 0)
    const skippedCount = Number(response.skippedCount || 0)
    infoMessage.value =
      skippedCount > 0
        ? t("Imported categories: {0}. Skipped rows: {1}.", [importedCount, skippedCount])
        : t("Imported categories: {0}.", [importedCount])
  } catch (error) {
    console.error("Error importing exercise categories", error)
    errorMessage.value =
      error?.response?.data?.hydra?.description || error?.response?.data?.detail || t("Could not import categories")
  } finally {
    isImporting.value = false
  }
}

async function saveCategory() {
  const title = form.title.trim()
  if (!title) {
    errorMessage.value = t("This field is required")
    return
  }

  isSaving.value = true
  errorMessage.value = ""
  infoMessage.value = ""

  try {
    await exerciseService.saveExerciseCategoryAction(
      props.categoryType,
      {
        action: editingCategoryId.value ? "update" : "create",
        categoryId: editingCategoryId.value,
        categoryTitle: title,
        description: form.description,
        submittedCsrfToken: csrfToken.value,
      },
      getContextParams(),
    )
    cancelForm()
    await loadCategories()
  } catch (error) {
    console.error("Error saving exercise category", error)
    errorMessage.value = error?.response?.data?.hydra?.description || error?.response?.data?.detail || t("Could not save category")
  } finally {
    isSaving.value = false
  }
}

function confirmDelete(category) {
  requireConfirmation({
    message: t("Are you sure you want to delete this category?"),
    accept: () => deleteCategory(category),
  })
}

async function deleteCategory(category) {
  isSaving.value = true
  errorMessage.value = ""
  infoMessage.value = ""

  try {
    await exerciseService.saveExerciseCategoryAction(
      props.categoryType,
      {
        action: "delete",
        categoryId: Number(category.id || 0),
        submittedCsrfToken: csrfToken.value,
      },
      getContextParams(),
    )
    await loadCategories()
  } catch (error) {
    console.error("Error deleting exercise category", error)
    errorMessage.value = error?.response?.data?.hydra?.description || error?.response?.data?.detail || t("Could not delete category")
  } finally {
    isSaving.value = false
  }
}

function decodeHtml(value) {
  if (!value) {
    return ""
  }

  const textarea = document.createElement("textarea")
  textarea.innerHTML = String(value)

  return textarea.value
}

function displayText(value, fallback = "") {
  const decodedValue = decodeHtml(value)
  const plainValue = decodeHtml(decodedValue.replace(/<[^>]*>/g, " "))
    .replace(/\s+/g, " ")
    .trim()

  return plainValue || fallback
}

onMounted(loadCategories)
</script>

<style scoped>
:deep(.exercise-category-toolbar__button) {
  min-width: 2.5rem;
  width: 2.5rem;
  height: 2.5rem;
}

:deep(.exercise-category-toolbar__button .p-button-icon) {
  font-size: 1.25rem;
}
</style>
