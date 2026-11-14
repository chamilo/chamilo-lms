<template>
  <section class="space-y-5">
    <div
      class="exercise-grade-by-question-toolbar flex w-fit flex-wrap items-center gap-1 rounded-xl border border-gray-20 bg-white px-2 py-1 shadow-sm"
    >
      <BaseButton
        class="exercise-grade-by-question-toolbar__button"
        :label="t('Back to learner score')"
        :route="{ name: 'ExerciseReport', params: getExerciseRouteParams(), query: getContextParams() }"
        :icon="safeIcon('back')"
        only-icon
        size="small"
        type="primary-text"
      />
    </div>

    <div class="border-b border-gray-20" />

    <header class="overflow-hidden rounded-xl border border-gray-20 bg-white shadow-sm">
      <div class="space-y-2 border-s-4 border-s-primary p-5">
        <h1 class="text-2xl font-semibold text-gray-90">
          {{ t("Grade by question") }}
        </h1>
        <p class="text-sm font-semibold text-gray-700">
          {{ title }}
        </p>
        <p class="text-sm text-gray-600">
          {{
            t(
              "Scores saved here only update the answer and the attempt total. Learners are not notified and the attempts stay pending until you validate the full review.",
            )
          }}
        </p>
      </div>
    </header>

    <div
      v-if="errorMessage"
      class="rounded-xl border border-danger/30 bg-danger/10 p-4 text-sm text-danger"
    >
      {{ errorMessage }}
    </div>

    <div
      v-if="lockedByGradebook"
      class="rounded-xl border border-warning/30 bg-warning/10 p-4 text-sm text-warning"
    >
      {{ t("This exercise is locked by gradebook.") }}
    </div>

    <div
      v-if="!isLoading && questions.length === 0 && !errorMessage"
      class="rounded-xl border border-gray-20 bg-white p-6 text-center text-sm text-gray-600 shadow-sm"
    >
      {{ t("This exercise has no question to grade manually.") }}
    </div>

    <template v-else-if="questions.length > 0">
      <div class="grid gap-3 rounded-xl border border-gray-20 bg-white p-4 shadow-sm md:grid-cols-[1fr_220px_auto]">
        <BaseSelect
          id="grade_by_question_question"
          v-model="selectedQuestionId"
          :label="t('Question')"
          :options="questionOptions"
          name="question"
          @change="onFiltersChange"
        />
        <BaseSelect
          id="grade_by_question_sort"
          v-model="sort"
          :label="t('Sort by')"
          :options="sortOptions"
          name="sort"
          @change="onFiltersChange"
        />
        <BaseCheckbox
          id="grade_by_question_only_unscored"
          v-model="onlyUnscored"
          :label="t('Only answers not scored yet')"
          name="only_unscored"
        />
      </div>

      <div
        v-if="isLoading"
        class="rounded-xl border border-gray-20 bg-white p-6 text-center text-sm text-gray-600 shadow-sm"
      >
        {{ t("Loading") }}...
      </div>

      <div
        v-else-if="items.length === 0"
        class="rounded-xl border border-gray-20 bg-white p-6 text-center text-sm text-gray-600 shadow-sm"
      >
        {{ onlyUnscored ? t("All answers to this question have a score.") : t("No answer to this question yet.") }}
      </div>

      <template v-else>
        <article
          v-for="item in items"
          :key="item.attemptId"
          class="overflow-hidden rounded-xl border border-gray-20 bg-white shadow-sm"
        >
          <div class="flex flex-col gap-2 border-b border-gray-20 p-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
              <h2 class="text-base font-semibold text-gray-90">{{ item.lastName }}, {{ item.firstName }}</h2>
              <p class="text-xs text-gray-500">{{ t("Submitted on") }} {{ formatDate(item.submittedAt) }}</p>
            </div>
            <span
              class="w-fit rounded-full px-3 py-1 text-xs font-semibold"
              :class="item.scored ? 'bg-success/10 text-success' : 'bg-warning/10 text-warning'"
            >
              {{ item.scored ? t("Scored") : t("Not scored yet") }}
            </span>
          </div>

          <div class="space-y-3 p-4">
            <p
              v-if="item.answer.kind === 'free_answer'"
              class="whitespace-pre-line text-sm text-gray-90"
              dir="auto"
            >
              {{ item.answer.text || t("No answer") }}
            </p>
            <template v-else-if="item.answer.files.length > 0">
              <div
                v-for="file in item.answer.files"
                :key="file.id"
                class="space-y-1"
              >
                <audio
                  v-if="isAudio(file)"
                  :src="file.url"
                  class="w-full max-w-xl"
                  controls
                  preload="none"
                />
                <a
                  :href="file.url"
                  class="text-sm text-primary underline"
                  rel="noopener"
                  target="_blank"
                >
                  {{ file.name }}
                </a>
              </div>
            </template>
            <p
              v-else-if="['annotation', 'onlyoffice'].includes(item.answer.kind)"
              class="text-sm text-gray-600"
            >
              {{ t("Open the full review to see this answer.") }}
            </p>
            <p
              v-else
              class="text-sm text-gray-600"
            >
              {{ t("No answer") }}
            </p>

            <div class="flex flex-wrap items-start gap-3">
              <div class="w-44">
                <BaseInputNumber
                  :id="`grade_by_question_score_${item.attemptId}`"
                  v-model="scores[item.attemptId]"
                  :disabled="lockedByGradebook || savingAttemptId === item.attemptId"
                  :label="`${t('Score')} / ${formatNumber(currentQuestion?.maxScore)}`"
                  :max="Number(currentQuestion?.maxScore || 0)"
                  :min="0"
                  :step="0.5"
                  name="score"
                />
              </div>
              <BaseButton
                :disabled="
                  lockedByGradebook || savingAttemptId === item.attemptId || !isValidScore(scores[item.attemptId])
                "
                :is-loading="savingAttemptId === item.attemptId"
                :label="t('Save score')"
                icon="save"
                type="primary"
                @click="saveScore(item)"
              />
              <BaseButton
                :label="t('Full review')"
                :route="{
                  name: 'ExerciseResult',
                  params: { ...getExerciseRouteParams(), attemptId: item.attemptId },
                  query: getReviewContextParams(),
                }"
                icon="eye-on"
                type="secondary-text"
              />
            </div>
          </div>
        </article>

        <div
          v-if="pageCount > 1"
          class="flex items-center justify-center gap-3"
        >
          <BaseButton
            :disabled="page <= 1"
            :label="t('Previous')"
            icon="back"
            only-icon
            type="primary-text"
            @click="goToPage(page - 1)"
          />
          <span class="text-sm text-gray-700">{{ t("Page {0} of {1}", [page, pageCount]) }}</span>
          <BaseButton
            :disabled="page >= pageCount"
            :label="t('Next')"
            icon="next"
            only-icon
            type="primary-text"
            @click="goToPage(page + 1)"
          />
        </div>
      </template>
    </template>
  </section>
