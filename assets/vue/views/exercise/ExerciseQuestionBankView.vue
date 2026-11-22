<template>
  <section class="space-y-5">
    <div class="flex flex-wrap items-center gap-1 rounded-xl border border-gray-20 bg-white px-2 py-1 shadow-sm w-fit">
      <BaseButton
        :label="isGlobalMode ? t('Return to exercises list') : t('Back to the questions')"
        :route="backRoute"
        icon="back"
        only-icon
        size="small"
        type="primary-text"
      />
    </div>

    <div class="border-b border-gray-20" />

    <section class="space-y-1">
      <h1 class="text-xl font-semibold text-gray-90">
        {{ t("Recycle existing questions") }}
      </h1>
      <p class="text-sm text-gray-600">
        {{ isGlobalMode ? t("Manage all questions") : t("Add question to test") }}
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

      <div
        v-if="filterableFields.length"
        class="mt-4 grid gap-4 md:grid-cols-2 lg:grid-cols-4"
      >
        <template
          v-for="field in filterableFields"
          :key="field.id"
        >
          <BaseSelect
            v-if="isChoiceField(field)"
            :id="`question-bank-filter-${field.id}`"
            v-model="fieldFilters[field.id]"
            :label="field.label"
            :name="`questionField_${field.id}`"
            :options="choiceOptions(field, t('All'))"
          />
          <BaseTextArea
            v-else-if="field.type === 2"
            :id="`question-bank-filter-${field.id}`"
            v-model="fieldFilters[field.id]"
            :label="field.label"
            :name="`questionField_${field.id}`"
            rows="2"
          />
          <BaseInputText
            v-else
            :id="`question-bank-filter-${field.id}`"
            v-model="fieldFilters[field.id]"
            :label="field.label"
            :name="`questionField_${field.id}`"
          />
        </template>
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
          type="tertiary-alternative"
          @click="clearFilters"
        />
      </div>
    </form>

    <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-gray-20 bg-white px-4 py-3 shadow-sm">
      <div class="space-y-1 text-sm text-gray-700">
        <div>{{ t("Questions") }}: {{ totalItems }}</div>
        <div v-if="!isGlobalMode" id="question-bank-exercise-count">
          {{ t("Questions in test") }}: {{ exerciseQuestionCount }}
        </div>
      </div>
      <div
        v-if="!isGlobalMode"
        class="flex flex-wrap gap-2"
      >
        <BaseButton
          :disabled="0 === selectedQuestionIds.length || isSaving || isLoading"
          :is-loading="isSaving"
          :label="t('Add selected questions to the test')"
          icon="plus"
          type="success"
          @click="addSelectedQuestions"
        />
      </div>
      <div v-else class="flex flex-wrap items-center gap-2">
        <span class="text-sm text-gray-600">{{ t("Selected") }}: {{ selectedQuestionIds.length }}</span>
        <BaseButton
          :disabled="0 === selectedQuestionIds.length || isSaving || isLoading"
          :label="t('Assign scores')"
          icon="edit"
          type="secondary"
          @click="openScoreEditor"
        />
      </div>
    </div>

    <p class="text-sm text-gray-600">
      {{ t("Statistics count completed, graded attempts in this course and session, using the current maximum question score. Partial scores count as below full score.") }}
    </p>
    <p v-if="isGlobalMode" class="text-sm text-gray-600">
      {{ t("Only unused single-answer questions owned by this context can receive bulk scores.") }}
    </p>

    <form
      v-if="scoreEditorOpen"
      class="space-y-3 rounded-xl border border-gray-20 bg-white p-4 shadow-sm"
      @submit.prevent="confirmScores"
    >
      <h2 class="font-semibold">{{ t("Assign scores") }} ({{ selectedQuestionIds.length }})</h2>
      <div class="grid gap-4 md:grid-cols-2">
        <BaseInputText
          id="question-bank-correct-score"
          v-model="scoreDraft.correct"
          :label="t('Correct score')"
          name="correctScore"
          type="number"
          min="0.01"
          max="9999.99"
          step="0.01"
          required
        />
        <BaseInputText
          id="question-bank-incorrect-score"
          v-model="scoreDraft.incorrect"
          :label="t('Incorrect score')"
          name="incorrectScore"
          type="number"
          min="-9999.99"
          max="0"
          step="0.01"
          required
        />
      </div>
      <div class="flex flex-wrap justify-end gap-2">
        <BaseButton :label="t('Cancel')" icon="close" type="secondary" @click="scoreEditorOpen = false" />
        <BaseButton
          :disabled="!validScores || isSaving || isLoading || selectedQuestionIds.length === 0"
          :label="t('Save')"
          :is-loading="isSaving"
          :is-submit="true"
          icon="save"
          type="primary"
        />
      </div>
    </form>

    <form
      v-if="classificationQuestion"
      class="space-y-3 rounded-xl border border-gray-20 bg-white p-4 shadow-sm"
      @submit.prevent="saveClassification"
    >
      <h2 class="font-semibold">
        {{ t("Edit classification") }}: {{ displayText(classificationQuestion.title, t("Untitled")) }}
      </h2>
      <div class="grid gap-4 md:grid-cols-2">
        <template v-for="field in editableFields" :key="field.id">
          <BaseSelect
            v-if="isChoiceField(field)"
            :id="`question-bank-classification-${field.id}`"
            v-model="classificationDraft[field.id]"
            :label="field.label"
            :name="`classification_${field.id}`"
            :options="choiceOptions(field, '--')"
          />
          <BaseTextArea
            v-else-if="field.type === 2"
            :id="`question-bank-classification-${field.id}`"
            v-model="classificationDraft[field.id]"
            :label="field.label"
            :name="`classification_${field.id}`"
            rows="4"
          />
          <BaseInputText
            v-else
            :id="`question-bank-classification-${field.id}`"
            v-model="classificationDraft[field.id]"
            :label="field.label"
            :name="`classification_${field.id}`"
            maxlength="4000"
          />
        </template>
      </div>
      <div class="flex flex-wrap justify-end gap-2">
        <BaseButton :label="t('Cancel')" icon="close" type="secondary" @click="classificationQuestion = null" />
        <BaseButton
          :disabled="!classificationChanged || isSaving || isLoading"
          :label="t('Save')"
          :is-loading="isSaving"
          :is-submit="true"
          icon="save"
          type="primary"
        />
      </div>
    </form>

    <BaseTable
      :key="tableKey"
      v-model:rows="filters.itemsPerPage"
      v-model:sort-field="filters.sortField"
      v-model:sort-order="sortOrder"
      :is-loading="isLoading"
      lazy
      :text-for-empty="t('No questions found')"
      :total-items="totalItems"
      :values="questions"
      data-key="id"
      @page="handleTablePage"
      @sort="handleTableSort"
    >
      <Column class="w-12">
        <template #header>
          <input
            :checked="areAllEligibleQuestionsSelected"
            :aria-label="t('Select all')"
            :disabled="isLoading || isSaving || eligibleQuestionIds.length === 0"
            class="h-4 w-4 rounded border-gray-25"
            name="selectAllQuestions"
            type="checkbox"
            @change="toggleAllQuestions"
          />
        </template>
        <template #body="{ data }">
          <input
            v-if="isEligible(data)"
            :id="`question-bank-select-${data.id}`"
            v-model="selectedQuestionIds"
            :aria-label="displayText(data.title, t('Untitled'))"
            :disabled="isLoading || isSaving"
            class="h-4 w-4 rounded border-gray-25"
            name="questionIds[]"
            type="checkbox"
            :value="Number(data.id)"
          />
        </template>
      </Column>

      <Column :header="t('Question')" field="title" sortable>
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
            <p v-if="data.alreadyInExercise" class="text-xs font-semibold text-gray-600">{{ t("Already in test") }}</p>
            <template v-for="field in questionFields" :key="field.id">
              <p v-if="data.fieldValues?.[field.id]" class="break-words text-xs text-gray-600">
                <span class="font-semibold">{{ field.label }}:</span> {{ formatFieldValue(field, data.fieldValues[field.id]) }}
              </p>
            </template>
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

      <Column :header="t('Questions category')" field="category" sortable>
        <template #body="{ data }">
          {{ displayText(data.categoryLabel, '-') }}
        </template>
      </Column>

      <Column
        :header="t('Difficulty')"
        class="w-24"
        field="difficulty"
        sortable
      >
        <template #body="{ data }">
          <span class="font-semibold text-gray-800">{{ data.difficulty }}</span>
        </template>
      </Column>

      <Column
        :header="t('Score')"
        class="w-24"
        field="score"
        sortable
      >
        <template #body="{ data }">
          {{ formatScore(data.score) }}
        </template>
      </Column>

      <Column :header="t('Occurrences')" field="occurrences" sortable>
        <template #body="{ data }">{{ data.occurrences }}</template>
      </Column>
      <Column :header="t('Full-score attempts')" field="correctAttempts" sortable>
        <template #body="{ data }">{{ data.correctAttempts }}</template>
      </Column>
      <Column :header="t('Below-full-score attempts')" field="incorrectAttempts" sortable>
        <template #body="{ data }">{{ data.incorrectAttempts }}</template>
      </Column>

      <Column
        :header="t('Actions')"
        class="w-40"
      >
        <template #body="{ data }">
          <div class="flex justify-end gap-1">
            <template v-if="isGlobalMode">
              <BaseButton
                v-if="data.canClassify"
                :disabled="isSaving || isLoading"
                :label="t('Edit classification')"
                icon="tag-outline"
                only-icon
                size="small"
                type="secondary-text"
                @click="openClassification(data)"
              />
              <BaseButton
                v-if="data.canEdit"
                :label="t('Edit')"
                icon="edit"
                only-icon
                :route="{ name: 'ExerciseGlobalQuestionEdit', params: { node: route.params.node, questionId: data.id }, query: getContextParams() }"
                size="small"
                type="secondary-text"
              />
              <BaseButton
                v-else
                :disabled="true"
                :label="getEditBlockedLabel(data)"
                icon="edit"
                only-icon
                size="small"
                type="secondary-text"
              />
              <BaseButton
                v-if="data.canDelete"
                :disabled="isSaving || isLoading"
                :label="t('Delete')"
                icon="delete"
                only-icon
                size="small"
                type="danger-text"
                @click="confirmDeleteQuestion(data)"
              />
              <BaseButton
                v-else
                :disabled="true"
                :label="getDeleteBlockedLabel(data)"
                icon="delete"
                only-icon
                size="small"
                type="danger-text"
              />
            </template>
            <template v-else>
              <BaseButton
                v-if="data.canReuse"
                :disabled="isSaving || isLoading"
                :label="t('Use this question in the test as a link (not a copy)')"
                icon="plus"
                only-icon
                size="small"
                type="success-text"
                @click="addQuestion(data)"
              />
              <BaseButton
                v-else
                :disabled="true"
                :label="getReuseBlockedLabel(data)"
                icon="plus"
                only-icon
                size="small"
                type="success-text"
              />
            </template>
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
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import BaseTextArea from "../../components/basecomponents/BaseTextArea.vue"
import { useConfirmation } from "../../composables/useConfirmation"
import { useStudentViewRefresh } from "../../composables/useStudentViewRefresh"
import exerciseService from "../../services/exerciseService"

