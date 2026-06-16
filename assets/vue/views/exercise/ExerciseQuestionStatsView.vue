<template>
  <section class="space-y-5">
    <div class="exercise-question-stats-toolbar flex w-fit flex-wrap items-center gap-1 rounded-xl border border-gray-20 bg-white px-2 py-1 shadow-sm">
      <BaseButton
        class="exercise-question-stats-toolbar__button"
        :label="t('Back to learner score')"
        :route="{ name: 'ExerciseReport', params: getExerciseRouteParams(), query: getContextParams() }"
        :icon="safeIcon('back')"
        only-icon
        size="small"
        type="primary-text"
      />
      <BaseButton
        class="exercise-question-stats-toolbar__button"
        :label="t('Report by question')"
        :route="{ name: 'ExerciseReportByQuestion', params: getExerciseRouteParams(), query: getContextParams() }"
        :icon="safeIcon('view-table', 'table')"
        only-icon
        size="small"
        type="primary-text"
      />
      <BaseButton
        v-if="actionUrls.questionStatsPdf"
        class="exercise-question-stats-toolbar__button"
        :label="t('Export to PDF')"
        :to-url="actionUrls.questionStatsPdf"
        :icon="safeIcon('file-pdf', 'file-excel')"
        only-icon
        size="small"
        type="primary-text"
      />
      <BaseButton
        class="exercise-question-stats-toolbar__button"
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
              {{ displayText(title, t('Question statistics')) }}
            </h1>
            <p class="text-sm font-semibold text-gray-700">
              {{ t('Question statistics') }}
            </p>
            <div
              v-if="description"
              class="exercise-question-stats-html text-sm text-gray-700"
              v-html="description"
            />
          </div>
          <div class="flex flex-wrap gap-2">
            <span class="rounded-full bg-info/10 px-3 py-1 text-sm font-semibold text-info">
              {{ t('Questions') }}: {{ summary.totalQuestions || 0 }}
            </span>
            <span class="rounded-full bg-success/10 px-3 py-1 text-sm font-semibold text-success">
              {{ t('Answered') }}: {{ summary.totalAnswered || 0 }}
            </span>
            <span class="rounded-full bg-warning/10 px-3 py-1 text-sm font-semibold text-warning">
              {{ t('Wrong answer') }}: {{ summary.totalWrong || 0 }}
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

    <BaseTable
      :is-loading="isLoading"
      :text-for-empty="t('No question statistics found')"
      :total-items="questions.length"
      :values="questions"
      data-key="id"
    >
      <Column :header="t('Question')" field="title" sortable>
        <template #body="{ data }">
          <div class="max-w-xl space-y-1">
            <div class="font-semibold text-gray-90">
              {{ displayText(data.title, '-') }}
            </div>
            <div class="text-xs text-gray-500">
              #{{ data.questionId }} · {{ t(data.typeLabel || 'Question') }}
            </div>
          </div>
        </template>
      </Column>
      <Column :header="t('Question type')" field="typeLabel" sortable>
        <template #body="{ data }">
          {{ t(data.typeLabel || 'Question') }}
        </template>
      </Column>
      <Column :header="t('Number of times the question was answered')" field="answeredAttempts" sortable>
        <template #body="{ data }">
          {{ data.answeredAttempts || 0 }}
        </template>
      </Column>
      <Column :header="t('Lowest score')" field="lowestScore" sortable>
        <template #body="{ data }">
          {{ formatNumber(data.lowestScore) }}
        </template>
      </Column>
      <Column :header="t('Average score')" field="averageScore" sortable>
        <template #body="{ data }">
          {{ formatNumber(data.averageScore) }}
        </template>
      </Column>
      <Column :header="t('Highest score')" field="highestScore" sortable>
        <template #body="{ data }">
          {{ formatNumber(data.highestScore) }}
        </template>
      </Column>
      <Column :header="t('Score')" field="maxScore" sortable>
        <template #body="{ data }">
          {{ formatNumber(data.maxScore) }}
        </template>
      </Column>
      <Column :header="`${t('Wrong answer')} / ${t('Total')}`" field="wrongAttempts" sortable>
        <template #body="{ data }">
          <span class="font-semibold text-gray-90">
            {{ data.wrongAttempts || 0 }} / {{ data.answeredAttempts || 0 }}
          </span>
        </template>
      </Column>
      <Column :header="'%'" field="wrongPercentage" sortable>
        <template #body="{ data }">
          {{ formatNumber(data.wrongPercentage) }} %
        </template>
      </Column>
    </BaseTable>
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

async function loadQuestionStats() {
  const exerciseId = getExerciseId()
  if (!exerciseId) {
    errorMessage.value = t("Invalid exercise")
    return
  }

  isLoading.value = true
  errorMessage.value = ""

  try {
    const response = await exerciseService.getExerciseQuestionStats(getContextParams(), exerciseId)
    title.value = response.title || ""
    description.value = response.description || ""
    questions.value = Array.isArray(response.questions) ? response.questions : []
    summary.value = response.summary || {}
    actionUrls.value = response.actionUrls || {}
  } catch (error) {
    console.error("Error loading exercise question statistics", error)
    errorMessage.value = t("Could not load question statistics")
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

onMounted(loadQuestionStats)

watch(
  () => [route.params.exerciseId, route.query.cid, route.query.sid, route.query.gid],
  () => {
    loadQuestionStats()
  },
)
</script>

<style scoped>
:deep(.exercise-question-stats-toolbar__button) {
  min-width: 2.5rem;
  width: 2.5rem;
  height: 2.5rem;
}

:deep(.exercise-question-stats-toolbar__button .p-button-icon) {
  font-size: 1.25rem;
}

.exercise-question-stats-html :deep(img) {
  max-width: 100%;
  height: auto;
}

.exercise-question-stats-html :deep(p) {
  margin-bottom: 0.25rem;
}

.exercise-question-stats-html :deep(p:last-child) {
  margin-bottom: 0;
}
</style>
