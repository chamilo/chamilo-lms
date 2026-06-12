<template>
  <section class="space-y-6">
    <div class="flex flex-wrap items-center gap-2">
      <div class="exercise-list-toolbar flex flex-wrap items-center gap-1 rounded-xl border border-gray-20 bg-white px-2 py-1 shadow-sm">
        <BaseButton
          v-if="canCreate"
          class="exercise-list-toolbar__button"
          :label="t('Create exercise')"
          :route="{ name: 'ExerciseCreate', params: route.params, query: getContextParams() }"
          icon="plus"
          only-icon
          size="small"
          type="primary-text"
        />
        <BaseButton
          v-if="canCreate"
          class="exercise-list-toolbar__button"
          :label="t('Create question')"
          :to-url="legacyUrl('question_create.php')"
          icon="help"
          only-icon
          size="small"
          type="primary-text"
        />
        <BaseButton
          v-if="canManage && settings.allowExerciseCategories"
          class="exercise-list-toolbar__button"
          :label="t('Exercise categories')"
          :to-url="legacyUrl('category.php')"
          icon="folder-generic"
          only-icon
          size="small"
          type="primary-text"
        />
        <BaseButton
          v-if="canManage"
          class="exercise-list-toolbar__button"
          :label="t('Question categories')"
          :to-url="legacyUrl('tests_category.php')"
          icon="folder-generic"
          only-icon
          size="small"
          type="primary-text"
        />
        <BaseButton
          v-if="canManage"
          class="exercise-list-toolbar__button"
          :label="t('Question bank')"
          :to-url="legacyUrl('question_pool.php')"
          icon="table"
          only-icon
          size="small"
          type="primary-text"
        />
        <BaseButton
          v-if="canManage"
          class="exercise-list-toolbar__button"
          :label="t('Import exercises QTI2')"
          :to-url="legacyUrl('qti2.php')"
          icon="import"
          only-icon
          size="small"
          type="primary-text"
        />
        <BaseButton
          v-if="canManage"
          class="exercise-list-toolbar__button"
          :label="t('Import Aiken quiz')"
          :to-url="legacyUrl('aiken.php')"
          icon="file-text"
          only-icon
          size="small"
          type="primary-text"
        />
        <BaseButton
          v-if="canManage"
          class="exercise-list-toolbar__button"
          :label="t('Import quiz from Excel')"
          :to-url="legacyUrl('upload_exercise.php')"
          icon="file-excel"
          only-icon
          size="small"
          type="primary-text"
        />
        <span
          class="mx-1 h-6 w-px bg-gray-20"
          aria-hidden="true"
        />
        <BaseButton
          class="exercise-list-toolbar__button"
          :label="isSearchVisible ? t('Hide search') : t('Search')"
          :icon="isSearchVisible ? 'close' : 'search'"
          only-icon
          size="small"
          type="primary-text"
          @click="toggleSearchForm"
        />
      </div>
    </div>

    <div class="border-b border-gray-20" />

    <div
      v-if="activeSearch && !isSearchVisible"
      class="flex flex-wrap items-center gap-2 rounded-xl border border-gray-20 bg-white px-4 py-3 text-sm text-gray-700 shadow-sm"
    >
      <span>{{ t("Showing results for: {0}", [activeSearch]) }}</span>
      <BaseButton
        :label="t('Clear search')"
        icon="close"
        only-icon
        size="small"
        type="secondary-text"
        @click="clearSearch"
      />
    </div>

    <form
      v-if="isSearchVisible"
      class="rounded-xl border border-gray-20 bg-white p-4 shadow-sm"
      @submit.prevent="applySearch"
    >
      <div class="flex flex-col gap-3 md:flex-row md:items-start">
        <BaseInputText
          id="exercise-search"
          v-model="searchTerm"
          class="flex-1"
          :help-text="t('Search by exercise title or description.')"
          :label="t('Search exercises')"
          name="search"
        />
        <BaseSelect
          v-if="settings.allowExerciseCategories && categories.length > 0"
          id="exercise-category-filter"
          v-model="selectedCategoryId"
          class="min-w-64"
          :label="t('Category')"
          name="categoryId"
          :options="categoryOptions"
          option-label="label"
          option-value="value"
        />
        <div class="flex flex-wrap gap-2 md:pt-1">
          <BaseButton
            :is-loading="isLoading"
            :label="t('Search')"
            icon="search"
            :is-submit="true"
            type="primary"
          />
          <BaseButton
            v-if="hasActiveFilters"
            :label="t('Clear search')"
            icon="close"
            type="secondary"
            @click="clearSearch"
          />
          <BaseButton
            v-else
            :label="t('Cancel')"
            icon="close"
            type="secondary"
            @click="hideSearchForm"
          />
        </div>
      </div>
    </form>

    <div
      v-if="errorMessage"
      class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"
    >
      {{ errorMessage }}
    </div>

    <div
      v-if="usesLegacyActions"
      class="rounded-xl border border-blue-100 bg-blue-50 p-4 text-sm text-blue-700"
    >
      {{ t("Exercise questions, attempts and reports are still handled by the legacy exercise tool in this batch.") }}
    </div>

    <BaseTable
      :is-loading="isLoading"
      :text-for-empty="t('No exercises found')"
      :total-items="exercises.length"
      :values="exercises"
      data-key="iid"
    >
      <Column
        :header="t('Title')"
        field="title"
        sortable
      >
        <template #body="{ data }">
          <div class="min-w-64">
            <div class="flex items-center gap-2">
              <span
                :class="chamiloIconToClass['multiple-marked']"
                class="ch-tool-icon"
                aria-hidden="true"
              />
              <span class="font-semibold text-gray-90">
                {{ displayText(data.title, t("Untitled")) }}
              </span>
            </div>
            <p
              v-if="displayText(data.description)"
              class="mt-1 text-xs text-gray-500"
            >
              {{ displayText(data.description) }}
            </p>
            <div class="mt-2 flex flex-wrap gap-2 text-xs">
              <span
                v-if="!data.visible"
                class="rounded-full bg-gray-100 px-2 py-0.5 text-gray-700"
              >
                {{ t("Hidden") }}
              </span>
              <span
                v-if="data.duration"
                class="rounded-full bg-blue-100 px-2 py-0.5 text-blue-700"
              >
                {{ t("Duration") }}: {{ formatDuration(data.duration) }}
              </span>
              <span
                v-if="data.maxAttempt"
                class="rounded-full bg-gray-100 px-2 py-0.5 text-gray-700"
              >
                {{ t("Attempts") }}: {{ data.maxAttempt }}
              </span>
            </div>
          </div>
        </template>
      </Column>

      <Column
        v-if="settings.allowExerciseCategories"
        :header="t('Category')"
        field="categoryTitle"
        sortable
      >
        <template #body="{ data }">
          {{ displayText(data.categoryTitle, "-") }}
        </template>
      </Column>

      <Column :header="t('Dates')">
        <template #body="{ data }">
          <div class="space-y-1 text-xs text-gray-600">
            <div>
              <span class="font-semibold text-gray-700">{{ t("Available from") }}:</span>
              {{ formatDate(data.startTime) }}
            </div>
            <div>
              <span class="font-semibold text-gray-700">{{ t("Until") }}:</span>
              {{ formatDate(data.endTime) }}
            </div>
          </div>
        </template>
      </Column>

      <Column :header="t('Status')">
        <template #body="{ data }">
          <span :class="['rounded-full px-2 py-1 text-xs font-semibold', availabilityBadgeClass(data.availabilityStatus)]">
            {{ availabilityLabel(data.availabilityStatus) }}
          </span>
        </template>
      </Column>

      <Column
        :header="t('Questions')"
        field="questionCount"
        sortable
      >
        <template #body="{ data }">
          <span class="font-semibold text-gray-800">{{ data.questionCount ?? 0 }}</span>
        </template>
      </Column>

      <Column
        v-if="canManage"
        :header="t('Attempts')"
        field="attemptCount"
        sortable
      >
        <template #body="{ data }">
          <span class="font-semibold text-gray-800">{{ data.attemptCount ?? 0 }}</span>
        </template>
      </Column>

      <Column
        :header="t('Actions')"
        class="w-48"
      >
        <template #body="{ data }">
          <div class="flex flex-wrap justify-end gap-1">
            <BaseButton
              v-if="data.canOpen"
              :label="t('Open exercise')"
              :to-url="legacyUrl('overview.php', { exerciseId: data.iid })"
              icon="play-box-outline"
              only-icon
              size="small"
              type="primary-text"
            />
            <BaseButton
              v-if="data.canOpen"
              :label="t('Open Vue player')"
              :route="{ name: 'ExercisePlayer', params: { ...route.params, exerciseId: data.iid }, query: getContextParams() }"
              icon="eye-on"
              only-icon
              size="small"
              type="primary-text"
            />
            <BaseButton
              v-if="data.canEdit"
              :label="t('Edit questions')"
              :route="{ name: 'ExerciseQuestions', params: { ...route.params, exerciseId: data.iid }, query: getContextParams() }"
              icon="edit"
              only-icon
              size="small"
              type="secondary-text"
            />
            <BaseButton
              v-if="data.canConfigure"
              :label="t('Configure')"
              :route="{ name: 'ExerciseEdit', params: { ...route.params, exerciseId: data.iid }, query: getContextParams() }"
              icon="settings"
              only-icon
              size="small"
              type="secondary-text"
            />
            <BaseButton
              v-if="data.canReport"
              :label="t('Results')"
              :route="{ name: 'ExerciseReport', params: { ...route.params, exerciseId: data.iid }, query: getContextParams() }"
              icon="tracking"
              only-icon
              size="small"
              type="primary-text"
            />
            <BaseButton
              v-if="data.canExport"
              :label="t('Export QTI2')"
              :to-url="legacyUrl('exercise.php', { exerciseId: data.iid, action: 'exportqti2' })"
              icon="export"
              only-icon
              size="small"
              type="primary-text"
            />
          </div>
        </template>
      </Column>
    </BaseTable>
  </section>