const { t } = useI18n()
const route = useRoute()
const { requireConfirmation } = useConfirmation()

const exerciseId = Number(getQueryValue(route.params.exerciseId) || 0)
const responseGlobalMode = ref(0 >= exerciseId)
const questions = ref([])
const tableKey = ref(0)
const categoryOptions = ref([])
const exerciseOptions = ref([])
const difficultyOptions = ref([])
const questionTypeOptions = ref([])
const selectedQuestionIds = ref([])
const totalItems = ref(0)
const exerciseQuestionCount = ref(0)
const questionFields = ref([])
const fieldFilters = ref({})
const classificationQuestion = ref(null)
const classificationDraft = ref({})
const scoreEditorOpen = ref(false)
const scoreDraft = reactive({ correct: "1", incorrect: "0" })
const sortFields = ["title", "category", "difficulty", "score", "occurrences", "correctAttempts", "incorrectAttempts"]
let loadVersion = 0
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
  sortField: sortFields.includes(getQueryValue(route.query.sortField)) ? getQueryValue(route.query.sortField) : "title",
  sortDirection: getQueryValue(route.query.sortDirection) === "DESC" ? "DESC" : "ASC",
})
const sortOrder = computed({
  get: () => (filters.sortDirection === "DESC" ? -1 : 1),
  set: (value) => { filters.sortDirection = value === -1 ? "DESC" : "ASC" },
})

