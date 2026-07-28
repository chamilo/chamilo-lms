<template>
  <section class="space-y-5">
    <div class="exercise-report-by-question-toolbar flex w-fit flex-wrap items-center gap-1 rounded-xl border border-gray-20 bg-white px-2 py-1 shadow-sm">
      <BaseButton
        class="exercise-report-by-question-toolbar__button"
        :label="t('Back to learner score')"
        :route="{ name: 'ExerciseReport', params: getExerciseRouteParams(), query: getContextParams() }"
        :icon="safeIcon('back')"
        only-icon
        size="small"
        type="primary-text"
      />
      <BaseButton
        class="exercise-report-by-question-toolbar__button"
        :label="t('Question stats')"
        :route="{ name: 'ExerciseQuestionStats', params: getExerciseRouteParams(), query: getContextParams() }"
        :icon="safeIcon('tracking', 'view-table')"
        only-icon
        size="small"
        type="primary-text"
      />
      <BaseButton
        v-if="actionUrls.reportByQuestionPdf"
        class="exercise-report-by-question-toolbar__button"
        :label="t('Export to PDF')"
        :to-url="actionUrls.reportByQuestionPdf"
        :icon="safeIcon('file-pdf', 'file-excel')"
        only-icon
        size="small"
        type="primary-text"
      />
      <BaseButton
        class="exercise-report-by-question-toolbar__button"
        :label="t('Live results')"
        :route="{ name: 'ExerciseLiveResults', params: getExerciseRouteParams(), query: getContextParams() }"
        :icon="safeIcon('usage', 'tracking')"
        only-icon
        size="small"
        type="primary-text"
      />
    </div>

    <div class="border-b border-gray-20" />

    <header class="overflow-hidden rounded-xl border border-gray-20 bg-white shadow-sm">
      <div class="border-l-4 border-l-primary p-5">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
          <div class="space-y-2">
            <h1 class="text-2xl font-semibold text-gray-90">
              {{ displayText(title, t('Report by question')) }}
            </h1>
            <p class="text-sm font-semibold text-gray-700">
              {{ t('Answer distribution') }}
            </p>
            <div
              v-if="description"
              class="exercise-report-by-question-html text-sm text-gray-700"
              v-html="description"
            />
          </div>
          <div class="flex flex-wrap gap-2">
            <span class="rounded-full bg-info/10 px-3 py-1 text-sm font-semibold text-info">
              {{ t('Questions') }}: {{ summary.totalQuestions || 0 }}
            </span>
            <span class="rounded-full bg-success/10 px-3 py-1 text-sm font-semibold text-success">
              {{ t('Configured answers') }}: {{ summary.totalAnswers || 0 }}
            </span>
            <span class="rounded-full bg-warning/10 px-3 py-1 text-sm font-semibold text-warning">
              {{ t('Selection') }}: {{ summary.totalSelections || 0 }}
            </span>
          </div>
        </div>
      </div>
    </header>

    <div
      v-if="errorMessage"
      class="rounded-xl border border-danger/30 bg-danger/10 p-4 text-sm text-danger"
    >
      {{ errorMessage }}
    </div>

    <div
      v-if="summary.specialCountingQuestions"
      class="rounded-xl border border-info/30 bg-info/10 p-4 text-sm text-info"
    >
      {{ t('Detailed answer counting for some question types is not available in the migrated report yet.') }}
    </div>

    <div
      v-if="isLoading"
      class="rounded-xl border border-gray-20 bg-white p-6 text-center text-sm text-gray-600 shadow-sm"
    >
      {{ t('Loading') }}...
    </div>

    <div
      v-else-if="questions.length === 0 && !errorMessage"
      class="rounded-xl border border-gray-20 bg-white p-6 text-center text-sm text-gray-600 shadow-sm"
    >
      {{ t('No report by question data found') }}
    </div>

    <template v-else>
      <article
        v-for="question in questions"
        :key="question.id"
        class="overflow-hidden rounded-xl border border-gray-20 bg-white shadow-sm"
      >
      <div class="border-l-4 border-l-primary p-4">
        <div class="flex flex-col gap-2 lg:flex-row lg:items-start lg:justify-between">
          <div class="space-y-1">
            <h2 class="text-lg font-semibold text-gray-90">
              {{ displayText(question.title, '-') }}
            </h2>
            <p class="text-xs text-gray-500">
              #{{ question.questionId }} · {{ t(question.typeLabel || 'Question') }} · {{ t('Score') }}: {{ formatNumber(question.maxScore) }}
            </p>
          </div>
          <span class="w-fit rounded-full bg-gray-15 px-3 py-1 text-sm font-semibold text-gray-700">
            {{ t('Selection') }}: {{ question.totalSelections || 0 }}
          </span>
        </div>
      </div>

      <div
        v-if="question.usesSpecialCounting && !question.countingAvailable"
        class="border-t border-gray-20 bg-info/5 px-4 py-3 text-sm text-info"
      >
        {{ t('Detailed answer counting for this question type is not available in the migrated report yet.') }}
      </div>

      <BaseTable
        :text-for-empty="t('No answer distribution found')"
        :total-items="question.answers.length"
        :values="question.answers"
        data-key="id"
      >
        <Column :header="t('Answer')" field="answer" sortable>
          <template #body="{ data }">
            <div class="max-w-xl space-y-1">
              <div class="font-semibold text-gray-90">
                {{ displayText(data.answer, '-') }}
              </div>
              <div class="text-xs text-gray-500">
                #{{ data.answerId }} · {{ t('Score') }}: {{ formatNumber(data.score) }}
              </div>
            </div>
          </template>
        </Column>
        <Column :header="t('Correct')" field="correct" sortable>
          <template #body="{ data }">
            <span
              class="rounded-full px-2 py-1 text-xs font-semibold"
              :class="!question.usesSpecialCounting && data.correct ? 'bg-success/10 text-success' : 'bg-gray-15 text-gray-700'"
            >
              {{ question.usesSpecialCounting ? '—' : data.correct ? t('Yes') : t('No') }}
            </span>
          </template>
        </Column>
        <Column :header="t('Number of times this answer was selected')" field="selectedCount" sortable>
          <template #body="{ data }">
            <span class="font-semibold text-gray-90">
              {{ formatCount(data.selectedCount) }}
            </span>
          </template>
        </Column>
        <Column :header="t('Selection')" field="selectedPercentage" sortable>
          <template #body="{ data }">
            {{ data.selectedCount === null ? '—' : `${formatNumber(data.selectedPercentage)} %` }}
          </template>
        </Column>
      </BaseTable>
      </article>
    </template>
  </section>
