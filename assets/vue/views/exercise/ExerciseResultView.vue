<template>
  <section class="space-y-5">
    <div class="flex w-fit flex-wrap items-center gap-1 rounded-xl border border-gray-20 bg-white px-2 py-1 shadow-sm">
      <BaseButton
        :label="t('Back to exercises')"
        :route="{ name: 'ExerciseList', params: getBaseRouteParams(), query: getContextParams() }"
        icon="back"
        only-icon
        size="small"
        type="primary-text"
      />
      <BaseButton
        :label="t('Open exercise player')"
        :route="{ name: 'ExercisePlayer', params: getPlayerRouteParams(), query: getContextParams() }"
        icon="play-box-outline"
        only-icon
        size="small"
        type="primary-text"
      />
      <BaseButton
        v-if="legacyUrls.legacyResult"
        :label="t('Open legacy result')"
        :to-url="legacyUrls.legacyResult"
        icon="tracking"
        only-icon
        size="small"
        type="secondary-text"
      />
    </div>

    <div class="border-b border-gray-20" />

    <div
      v-if="isLoading"
      class="rounded-xl border border-gray-20 bg-white p-4 text-sm text-gray-700 shadow-sm"
    >
      {{ t("Loading") }}
    </div>

    <div
      v-if="errorMessage"
      class="rounded-xl border border-danger/30 bg-danger/10 p-4 text-sm text-danger"
    >
      {{ errorMessage }}
    </div>

    <div
      v-if="correctionError"
      class="rounded-xl border border-danger/30 bg-danger/10 p-4 text-sm text-danger"
    >
      {{ correctionError }}
    </div>

    <div
      v-if="correctionMessage"
      class="rounded-xl border border-success/30 bg-success/10 p-4 text-sm text-success"
    >
      {{ correctionMessage }}
    </div>

    <template v-if="!isLoading && !errorMessage">
      <header class="overflow-hidden rounded-xl border border-gray-20 bg-white shadow-sm">
        <div class="border-l-4 border-l-primary p-5">
          <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="space-y-2">
              <h1 class="text-2xl font-semibold text-gray-90">
                {{ displayText(title, t("Exercise result")) }}
              </h1>
              <div
                v-if="description"
                class="exercise-result-html text-sm text-gray-700"
                v-html="description"
              />
            </div>

            <div
              v-if="visibility.showTotalScore && attempt.passed !== null && attempt.passed !== undefined"
              class="inline-flex w-fit items-center gap-2 rounded-full px-3 py-1 text-sm font-semibold"
              :class="attempt.passed ? 'bg-success/10 text-success' : 'bg-danger/10 text-danger'"
            >
              <span
                class="mdi"
                :class="attempt.passed ? 'mdi-check-circle' : 'mdi-alert-circle'"
              />
              {{ attempt.passed ? t("Passed") : t("Failed") }}
            </div>
          </div>

          <div
            v-if="attempt.textWhenFinished"
            class="exercise-result-html mt-4 rounded-lg border border-info/30 bg-support-1 p-3 text-sm text-support-4"
            v-html="attempt.textWhenFinished"
          />

          <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-lg border border-gray-20 bg-gray-10 p-4">
              <div class="flex items-center justify-between gap-3">
                <div>
                  <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                    {{ t("Attempt") }}
                  </div>
                  <div class="mt-1 text-xl font-semibold text-gray-90">#{{ attempt.attemptId }}</div>
                  <div class="text-xs text-gray-600">{{ t(attempt.status || "completed") }}</div>
                </div>
                <span class="mdi mdi-clipboard-check-outline text-2xl text-primary" />
              </div>
            </div>

            <div class="rounded-lg border border-gray-20 bg-gray-10 p-4">
              <div class="flex items-center justify-between gap-3">
                <div>
                  <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                    {{ t("Score") }}
                  </div>
                  <div
                    v-if="visibility.showTotalScore"
                    class="mt-1 text-xl font-semibold text-gray-90"
                  >
                    {{ formatNumber(attempt.score) }} / {{ formatNumber(attempt.maxScore) }}
                  </div>
                  <div
                    v-else
                    class="mt-1 text-sm text-gray-600"
                  >
                    {{ t("The score is hidden for this exercise.") }}
                  </div>
                </div>
                <span class="mdi mdi-counter text-2xl text-primary" />
              </div>
            </div>

            <div class="rounded-lg border border-gray-20 bg-gray-10 p-4">
              <div class="flex items-center justify-between gap-3">
                <div>
                  <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                    {{ t("Percentage") }}
                  </div>
                  <div
                    v-if="visibility.showTotalScore"
                    class="mt-1 text-xl font-semibold text-gray-90"
                  >
                    {{ formatNumber(attempt.percentage) }}%
                  </div>
                  <div
                    v-else
                    class="mt-1 text-sm text-gray-600"
                  >
                    —
                  </div>
                </div>
                <span class="mdi mdi-percent-outline text-2xl text-primary" />
              </div>
            </div>

            <div class="rounded-lg border border-gray-20 bg-gray-10 p-4">
              <div class="flex items-center justify-between gap-3">
                <div>
                  <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                    {{ t("Duration") }}
                  </div>
                  <div class="mt-1 text-xl font-semibold text-gray-90">
                    {{ formatSeconds(attempt.duration) }}
                  </div>
                  <div
                    v-if="attempt.completedAt"
                    class="text-xs text-gray-600"
                  >
                    {{ formatDate(attempt.completedAt) }}
                  </div>
                </div>
                <span class="mdi mdi-clock-outline text-2xl text-primary" />
              </div>
            </div>
          </div>

          <div
            v-if="!visibility.showCorrections"
            class="mt-4 rounded-lg border border-warning/30 bg-warning/10 p-3 text-sm text-warning"
          >
            {{ t("Corrections are hidden according to the exercise result settings.") }}
          </div>
        </div>
      </header>

      <div class="space-y-4">
        <article
          v-for="question in questions"
          :key="question.id"
          class="overflow-hidden rounded-xl border bg-white shadow-sm"
          :class="questionCardClass(question)"
        >
          <div class="border-b border-gray-20 bg-gray-10 p-4">
            <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
              <div class="flex gap-3">
                <span
                  class="mdi mt-1 text-xl"
                  :class="questionStatusIconClass(question)"
                />
                <div class="space-y-1">
                  <div class="flex flex-wrap items-center gap-2 text-xs font-semibold uppercase tracking-wide text-gray-500">
                    <span>{{ questionLabel(question.position) }}</span>
                    <span>·</span>
                    <span>{{ t(question.typeLabel) }}</span>
                  </div>
                  <h2
                    class="exercise-result-html text-lg font-semibold text-gray-90"
                    v-html="question.title"
                  />
                  <div
                    v-if="question.description"
                    class="exercise-result-html text-sm text-gray-700"
                    v-html="question.description"
                  />
                </div>
              </div>

              <div class="flex flex-wrap items-center gap-2 md:justify-end">
                <span
                  v-if="visibility.showQuestionScore"
                  class="rounded-full border px-3 py-1 text-xs font-semibold"
                  :class="questionScoreClass(question)"
                >
                  {{ t("Score") }}: {{ formatNumber(question.score) }} / {{ formatNumber(question.maxScore) }}
                </span>
                <span
                  v-if="visibility.showQuestionScore && questionResultBadgeLabel(question)"
                  class="rounded-full px-3 py-1 text-xs font-semibold"
                  :class="questionResultBadgeClass(question)"
                >
                  {{ questionResultBadgeLabel(question) }}
                </span>
                <span
                  v-if="question.pendingCorrection"
                  class="rounded-full bg-warning/10 px-3 py-1 text-xs font-semibold text-warning"
                >
                  {{ t("Pending correction") }}
                </span>
              </div>
            </div>
          </div>

          <div class="space-y-3 p-4">
            <template v-if="question.answer.kind === 'choice' || question.answer.kind === 'dropdown'">
              <div
                v-for="choice in question.answer.choices || question.answer.options"
                :key="choice.id"
                class="rounded-lg border p-3 text-sm"
                :class="choiceClass(choice)"
              >
                <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                  <div class="flex flex-1 gap-2">
                    <span
                      class="mdi mt-0.5 text-base"
                      :class="choiceIconClass(choice)"
                    />
                    <div class="exercise-result-html flex-1" v-html="choice.answer" />
                  </div>
                  <div class="flex flex-wrap gap-2 text-xs font-semibold">
                    <span
                      v-if="choice.selected"
                      class="rounded-full bg-info/10 px-2 py-1 text-info"
                    >
                      {{ t("Your answer") }}
                    </span>
                    <span
                      v-if="choice.correct"
                      class="rounded-full bg-success/10 px-2 py-1 text-success"
                    >
                      {{ t("Correct answer") }}
                    </span>
                  </div>
                </div>
                <div
                  v-if="choice.comment"
                  class="exercise-result-html mt-2 rounded bg-white/70 p-2 text-xs text-gray-700"
                  v-html="choice.comment"
                />
              </div>
            </template>

            <template v-else-if="question.answer.kind === 'true_false'">
              <div
                v-for="choice in question.answer.choices"
                :key="choice.id"
                class="rounded-lg border p-3 text-sm"
                :class="trueFalseClass(choice)"
              >
                <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                  <div class="exercise-result-html font-medium text-gray-90" v-html="choice.answer" />
                  <span
                    class="mdi text-base"
                    :class="trueFalseIconClass(choice)"
                  />
                </div>
                <div class="mt-2 flex flex-wrap gap-2 text-xs font-semibold">
                  <span class="rounded-full bg-info/10 px-2 py-1 text-info">
                    {{ t("Your answer") }}: {{ choice.selectedOptionLabel || t("No answer") }}
                  </span>
                  <span
                    v-if="choice.correctOptionLabel"
                    class="rounded-full bg-success/10 px-2 py-1 text-success"
                  >
                    {{ t("Correct answer") }}: {{ choice.correctOptionLabel }}
                  </span>
                </div>
                <div
                  v-if="choice.comment"
                  class="exercise-result-html mt-2 rounded bg-white/70 p-2 text-xs text-gray-700"
                  v-html="choice.comment"
                />
              </div>
            </template>

            <template v-else-if="question.answer.kind === 'fill_blanks'">
              <div class="space-y-2 rounded-lg border border-gray-20 bg-gray-10 p-3 text-sm">
                <div
                  v-for="blank in question.answer.blanks"
                  :key="blank.position"
                  class="flex flex-col gap-2 rounded border bg-white p-3 md:flex-row md:items-center md:justify-between"
                  :class="blankClass(blank)"
                >
                  <div class="flex items-center gap-2">
                    <span
                      class="mdi text-base"
                      :class="blankIconClass(blank)"
                    />
                    <div>
                      <span class="font-semibold">{{ t("Blank {0}", [blank.position]) }}:</span>
                      {{ blank.studentAnswer || t("No answer") }}
                    </div>
                  </div>
                  <div
                    v-if="blank.correctAnswer"
                    class="text-sm font-semibold text-success"
                  >
                    {{ t("Correct answer") }}: {{ blank.correctAnswer }}
                  </div>
                </div>
              </div>
            </template>

            <template v-else-if="question.answer.kind === 'matching'">
              <div
                v-for="prompt in question.answer.prompts"
                :key="prompt.id"
                class="rounded-lg border p-3 text-sm"
                :class="matchingClass(prompt)"
              >
                <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                  <div class="exercise-result-html font-medium text-gray-90" v-html="prompt.answer" />
                  <span
                    class="mdi text-base"
                    :class="matchingIconClass(prompt)"
                  />
                </div>
                <div class="mt-2 grid gap-2 text-sm md:grid-cols-2">
                  <div class="rounded bg-info/10 p-2 text-info">
                    <span class="font-semibold">{{ t("Your answer") }}:</span>
                    {{ displayText(prompt.selectedOptionAnswer, t("No answer")) }}
                  </div>
                  <div
                    v-if="prompt.correctOptionAnswer"
                    class="rounded bg-success/10 p-2 text-success"
                  >
                    <span class="font-semibold">{{ t("Correct answer") }}:</span>
                    {{ displayText(prompt.correctOptionAnswer) }}
                  </div>
                </div>
                <div
                  v-if="prompt.comment"
                  class="exercise-result-html mt-2 rounded bg-white/70 p-2 text-xs text-gray-700"
                  v-html="prompt.comment"
                />
              </div>
            </template>

            <template v-else-if="question.answer.kind === 'upload_answer'">
              <div class="space-y-3">
                <div class="rounded-lg border border-gray-20 bg-gray-10 p-3">
                  <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">
                    {{ t("Learner file") }}
                  </div>
                  <div
                    v-if="question.answer.files?.length"
                    class="space-y-2"
                  >
                    <a
                      v-for="file in question.answer.files"
                      :key="file.id || file.name"
                      class="inline-flex items-center gap-2 rounded border border-primary/30 bg-white px-3 py-2 text-sm font-semibold text-primary hover:bg-support-1"
                      :href="file.url"
                      rel="noopener noreferrer"
                      target="_blank"
                    >
                      <BaseIcon icon="download" size="small" />
                      {{ file.name || t("Download file") }}
                    </a>
                  </div>
                  <div
                    v-else
                    class="text-sm text-gray-600"
                  >
                    {{ t("No uploaded file") }}
                  </div>
                </div>

                <div
                  v-if="question.pendingCorrection"
                  class="rounded-lg border border-warning/30 bg-warning/10 p-3 text-sm text-warning"
                >
                  {{ t("This answer is pending teacher correction.") }}
                </div>

                <div
                  v-if="question.answer.teacherComment"
                  class="rounded-lg border border-info/30 bg-support-1 p-3 text-sm text-support-4"
                >
                  <div class="mb-1 text-xs font-semibold uppercase tracking-wide">
                    {{ t("Teacher comment") }}
                  </div>
                  {{ question.answer.teacherComment }}
                </div>

                <div
                  v-if="question.canCorrect && correctionForms[question.id]"
                  class="rounded-lg border border-gray-20 bg-white p-4"
                >
                  <div class="mb-3 flex items-center gap-2 text-sm font-semibold text-gray-800">
                    <BaseIcon icon="edit" size="small" />
                    {{ t("Teacher correction") }}
                  </div>
                  <div class="grid gap-3 md:grid-cols-[220px_1fr_auto] md:items-start">
                    <BaseInputNumber
                      v-model="correctionForms[question.id].marks"
                      :disabled="correctionSavingQuestionId === question.id"
                      :id="correctionScoreInputId(question)"
                      :label="t('Correction score')"
                      :max="Number(question.maxScore || 0)"
                      :min="0"
                      :step="0.5"
                      name="correction_score"
                    />
                    <BaseTextArea
                      v-model="correctionForms[question.id].teacherComment"
                      :disabled="correctionSavingQuestionId === question.id"
                      :id="correctionCommentInputId(question)"
                      :label="t('Teacher comment')"
                      name="teacher_comment"
                      rows="3"
                    />
                    <BaseButton
                      :disabled="correctionSavingQuestionId === question.id"
                      :is-loading="correctionSavingQuestionId === question.id"
                      :label="t('Save correction')"
                      icon="content-save"
                      type="success"
                      @click="saveManualCorrection(question)"
                    />
                  </div>
                </div>
              </div>
            </template>

            <template v-else-if="question.answer.kind === 'free_answer'">
              <div class="space-y-3">
                <div class="rounded-lg border border-gray-20 bg-gray-10 p-3">
                  <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">
                    {{ t("Learner answer") }}
                  </div>
                  <div class="whitespace-pre-wrap text-sm text-gray-800">
                    {{ question.answer.studentAnswer || t("No answer") }}
                  </div>
                </div>

                <div
                  v-if="question.pendingCorrection"
                  class="rounded-lg border border-warning/30 bg-warning/10 p-3 text-sm text-warning"
                >
                  {{ t("This answer is pending teacher correction.") }}
                </div>

                <div
                  v-if="question.answer.teacherComment"
                  class="rounded-lg border border-info/30 bg-support-1 p-3 text-sm text-support-4"
                >
                  <div class="mb-1 text-xs font-semibold uppercase tracking-wide">
                    {{ t("Teacher comment") }}
                  </div>
                  {{ question.answer.teacherComment }}
                </div>

                <div
                  v-if="question.canCorrect && correctionForms[question.id]"
                  class="rounded-lg border border-gray-20 bg-white p-4"
                >
                  <div class="mb-3 flex items-center gap-2 text-sm font-semibold text-gray-800">
                    <BaseIcon icon="edit" size="small" />
                    {{ t("Teacher correction") }}
                  </div>
                  <div class="grid gap-3 md:grid-cols-[220px_1fr_auto] md:items-start">
                    <BaseInputNumber
                      v-model="correctionForms[question.id].marks"
                      :disabled="correctionSavingQuestionId === question.id"
                      :id="correctionScoreInputId(question)"
                      :label="t('Correction score')"
                      :max="Number(question.maxScore || 0)"
                      :min="0"
                      :step="0.5"
                      name="correction_score"
                    />
                    <BaseTextArea
                      v-model="correctionForms[question.id].teacherComment"
                      :disabled="correctionSavingQuestionId === question.id"
                      :id="correctionCommentInputId(question)"
                      :label="t('Teacher comment')"
                      name="teacher_comment"
                      rows="3"
                    />
                    <BaseButton
                      :disabled="correctionSavingQuestionId === question.id"
                      :is-loading="correctionSavingQuestionId === question.id"
                      :label="t('Save correction')"
                      icon="content-save"
                      type="success"
                      @click="saveManualCorrection(question)"
                    />
                  </div>
                </div>
              </div>
            </template>

            <template v-else>
              <div class="rounded-lg border border-warning/30 bg-warning/10 p-3 text-sm text-warning">
                {{ t("This question type is not available in the Vue result review yet.") }}
              </div>
            </template>

            <div
              v-if="question.feedback"
              class="exercise-result-html rounded-lg border border-info/30 bg-support-1 p-3 text-sm text-support-4"
              v-html="question.feedback"
            />
          </div>
        </article>
      </div>
    </template>
  </section>