const isGlobalMode = computed(() => responseGlobalMode.value)
const backRoute = computed(() => {
  if (isGlobalMode.value) {
    return { name: "ExerciseList", params: { node: route.params.node }, query: getContextParams() }
  }

  return { name: "ExerciseQuestions", params: { ...route.params, exerciseId }, query: getContextParams() }
})
const filterableFields = computed(() => questionFields.value.filter((field) => field.filter))
const editableFields = computed(() => questionFields.value.filter((field) => field.changeable))
const eligibleQuestionIds = computed(() => questions.value.filter(isEligible).map((question) => Number(question.id)))
const areAllEligibleQuestionsSelected = computed(
  () => eligibleQuestionIds.value.length > 0 && eligibleQuestionIds.value.every((id) => selectedQuestionIds.value.includes(id)),
)
const validScores = computed(() => {
  const correct = Number(scoreDraft.correct)
  const incorrect = Number(scoreDraft.incorrect)
  return String(scoreDraft.correct ?? "").trim() !== "" && String(scoreDraft.incorrect ?? "").trim() !== "" &&
    Number.isFinite(correct) && correct >= 0.01 && correct <= 9999.99 &&
    Number.isFinite(incorrect) && incorrect >= -9999.99 && incorrect <= 0 &&
    Math.abs(correct * 100 - Math.round(correct * 100)) < 0.000001 &&
    Math.abs(incorrect * 100 - Math.round(incorrect * 100)) < 0.000001
})
const classificationChanged = computed(() => editableFields.value.some((field) =>
  String(classificationDraft.value[field.id] ?? "") !== String(classificationQuestion.value?.fieldValues?.[field.id] ?? ""),
))