</template>

<script setup>
import { onMounted, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import { chamiloIconToClass } from "../../components/basecomponents/ChamiloIcons"
import exerciseService from "../../services/exerciseService"

const { t } = useI18n()
const route = useRoute()

const isLoading = ref(false)
const errorMessage = ref("")
const title = ref("")
const description = ref("")
const questions = ref([])
const summary = ref({})
const actionUrls = ref({})
const availableIcons = Object.keys(chamiloIconToClass)

function safeIcon(icon, fallback = "information") {
  if (icon && availableIcons.includes(icon)) {
    return icon
  }

  if (fallback && availableIcons.includes(fallback)) {
    return fallback
  }

  return availableIcons[0] || "information"
}

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

function getBaseRouteParams() {
  return {
    node: route.params.node,
  }
}

function getExerciseRouteParams() {
  return {
    ...getBaseRouteParams(),
    exerciseId: route.params.exerciseId,
  }
}

function getExerciseId() {
  return Number(route.params.exerciseId || 0)
}

async function loadReportByQuestion() {
  const exerciseId = getExerciseId()
  if (!exerciseId) {
    errorMessage.value = t("Invalid exercise")
    return
  }

  isLoading.value = true
  errorMessage.value = ""

  try {
    const response = await exerciseService.getExerciseReportByQuestion(getContextParams(), exerciseId)
    title.value = response.title || ""
    description.value = response.description || ""
    questions.value = Array.isArray(response.questions) ? response.questions : []
    summary.value = response.summary || {}
    actionUrls.value = response.actionUrls || {}
  } catch (error) {
    console.error("Error loading exercise report by question", error)
    errorMessage.value = t("Could not load report by question")
  } finally {
    isLoading.value = false
  }
}

function formatNumber(value) {
  if (value === null || value === undefined || value === "") {
    return "—"
  }

  const number = Number(value)
  if (Number.isNaN(number)) {
    return "—"
  }

  return number.toFixed(2).replace(/\.00$/, "")
}

function formatCount(value) {
  if (value === null || value === undefined) {
    return "—"
  }

  return Number(value) || 0
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

onMounted(loadReportByQuestion)

watch(
  () => [route.params.exerciseId, route.query.cid, route.query.sid, route.query.gid],
  () => {
    loadReportByQuestion()
  },
)
</script>

<style scoped>
:deep(.exercise-report-by-question-toolbar__button) {
  min-width: 2.5rem;
  width: 2.5rem;
  height: 2.5rem;
}

:deep(.exercise-report-by-question-toolbar__button .p-button-icon) {
  font-size: 1.25rem;
}

.exercise-report-by-question-html :deep(img) {
  max-width: 100%;
  height: auto;
}

.exercise-report-by-question-html :deep(p) {
  margin-bottom: 0.25rem;
}

.exercise-report-by-question-html :deep(p:last-child) {
  margin-bottom: 0;
}
</style>