</template>

<script setup>
import { computed, onMounted, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseCheckbox from "../../components/basecomponents/BaseCheckbox.vue"
import BaseInputNumber from "../../components/basecomponents/BaseInputNumber.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import { chamiloIconToClass } from "../../components/basecomponents/ChamiloIcons"
import { useNotification } from "../../composables/notification"
import exerciseService from "../../services/exerciseService"

const ITEMS_PER_PAGE = 20

const { t } = useI18n()
const route = useRoute()
const { showErrorNotification, showSuccessNotification } = useNotification()

const isLoading = ref(false)
const errorMessage = ref("")
const title = ref("")
const questions = ref([])
const items = ref([])
const totalItems = ref(0)
const lockedByGradebook = ref(false)
const selectedQuestionId = ref(null)
const sort = ref("date")
const onlyUnscored = ref(false)
const page = ref(1)
const scores = ref({})
const savingAttemptId = ref(null)
const availableIcons = Object.keys(chamiloIconToClass)

const sortOptions = computed(() => [
  { label: t("Submission date"), value: "date" },
  { label: t("Last name"), value: "lastname" },
])

const questionOptions = computed(() =>
  questions.value.map((question) => ({
    label: `${question.title || `#${question.id}`} (${t("Not scored yet")}: ${question.unscoredCount})`,
    value: question.id,
  })),
)

const currentQuestion = computed(() => questions.value.find((question) => question.id === selectedQuestionId.value))

const pageCount = computed(() => Math.max(1, Math.ceil(totalItems.value / ITEMS_PER_PAGE)))

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

function getReviewContextParams() {
  return {
    ...getContextParams(),
    review: "1",
    mode: "review",
  }
}

function getExerciseRouteParams() {
  return {
    node: route.params.node,
    exerciseId: route.params.exerciseId,
  }
}

function getExerciseId() {
  return Number(route.params.exerciseId || 0)
}

async function loadAnswers() {
  const exerciseId = getExerciseId()
  if (!exerciseId) {
    errorMessage.value = t("Invalid exercise")
    return
  }

  isLoading.value = true
  errorMessage.value = ""

  try {
    const response = await exerciseService.getExerciseRuntimeQuestionGrading(
      {
        ...getContextParams(),
        questionId: selectedQuestionId.value || undefined,
        sort: sort.value,
        onlyUnscored: onlyUnscored.value ? 1 : 0,
        page: page.value,
        itemsPerPage: ITEMS_PER_PAGE,
      },
      exerciseId,
    )

    title.value = response.title || ""
    questions.value = Array.isArray(response.questions) ? response.questions : []
    selectedQuestionId.value = response.questionId || null
    items.value = Array.isArray(response.items) ? response.items : []
    totalItems.value = Number(response.totalItems || 0)
    lockedByGradebook.value = Boolean(response.lockedByGradebook)
    scores.value = Object.fromEntries(items.value.map((item) => [item.attemptId, Number(item.marks || 0)]))
  } catch (error) {
    console.error("Error loading the answers to grade", error)
    errorMessage.value = t("Could not load the answers to grade")
  } finally {
    isLoading.value = false
  }
}

function onFiltersChange() {
  page.value = 1
  loadAnswers()
}

function goToPage(target) {
  page.value = Math.min(Math.max(1, target), pageCount.value)
  loadAnswers()
}

function isValidScore(value) {
  const maxScore = Number(currentQuestion.value?.maxScore || 0)

  return typeof value === "number" && Number.isFinite(value) && value >= 0 && value <= maxScore
}

function isAudio(file) {
  return String(file.mimeType || "").startsWith("audio/")
}

async function saveScore(item) {
  const marks = scores.value[item.attemptId]
  if (!isValidScore(marks)) {
    return
  }

  savingAttemptId.value = item.attemptId

  try {
    await exerciseService.saveExerciseRuntimeQuestionScore(
      { questionId: selectedQuestionId.value, marks },
      getContextParams(),
      getExerciseId(),
      item.attemptId,
    )

    if (!item.scored) {
      item.scored = true
      const question = currentQuestion.value
      if (question && question.unscoredCount > 0) {
        question.unscoredCount -= 1
      }
    }
    item.marks = marks
    showSuccessNotification(t("Score saved"))
  } catch (error) {
    showErrorNotification(error)
  } finally {
    savingAttemptId.value = null
  }
}

function formatNumber(value) {
  const number = Number(value)
  if (value === null || value === undefined || Number.isNaN(number)) {
    return "—"
  }

  return number.toFixed(2).replace(/\.00$/, "")
}

function formatDate(value) {
  if (!value) {
    return "—"
  }

  const date = new Date(value)

  return Number.isNaN(date.getTime()) ? "—" : date.toLocaleString()
}

onMounted(loadAnswers)

watch(onlyUnscored, onFiltersChange)

watch(
  () => [route.params.exerciseId, route.query.cid, route.query.sid, route.query.gid],
  () => {
    selectedQuestionId.value = null
    page.value = 1
    loadAnswers()
  },
)
</script>

<style scoped>
:deep(.exercise-grade-by-question-toolbar__button) {
  min-width: 2.5rem;
  width: 2.5rem;
  height: 2.5rem;
}

:deep(.exercise-grade-by-question-toolbar__button .p-button-icon) {
  font-size: 1.25rem;
}
</style>