function isEligible(question) {
  return isGlobalMode.value ? question.canAssignScores : question.canReuse
}

function isChoiceField(field) {
  return [3, 4].includes(field.type)
}

function choiceOptions(field, emptyLabel) {
  return [{ label: emptyLabel, value: "" }, ...field.options]
}

function formatFieldValue(field, value) {
  return field.options.find((option) => option.value === value)?.label ?? value
}

function clearEditors() {
  selectedQuestionIds.value = []
  scoreEditorOpen.value = false
  classificationQuestion.value = null
}

function openScoreEditor() {
  classificationQuestion.value = null
  scoreEditorOpen.value = true
}

function openClassification(question) {
  scoreEditorOpen.value = false
  classificationQuestion.value = question
  classificationDraft.value = Object.fromEntries(editableFields.value.map((field) => [field.id, String(question.fieldValues?.[field.id] ?? "")]))
}

function confirmScores() {
  if (!validScores.value || isSaving.value || isLoading.value) return
  const ids = [...selectedQuestionIds.value]
  const scores = { correctScore: Number(scoreDraft.correct), incorrectScore: Number(scoreDraft.incorrect) }
  requireConfirmation({
    message: t("Assign these scores to the selected questions?"),
    accept: () => runBankAction("assign_scores", ids, scores),
  })
}

async function saveClassification() {
  if (!classificationQuestion.value || !classificationChanged.value || isSaving.value || isLoading.value) return
  const question = classificationQuestion.value
  const fieldValues = Object.fromEntries(editableFields.value
    .filter((field) => String(classificationDraft.value[field.id] ?? "") !== String(question.fieldValues?.[field.id] ?? ""))
    .map((field) => [field.id, String(classificationDraft.value[field.id] ?? "")]))
  await runBankAction("classify", [Number(question.id)], { fieldValues })
}

function getQueryValue(value) {
  return Array.isArray(value) ? value[0] : value
}

