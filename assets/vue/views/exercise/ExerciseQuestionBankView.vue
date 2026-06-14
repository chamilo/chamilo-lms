<template>
  <section class="space-y-5">
    <div class="flex flex-wrap items-center gap-1 rounded-xl border border-gray-20 bg-white px-2 py-1 shadow-sm w-fit">
      <BaseButton
        :label="t('Back to questions')"
        :route="{ name: 'ExerciseQuestions', params: { ...route.params, exerciseId }, query: getContextParams() }"
        icon="back"
        only-icon
        size="small"
        type="primary-text"
      />
    </div>

    <div class="border-b border-gray-20" />

    <section class="space-y-1">
      <h1 class="text-xl font-semibold text-gray-90">
        {{ t("Question bank") }}
      </h1>
      <p class="text-sm text-gray-600">
        {{ t("Reuse existing questions in this test.") }}
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
      class="rounded-xl border border-gray-20 bg-white p-4 shadow-sm"
      @submit.prevent="applyFilters"
    >
      <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-5">
        <BaseInputText
          id="question-bank-search"
          v-model="filters.search"
          :label="t('Search')"
          name="search"
        />
        <BaseSelect
          id="question-bank-category"
          v-model="filters.categoryId"
          :label="t('Questions category')"
          name="categoryId"
          :options="categoryOptions"
          option-label="label"
          option-value="value"
        />
        <BaseSelect
          id="question-bank-source-exercise"
          v-model="filters.sourceExerciseId"
          :label="t('Test')"
          name="sourceExerciseId"
          :options="exerciseOptions"
          option-label="label"
          option-value="value"
        />
        <BaseSelect
          id="question-bank-difficulty"
          v-model="filters.difficulty"
          :label="t('Difficulty')"
          name="difficulty"
          :options="difficultyOptions"
          option-label="label"
          option-value="value"
        />
        <BaseSelect
          id="question-bank-question-type"
          v-model="filters.questionType"
          :label="t('Answer type')"
          name="questionType"
          :options="questionTypeOptions"
          option-label="label"
          option-value="value"
        />
      </div>

      <div class="mt-4 flex flex-wrap gap-2">
        <BaseButton
          :is-loading="isLoading"
          :label="t('Filter')"
          icon="search"
          :is-submit="true"
          type="primary"
        />
        <BaseButton
          :label="t('Clear search')"
          icon="close"
          type="plain"
          @click="clearFilters"
        />
      </div>
    </form>

    <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-gray-20 bg-white px-4 py-3 shadow-sm">
      <div class="text-sm text-gray-700">
        {{ t("{0} questions found", [totalItems]) }}
      </div>
      <div class="flex flex-wrap gap-2">
        <BaseButton
          :disabled="0 === selectedQuestionIds.length || isSaving"
          :is-loading="isSaving"
          :label="t('Add selected questions to the test')"
          icon="plus"
          type="success"
          @click="addSelectedQuestions"
        />
      </div>
    </div>

    <BaseTable
      :is-loading="isLoading"
      :text-for-empty="t('No questions found')"
      :total-items="totalItems"
      :values="questions"
      data-key="id"
    >
      <Column class="w-12">
        <template #header>
          <input
            :checked="areAllReusableQuestionsSelected"
            class="h-4 w-4 rounded border-gray-25"
            name="selectAllQuestions"
            type="checkbox"
            @change="toggleAllQuestions"
          />
        </template>
        <template #body="{ data }">
          <input
            v-if="data.canReuse"
            v-model="selectedQuestionIds"
            class="h-4 w-4 rounded border-gray-25"
            name="questionIds[]"
            type="checkbox"
            :value="Number(data.id)"
          />
        </template>
      </Column>

      <Column :header="t('Question')">
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

      <Column
        :header="t('Type')"
        class="w-32"
      >
        <template #body="{ data }">
          <div class="flex items-center justify-center">
            <img
              v-if="data.typeIcon"
              :alt="t(data.typeLabel)"
              class="h-9 w-9 object-contain"
              :src="`/img/icons/64/${data.typeIcon}`"
              @error="useFallbackIcon"
            />
          </div>
          <div class="mt-1 text-center text-xs text-gray-600">
            {{ t(data.typeLabel) }}
          </div>
        </template>
      </Column>

      <Column :header="t('Questions category')">
        <template #body="{ data }">
          {{ displayText(data.categoryLabel, '-') }}
        </template>
      </Column>

      <Column
        :header="t('Difficulty')"
        class="w-24"
      >
        <template #body="{ data }">
          <span class="font-semibold text-gray-800">{{ data.difficulty }}</span>
        </template>
      </Column>

      <Column
        :header="t('Score')"
        class="w-24"
      >
        <template #body="{ data }">
          {{ formatScore(data.score) }}
        </template>
      </Column>

      <Column
        :header="t('Actions')"
        class="w-40"
      >
        <template #body="{ data }">
          <div class="flex justify-end gap-1">
            <span
              v-if="data.alreadyInExercise"
              class="rounded-full bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-700"
            >
              {{ t("Already in test") }}
            </span>
            <span
              v-else-if="!data.canReuse"
              class="rounded-full bg-yellow-100 px-2 py-1 text-xs font-semibold text-yellow-800"
              :title="t(data.blockedReason || 'This question cannot be reused here.')"
            >
              {{ t("Not compatible") }}
            </span>
            <BaseButton
              v-else
              :disabled="isSaving"
              :label="t('Use this question in the test as a link (not a copy)')"
              icon="plus"
              only-icon
              size="small"
              type="success-text"
              @click="addQuestion(data)"
            />
          </div>
        </template>
      </Column>
    </BaseTable>

    <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-gray-20 bg-white px-4 py-3 shadow-sm">
      <BaseButton
        :disabled="1 >= filters.page || isLoading"
        :label="t('Previous')"
        icon="back"
        type="plain"
        @click="changePage(filters.page - 1)"
      />
      <span class="text-sm text-gray-700">
        {{ t("Page {0} of {1}", [filters.page, pageCount]) }}
      </span>
      <BaseButton
        :disabled="filters.page >= pageCount || isLoading"
        :label="t('Next')"
        icon="next"
        type="plain"
        @click="changePage(filters.page + 1)"
      />
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import exerciseService from "../../services/exerciseService"