</template>

<script setup>
import { onMounted, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import BaseInputNumber from "../../components/basecomponents/BaseInputNumber.vue"
import BaseTextArea from "../../components/basecomponents/BaseTextArea.vue"
import exerciseService from "../../services/exerciseService"

const { t } = useI18n()
const route = useRoute()

const isLoading = ref(false)
const errorMessage = ref("")
const title = ref("")
const description = ref("")
const attempt = ref({})
const visibility = ref({})
const questions = ref([])
const legacyUrls = ref({})
const correctionForms = ref({})
const correctionSavingQuestionId = ref(null)
const correctionError = ref("")
const correctionMessage = ref("")

function getQueryValue(value) {
  return Array.isArray(value) ? value[0] : value
}

function getContextParams() {
  return {
    cid: getQueryValue(route.query.cid),
    sid: getQueryValue(route.query.sid),
    gid: getQueryValue(route.query.gid),
    origin: getQueryValue(route.query.origin),
    learnpath_id: getQueryValue(route.query.learnpath_id),
    learnpath_item_id: getQueryValue(route.query.learnpath_item_id),
    learnpath_item_view_id: getQueryValue(route.query.learnpath_item_view_id),
  }
}

function getBaseRouteParams() {
  return {
    node: route.params.node,
  }
}

function getPlayerRouteParams() {
  return {
    ...getBaseRouteParams(),
    exerciseId: route.params.exerciseId,
  }
}

function getExerciseId() {
  return Number(route.params.exerciseId || 0)
}

function getAttemptId() {
  return Number(route.params.attemptId || 0)
}

async function loadResult() {
  const exerciseId = getExerciseId()
  const attemptId = getAttemptId()
  if (!exerciseId || !attemptId) {
    errorMessage.value = t("Invalid exercise attempt")
    return
  }

  isLoading.value = true
  errorMessage.value = ""

  try {
    const response = await exerciseService.getExerciseRuntimeResult(getContextParams(), exerciseId, attemptId)
    title.value = response.title || ""
    description.value = response.description || ""
    attempt.value = response.attempt || {}
    visibility.value = response.visibility || {}
    questions.value = Array.isArray(response.questions) ? response.questions : []
    legacyUrls.value = response.legacyUrls || {}
    initializeCorrectionForms()
  } catch (error) {
    console.error("Error loading exercise result", error)
    errorMessage.value = t("Could not load exercise result")
  } finally {
    isLoading.value = false
  }
}

function questionLabel(position) {
  const safePosition = Number(position || 0)

  return safePosition > 0 ? `${t("Question")} ${safePosition}` : t("Question")
}

function isManualCorrectionQuestion(question) {
  return ["free_answer", "upload_answer"].includes(question?.answer?.kind)
}

function hasPartialManualScore(question) {
  if (!isManualCorrectionQuestion(question) || question?.pendingCorrection) {
    return false
  }

  const score = Number(question?.score ?? 0)
  const maxScore = Number(question?.maxScore ?? 0)

  return maxScore > 0 && score > 0 && score < maxScore
}

function questionCardClass(question) {
  if (question.pendingCorrection) {
    return "border-warning/40"
  }

  if (question.isCorrect === true) {
    return "border-success/40"
  }

  if (hasPartialManualScore(question)) {
    return "border-warning/40"
  }

  if (question.isCorrect === false) {
    return "border-danger/40"
  }

  return "border-gray-20"
}

function questionStatusIconClass(question) {
  if (question.pendingCorrection) {
    return "mdi-help-circle-outline text-warning"
  }

  if (question.isCorrect === true) {
    return "mdi-check-circle text-success"
  }

  if (hasPartialManualScore(question)) {
    return "mdi-progress-check text-warning"
  }

  if (question.isCorrect === false) {
    return "mdi-alert-circle text-danger"
  }

  return "mdi-help-circle-outline text-primary"
}

function questionScoreClass(question) {
  if (question.pendingCorrection) {
    return "border-warning/30 bg-warning/10 text-warning"
  }

  if (question.isCorrect === true) {
    return "border-success/30 bg-success/10 text-success"
  }

  if (hasPartialManualScore(question)) {
    return "border-warning/30 bg-warning/10 text-warning"
  }

  if (question.isCorrect === false) {
    return "border-danger/30 bg-danger/10 text-danger"
  }

  return "border-gray-20 bg-white text-gray-700"
}

function questionResultBadgeLabel(question) {
  if (question.pendingCorrection) {
    return ""
  }

  if (question.isCorrect === true) {
    return t("Correct")
  }

  if (hasPartialManualScore(question)) {
    return t("Partially correct")
  }

  if (question.isCorrect === false) {
    return t("Incorrect")
  }

  return ""
}

function questionResultBadgeClass(question) {
  if (question.isCorrect === true) {
    return "bg-success/10 text-success"
  }

  if (hasPartialManualScore(question)) {
    return "bg-warning/10 text-warning"
  }

  if (question.isCorrect === false) {
    return "bg-danger/10 text-danger"
  }

  return "bg-gray-20 text-gray-700"
}

function choiceClass(choice) {
  if (choice.correct) {
    return "border-success/40 bg-success/10"
  }

  if (choice.selected) {
    return "border-info/40 bg-support-1"
  }

  return "border-gray-20 bg-white"
}

function choiceIconClass(choice) {
  if (choice.correct) {
    return "mdi-check-circle text-success"
  }

  if (choice.selected) {
    return "mdi-radiobox-marked text-info"
  }

  return "mdi-circle-outline text-gray-50"
}

function trueFalseClass(choice) {
  if (isTrueFalseChoiceCorrect(choice)) {
    return "border-success/40 bg-success/10"
  }

  if (choice.selectedOptionLabel) {
    return "border-danger/40 bg-danger/10"
  }

  return "border-gray-20 bg-white"
}

function trueFalseIconClass(choice) {
  if (isTrueFalseChoiceCorrect(choice)) {
    return "mdi-check-circle text-success"
  }

  if (choice.selectedOptionLabel) {
    return "mdi-alert-circle text-danger"
  }

  return "mdi-help-circle-outline text-gray-50"
}

function isTrueFalseChoiceCorrect(choice) {
  return Boolean(choice.correctOptionLabel && choice.selectedOptionLabel === choice.correctOptionLabel)
}

function blankClass(blank) {
  if (isBlankCorrect(blank)) {
    return "border-success/30"
  }

  return "border-danger/30"
}

function blankIconClass(blank) {
  return isBlankCorrect(blank) ? "mdi-check-circle text-success" : "mdi-alert-circle text-danger"
}

function isBlankCorrect(blank) {
  return String(blank.studentScore || "") === "1"
}

function matchingClass(prompt) {
  if (isMatchingCorrect(prompt)) {
    return "border-success/40 bg-success/10"
  }

  if (prompt.selectedOptionAnswer) {
    return "border-danger/40 bg-danger/10"
  }

  return "border-gray-20 bg-white"
}

function matchingIconClass(prompt) {
  if (isMatchingCorrect(prompt)) {
    return "mdi-check-circle text-success"
  }

  if (prompt.selectedOptionAnswer) {
    return "mdi-alert-circle text-danger"
  }

  return "mdi-help-circle-outline text-gray-50"
}

function isMatchingCorrect(prompt) {
  return Boolean(prompt.correctOptionAnswer && prompt.selectedOptionAnswer === prompt.correctOptionAnswer)
}


function initializeCorrectionForms() {
  const forms = {}
  for (const question of questions.value) {
    if (!question.canCorrect) {
      continue
    }

    forms[question.id] = {
      marks: Number(question.score ?? question.answer?.marks ?? 0),
      teacherComment: question.answer?.teacherComment || "",
    }
  }

  correctionForms.value = forms
}

function correctionScoreInputId(question) {
  return `correction_score_${Number(question.id || 0)}`
}

function correctionCommentInputId(question) {
  return `teacher_comment_${Number(question.id || 0)}`
}

async function saveManualCorrection(question) {
  const form = correctionForms.value[question.id]
  const exerciseId = getExerciseId()
  const attemptId = getAttemptId()
  const questionId = Number(question.id || 0)
  if (!form || !exerciseId || !attemptId || !questionId) {
    return
  }

  correctionSavingQuestionId.value = questionId
  correctionError.value = ""
  correctionMessage.value = ""

  try {
    const response = await exerciseService.saveExerciseRuntimeCorrection(
      {
        exerciseId,
        attemptId,
        questionId,
        marks: Number(form.marks || 0),
        teacherComment: form.teacherComment || "",
      },
      getContextParams(),
      exerciseId,
      attemptId,
    )

    if (!response?.success) {
      throw new Error(response?.message || "Could not save correction")
    }

    correctionMessage.value = t("Correction saved")
    await loadResult()
  } catch (error) {
    console.error("Error saving exercise correction", error)
    correctionError.value = t("Could not save correction")
  } finally {
    correctionSavingQuestionId.value = null
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

function formatSeconds(seconds) {
  const safeSeconds = Math.max(0, Number(seconds || 0))
  const hours = Math.floor(safeSeconds / 3600)
  const minutes = Math.floor((safeSeconds % 3600) / 60)
  const remainingSeconds = safeSeconds % 60

  if (hours > 0) {
    return `${String(hours).padStart(2, "0")}:${String(minutes).padStart(2, "0")}:${String(remainingSeconds).padStart(2, "0")}`
  }

  return `${String(minutes).padStart(2, "0")}:${String(remainingSeconds).padStart(2, "0")}`
}

function formatDate(value) {
  if (!value) {
    return ""
  }

  const date = new Date(value)
  if (Number.isNaN(date.getTime())) {
    return ""
  }

  return date.toLocaleString()
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

onMounted(loadResult)

watch(
  () => [route.params.exerciseId, route.params.attemptId, route.query.cid, route.query.sid, route.query.gid],
  () => loadResult(),
)
</script>

<style scoped>
.exercise-result-html :deep(img) {
  max-width: 100%;
  height: auto;
}

.exercise-result-html :deep(.tiny-content) {
  display: block;
}

.exercise-result-html :deep(p) {
  margin-bottom: 0.25rem;
}

.exercise-result-html :deep(p:last-child) {
  margin-bottom: 0;
}
</style>