function getContextParams() {
  const params = {
    cid: getQueryValue(route.query.cid),
    sid: getQueryValue(route.query.sid),
    gid: getQueryValue(route.query.gid),
  }

  for (const key of [
    "origin",
    "lp_id",
    "learnpath_id",
    "node",
    "parent",
    "lp_parent_id",
    "lp_item_id",
    "type",
    "returnToLp",
    "gradebook",
    "lpTool",
  ]) {
    const value = getQueryValue(route.query[key])
    if (value !== undefined && value !== null && String(value) !== "") {
      params[key] = value
    }
  }

  return params
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
    sortField: filters.sortField,
    sortDirection: filters.sortDirection,
    ...Object.fromEntries(filterableFields.value.map((field) => [`questionField_${field.id}`, fieldFilters.value[field.id] ?? ""])),
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

function resetTablePagination() {
  tableKey.value += 1
}

function applyFilters() {
  clearEditors()
  filters.page = 1
  resetTablePagination()
  loadQuestionBank()
}

function clearFilters() {
  filters.page = 1
  filters.search = ""
  filters.categoryId = 0
  filters.sourceExerciseId = 0
  filters.difficulty = -1
  filters.questionType = -1
  filters.sortField = "title"
  filters.sortDirection = "ASC"
  fieldFilters.value = Object.fromEntries(filterableFields.value.map((field) => [field.id, ""]))
  clearEditors()
  resetTablePagination()
  loadQuestionBank()
}

function handleTablePage(event) {
  clearEditors()
  filters.page = Number(event.page || 0) + 1
  filters.itemsPerPage = Number(event.rows || filters.itemsPerPage || 20)
  loadQuestionBank()
}

function handleTableSort(event) {
  if (!sortFields.includes(event.sortField)) return
  clearEditors()
  filters.sortField = event.sortField
  filters.sortDirection = event.sortOrder === -1 ? "DESC" : "ASC"
  filters.page = 1
  loadQuestionBank()
}

function getEditBlockedLabel(question) {
  if (question.usedInActiveTest) {
    return t("This question is used in an active test and cannot be edited here.")
  }

  return t("This question cannot be edited here.")
}

function getDeleteBlockedLabel(question) {
  if (question.usedInActiveTest) {
    return t("This question is used in an active test and cannot be deleted here.")
  }

  return t("This question cannot be deleted because your access is limited by the platform settings.")
}

function getReuseBlockedLabel(question) {
  if (question.alreadyInExercise) {
    return t("Already in test")
  }

  return t(question.blockedReason || "This question cannot be reused here.")
}

function toggleAllQuestions(event) {
  selectedQuestionIds.value = event.target.checked ? [...eligibleQuestionIds.value] : []
  if (selectedQuestionIds.value.length === 0) scoreEditorOpen.value = false
}

function confirmDeleteQuestion(question) {
  requireConfirmation({
    message: t("Are you sure you want to delete the question?"),
    accept: () => deleteQuestion(question),
  })
}

async function addQuestion(question) {
  await runReuseAction([Number(question.id)])
}

async function addSelectedQuestions() {
  await runReuseAction(selectedQuestionIds.value)
}

async function deleteQuestion(question) {
  await runBankAction("delete", [Number(question.id)])
}

async function runReuseAction(questionIds) {
  await runBankAction("reuse", questionIds)
}

async function runBankAction(action, questionIds, extra = {}) {
  if (isSaving.value || isLoading.value) {
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
        action,
        questionIds: safeQuestionIds,
        ...extra,
      },
      getContextParams(),
      isGlobalMode.value ? null : exerciseId,
    )
    infoMessage.value = response?.message ? t(response.message) : t("Question bank updated")
    clearEditors()
    if (action === "assign_scores" || action === "classify") {
      filters.page = 1
      resetTablePagination()
    }
    await loadQuestionBank()
  } catch (error) {
    console.error("Error processing exercise question bank action", error)
    errorMessage.value = error?.response?.data?.detail || error?.response?.data?.["hydra:description"] || t("Could not update the question bank")
  } finally {
    isSaving.value = false
  }
}

async function loadQuestionBank() {
  const version = ++loadVersion
  isLoading.value = true
  errorMessage.value = ""

  try {
    const response = await exerciseService.getExerciseQuestionBank(getBankParams(), isGlobalMode.value ? null : exerciseId)
    if (version !== loadVersion) return
    responseGlobalMode.value = true === response.globalMode || 0 >= exerciseId
    questions.value = Array.isArray(response.items) ? response.items : []
    categoryOptions.value = Array.isArray(response.categoryOptions) ? response.categoryOptions : []
    exerciseOptions.value = Array.isArray(response.exerciseOptions) ? response.exerciseOptions : []
    difficultyOptions.value = Array.isArray(response.difficultyOptions) ? response.difficultyOptions : []
    questionTypeOptions.value = Array.isArray(response.questionTypeOptions) ? response.questionTypeOptions : []
    totalItems.value = Number(response.totalItems || 0)
    exerciseQuestionCount.value = Number(response.exerciseQuestionCount || 0)
    questionFields.value = Array.isArray(response.questionFields) ? response.questionFields : []
    fieldFilters.value = Object.fromEntries(filterableFields.value.map((field) => [field.id, fieldFilters.value[field.id] ?? ""]))
    filters.page = Number(response.page || filters.page || 1)
    filters.itemsPerPage = Number(response.itemsPerPage || filters.itemsPerPage || 20)
    selectedQuestionIds.value = selectedQuestionIds.value.filter((id) => eligibleQuestionIds.value.includes(Number(id)))
  } catch (error) {
    if (version !== loadVersion) return
    clearEditors()
    questions.value = []
    totalItems.value = 0
    exerciseQuestionCount.value = 0
    questionFields.value = []
    fieldFilters.value = {}
    console.error("Error loading exercise question bank", error)
    errorMessage.value = error?.response?.data?.detail || error?.response?.data?.["hydra:description"] || t("Could not load the question bank")
  } finally {
    if (version === loadVersion) isLoading.value = false
  }
}

onMounted(loadQuestionBank)
useStudentViewRefresh(loadQuestionBank, { before: clearEditors })
</script>