const { t } = useI18n()
const route = useRoute()

const exerciseId = Number(getQueryValue(route.params.exerciseId) || 0)
const questions = ref([])
const categoryOptions = ref([])
const exerciseOptions = ref([])
const difficultyOptions = ref([])
const questionTypeOptions = ref([])
const selectedQuestionIds = ref([])
const csrfToken = ref("")
const totalItems = ref(0)
const isLoading = ref(false)
const isSaving = ref(false)
const errorMessage = ref("")
const infoMessage = ref("")
const filters = reactive({
  page: Number(getQueryValue(route.query.page) || 1),
  itemsPerPage: Number(getQueryValue(route.query.itemsPerPage) || 20),
  search: String(getQueryValue(route.query.search) || ""),
  categoryId: Number(getQueryValue(route.query.categoryId) || 0),
  sourceExerciseId: Number(getQueryValue(route.query.sourceExerciseId) || 0),
  difficulty: Number(getQueryValue(route.query.difficulty) || -1),
  questionType: Number(getQueryValue(route.query.questionType) || -1),
})

const reusableQuestionIds = computed(() => questions.value.filter((question) => question.canReuse).map((question) => Number(question.id)))
const areAllReusableQuestionsSelected = computed(
  () => reusableQuestionIds.value.length > 0 && reusableQuestionIds.value.every((id) => selectedQuestionIds.value.includes(id)),
)
const pageCount = computed(() => Math.max(1, Math.ceil(totalItems.value / Math.max(1, filters.itemsPerPage))))

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