</template>

<script setup>
import { computed, onMounted, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import { chamiloIconToClass } from "../../components/basecomponents/ChamiloIcons"
import exerciseService from "../../services/exerciseService"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()

const exercises = ref([])
const categories = ref([])
const settings = ref({})
const canManage = ref(false)
const canCreate = ref(false)
const usesLegacyActions = ref(true)
const isLoading = ref(false)
const errorMessage = ref("")
const searchTerm = ref(getSearchQuery())
const selectedCategoryId = ref(getCategoryQuery())
const isSearchVisible = ref(Boolean(getSearchQuery() || getCategoryQuery()))
const activeSearch = computed(() => getSearchQuery())
const hasActiveFilters = computed(() => Boolean(getSearchQuery() || getCategoryQuery()))
const categoryOptions = computed(() => [
  { label: t("All categories"), value: 0 },
  ...categories.value.map((category) => ({ label: displayText(category.title, t("Untitled")), value: Number(category.id) })),
])

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

function getSearchQuery() {
  return String(getQueryValue(route.query.search) || "").trim()
}

function getCategoryQuery() {
  return Number(getQueryValue(route.query.categoryId) || 0)
}

function getListParams() {
  const params = { ...getContextParams() }
  const search = getSearchQuery()
  const categoryId = getCategoryQuery()

  if (search) {
    params.search = search
  }

  if (categoryId > 0) {
    params.categoryId = categoryId
  }

  return params
}

function buildQueryString(params = {}) {
  const query = new URLSearchParams()

  for (const [key, value] of Object.entries(params)) {
    if (value !== undefined && value !== null && String(value) !== "") {
      query.set(key, String(value))
    }
  }

  const queryString = query.toString()

  return queryString ? `?${queryString}` : ""
}

function legacyUrl(path, params = {}) {
  return `/main/exercise/${path}${buildQueryString({ ...getContextParams(), ...params })}`
}

function toggleSearchForm() {
  isSearchVisible.value = !isSearchVisible.value
}

function hideSearchForm() {
  searchTerm.value = getSearchQuery()
  selectedCategoryId.value = getCategoryQuery()
  isSearchVisible.value = false
}

async function applySearch() {
  const search = searchTerm.value.trim()
  const categoryId = Number(selectedCategoryId.value || 0)
  const query = { ...route.query }

  if (search) {
    query.search = search
  } else {
    delete query.search
  }

  if (categoryId > 0) {
    query.categoryId = categoryId
  } else {
    delete query.categoryId
  }

  await router.push({
    name: route.name,
    params: route.params,
    query,
  })

  isSearchVisible.value = false
}

async function clearSearch() {
  searchTerm.value = ""
  selectedCategoryId.value = 0
  await applySearch()
}

async function loadExercises() {
  isLoading.value = true
  errorMessage.value = ""

  try {
    const response = await exerciseService.getExerciseList(getListParams())
    exercises.value = Array.isArray(response.items) ? response.items : []
    categories.value = Array.isArray(response.categories) ? response.categories : []
    settings.value = response.settings || {}
    canManage.value = true === response.canManage
    canCreate.value = true === response.canCreate
    usesLegacyActions.value = true === response.usesLegacyActions
  } catch (error) {
    console.error("Error loading exercises", error)
    errorMessage.value = t("Could not load exercises")
  } finally {
    isLoading.value = false
  }
}

function formatDate(date) {
  if (!date) {
    return t("No date")
  }

  const parsedDate = new Date(date)
  if (Number.isNaN(parsedDate.getTime())) {
    return t("No date")
  }

  return parsedDate.toLocaleString()
}

function formatDuration(minutes) {
  const value = Number(minutes || 0)
  if (value <= 0) {
    return t("No limit")
  }

  return t("{0} min", [value])
}

function availabilityLabel(status) {
  switch (status) {
    case "not_started":
      return t("Not started")
    case "closed":
      return t("Closed")
    default:
      return t("Open")
  }
}

function availabilityBadgeClass(status) {
  switch (status) {
    case "not_started":
      return "bg-blue-100 text-blue-700"
    case "closed":
      return "bg-gray-100 text-gray-700"
    default:
      return "bg-green-100 text-green-700"
  }
}

function decodeHtml(value) {
  if (!value) {
    return ""
  }

  if (typeof document === "undefined") {
    return String(value)
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

onMounted(loadExercises)

watch(
  () => route.query,
  () => {
    searchTerm.value = getSearchQuery()
    selectedCategoryId.value = getCategoryQuery()
    loadExercises()
  },
)
</script>

<style scoped>
:deep(.exercise-list-toolbar__button) {
  min-width: 2.25rem;
  width: 2.25rem;
  height: 2.25rem;
}

:deep(.exercise-list-toolbar__button .p-button-icon) {
  font-size: 1.1rem;
}
</style>