function getBankParams() {
  return {
    ...getContextParams(),
    page: filters.page,
    itemsPerPage: filters.itemsPerPage,
    search: filters.search,
    categoryId: filters.categoryId,
    sourceExerciseId: filters.sourceExerciseId,
    difficulty: filters.difficulty,
    questionType: filters.questionType,
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

function formatScore(score) {
  const value = Number(score || 0)

  return Number.isInteger(value) ? String(value) : value.toFixed(2)
}

function useFallbackIcon(event) {
  event.target.src = "/img/icons/64/new_question.png"
}

function applyFilters() {
  filters.page = 1
  loadQuestionBank()
}

function clearFilters() {
  filters.page = 1
  filters.search = ""
  filters.categoryId = 0
  filters.sourceExerciseId = 0
  filters.difficulty = -1
  filters.questionType = -1
  loadQuestionBank()
}

function changePage(page) {
  filters.page = Math.max(1, Math.min(page, pageCount.value))
  loadQuestionBank()
}

function toggleAllQuestions(event) {
  selectedQuestionIds.value = event.target.checked ? [...reusableQuestionIds.value] : []
}

async function addQuestion(question) {
  await runReuseAction([Number(question.id)])
}

async function addSelectedQuestions() {
  await runReuseAction(selectedQuestionIds.value)
}

async function runReuseAction(questionIds) {
  if (isSaving.value) {
    return
  }

  const safeQuestionIds = [...new Set(questionIds.map((id) => Number(id)).filter((id) => id > 0))]
  if (0 === safeQuestionIds.length) {
    errorMessage.value = t("Select at least one question.")
    return
  }

  isSaving.value = true
  errorMessage.value = ""
  infoMessage.value = ""

  try {
    const response = await exerciseService.saveExerciseQuestionBankAction(
      {
        exerciseId,
        action: "reuse",
        questionIds: safeQuestionIds,
        submittedCsrfToken: csrfToken.value,
      },
      getContextParams(),
      exerciseId,
    )
    infoMessage.value = response?.message ? t(response.message) : t("Questions added to the test")
    selectedQuestionIds.value = []
    await loadQuestionBank()
  } catch (error) {
    console.error("Error processing exercise question bank action", error)
    errorMessage.value = error?.response?.data?.detail || error?.response?.data?.["hydra:description"] || t("Could not update the question bank")
  } finally {
    isSaving.value = false
  }
}

async function loadQuestionBank() {
  if (!exerciseId) {
    errorMessage.value = t("A valid exercise id is required.")
    return
  }

  isLoading.value = true
  errorMessage.value = ""

  try {
    const response = await exerciseService.getExerciseQuestionBank(getBankParams(), exerciseId)
    questions.value = Array.isArray(response.items) ? response.items : []
    categoryOptions.value = Array.isArray(response.categoryOptions) ? response.categoryOptions : []
    exerciseOptions.value = Array.isArray(response.exerciseOptions) ? response.exerciseOptions : []
    difficultyOptions.value = Array.isArray(response.difficultyOptions) ? response.difficultyOptions : []
    questionTypeOptions.value = Array.isArray(response.questionTypeOptions) ? response.questionTypeOptions : []
    csrfToken.value = response.csrfToken || ""
    totalItems.value = Number(response.totalItems || 0)
    filters.page = Number(response.page || filters.page || 1)
    filters.itemsPerPage = Number(response.itemsPerPage || filters.itemsPerPage || 20)
    selectedQuestionIds.value = selectedQuestionIds.value.filter((id) => reusableQuestionIds.value.includes(Number(id)))
  } catch (error) {
    console.error("Error loading exercise question bank", error)
    errorMessage.value = error?.response?.data?.detail || error?.response?.data?.["hydra:description"] || t("Could not load the question bank")
  } finally {
    isLoading.value = false
  }
}

onMounted(loadQuestionBank)
</script>
