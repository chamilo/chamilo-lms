<template>
  <section class="space-y-5">
    <div class="flex flex-wrap items-center gap-1 rounded-xl border border-gray-20 bg-white px-2 py-1 shadow-sm w-fit">
      <BaseButton
        :label="t('Back to exercises')"
        :route="{ name: 'ExerciseList', params: route.params, query: getContextParams() }"
        icon="back"
        only-icon
        size="small"
        type="primary-text"
      />
      <BaseButton
        v-if="legacyUrls.overview"
        :label="t('Open legacy exercise')"
        :to-url="legacyUrls.overview"
        icon="play-box-outline"
        only-icon
        size="small"
        type="primary-text"
      />
      <BaseButton
        v-if="canManage"
        :label="t('Results')"
        :route="{ name: 'ExerciseReport', params: { ...route.params, exerciseId: getExerciseId() }, query: getContextParams() }"
        icon="tracking"
        only-icon
        size="small"
        type="primary-text"
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

    <template v-if="!isLoading && !errorMessage">
      <header class="space-y-3 rounded-xl border border-gray-20 bg-white p-5 shadow-sm">
        <div class="space-y-2">
          <h1 class="text-2xl font-semibold text-gray-90">
            {{ displayText(title, t("Untitled")) }}
          </h1>
          <div
            v-if="description"
            class="exercise-runtime-html text-sm text-gray-700"
            v-html="description"
          />
        </div>

        <div class="flex flex-wrap gap-2 text-xs">
          <span class="rounded-full bg-gray-100 px-2 py-1 text-gray-700">
            {{ t("Questions") }}: {{ questionCount }}
          </span>
          <span class="rounded-full bg-gray-100 px-2 py-1 text-gray-700">
            {{ t("Total score") }}: {{ totalScore }}
          </span>
          <span
            v-if="settings.duration"
            class="rounded-full bg-blue-100 px-2 py-1 text-blue-700"
          >
            {{ t("Duration") }}: {{ t("{0} min", [settings.duration]) }}
          </span>
          <span
            v-if="settings.maxAttempt"
            class="rounded-full bg-gray-100 px-2 py-1 text-gray-700"
          >
            {{ t("Attempts") }}: {{ settings.maxAttempt }}
          </span>
          <span
            v-if="settings.oneQuestionPerPage"
            class="rounded-full bg-gray-100 px-2 py-1 text-gray-700"
          >
            {{ t("One question per page") }}
          </span>
          <span
            v-if="settings.randomQuestions"
            class="rounded-full bg-gray-100 px-2 py-1 text-gray-700"
          >
            {{ t("Random questions") }}: {{ settings.randomQuestions }}
          </span>
          <span
            v-if="settings.randomAnswers"
            class="rounded-full bg-gray-100 px-2 py-1 text-gray-700"
          >
            {{ t("Random answers") }}
          </span>
        </div>
      </header>

      <div
        v-if="usesLegacySubmit && activeAttempt"
        class="rounded-xl border border-info/30 bg-support-1 p-4 text-sm text-support-4"
      >
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
          <p>
            {{ t("This Vue player can save draft answers for simple question types. Final submission, scoring, results and review still use the legacy exercise runtime in this batch.") }}
          </p>
          <BaseButton
            v-if="legacyUrls.overview"
            :label="t('Continue in legacy exercise')"
            :to-url="legacyUrls.overview"
            icon="play-box-outline"
            type="primary"
          />
        </div>
      </div>

      <div class="rounded-xl border border-gray-20 bg-white p-4 shadow-sm">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
          <div class="space-y-1 text-sm text-gray-700">
            <div class="font-semibold text-gray-90">
              {{ activeAttempt ? t("Vue attempt started") : t("Vue attempt") }}
            </div>
            <div v-if="canManage">
              {{ t("Teacher preview does not create a tracked attempt.") }}
            </div>
            <div v-else-if="activeAttempt">
              {{ t("Attempt") }} #{{ activeAttempt.attemptId }} · {{ t("Question") }} {{ currentQuestionIndex + 1 }} / {{ visibleQuestionTotal }}
              <span v-if="activeAttempt.remainingSeconds !== null && activeAttempt.remainingSeconds !== undefined">
                · {{ t("Time left") }}: {{ formatSeconds(activeAttempt.remainingSeconds) }}
              </span>
            </div>
            <div v-else>
              {{ t("Start or resume a Vue attempt. Draft answers for simple question types can be saved before final legacy submission.") }}
            </div>
            <div v-if="attemptMessage" class="text-support-4">
              {{ attemptMessage }}
            </div>
            <div v-if="attemptError" class="text-danger">
              {{ attemptError }}
            </div>
            <div v-if="answerSaveMessage" class="text-green-700">
              {{ answerSaveMessage }}
            </div>
            <div v-if="answerSaveError" class="text-danger">
              {{ answerSaveError }}
            </div>
            <div v-if="finishMessage" class="text-support-4">
              {{ finishMessage }}
            </div>
            <div v-if="finishError" class="text-danger">
              {{ finishError }}
            </div>
          </div>
          <div class="flex flex-wrap gap-2">
            <BaseButton
              v-if="!canManage && !activeAttempt"
              :disabled="isStartingAttempt"
              :label="isStartingAttempt ? t('Starting') : t('Start Vue attempt')"
              icon="play-box-outline"
              type="primary"
              @click="startAttempt"
            />
            <BaseButton
              v-if="legacyUrls.overview"
              :label="t('Use legacy runtime')"
              :to-url="legacyUrls.overview"
              icon="play-box-outline"
              type="secondary"
            />
          </div>
        </div>
      </div>

      <form v-if="canManage || activeAttempt" class="space-y-4" @submit.prevent="submitDisabled">
        <article
          v-for="(question, index) in visibleQuestions"
          :key="question.id"
          class="rounded-xl border border-gray-20 bg-white p-5 shadow-sm"
        >
          <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
            <div class="space-y-1">
              <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                {{ questionNumberLabel(question, index) }} · {{ t(question.typeLabel) }}
              </div>
              <h2
                v-if="!settings.hideQuestionTitle"
                class="exercise-runtime-html text-lg font-semibold text-gray-90"
                v-html="question.title"
              />
              <div
                v-if="question.description && !isReadingQuestion(question)"
                class="exercise-runtime-html text-sm text-gray-700"
                v-html="question.description"
              />
            </div>
            <div class="flex flex-wrap gap-2">
              <span class="rounded-full bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-700">
                {{ t("Score") }}: {{ question.score }}
              </span>
              <span
                v-if="savedQuestionIds.has(Number(question.id))"
                class="rounded-full bg-green-100 px-2 py-1 text-xs font-semibold text-green-700"
              >
                {{ t("Draft saved") }}
              </span>
            </div>
          </div>

          <div v-if="answers[question.id]" class="space-y-4">
            <div v-if="isRadioChoice(question)" class="space-y-3">
              <label
                v-for="choice in question.choices"
                :key="choice.id"
                class="flex items-start gap-3 rounded-lg border border-gray-20 p-3 hover:bg-gray-10"
              >
                <input
                  v-model="answers[question.id].choice"
                  class="mt-1"
                  :name="`question_${question.id}`"
                  type="radio"
                  :value="choice.id"
                />
                <span class="exercise-runtime-html flex-1" v-html="choice.answer" />
              </label>
            </div>

            <div v-else-if="isCheckboxChoice(question)" class="space-y-3">
              <label
                v-for="choice in question.choices"
                :key="choice.id"
                class="flex items-start gap-3 rounded-lg border border-gray-20 p-3 hover:bg-gray-10"
              >
                <input
                  v-model="answers[question.id].choices"
                  class="mt-1"
                  :name="`question_${question.id}[]`"
                  type="checkbox"
                  :value="choice.id"
                />
                <span class="exercise-runtime-html flex-1" v-html="choice.answer" />
              </label>
            </div>

            <div v-else-if="isTrueFalseQuestion(question)" class="space-y-3">
              <div
                v-for="choice in question.choices"
                :key="choice.id"
                class="rounded-lg border border-gray-20 p-3"
              >
                <div class="exercise-runtime-html mb-3 font-medium text-gray-90" v-html="choice.answer" />
                <div class="flex flex-wrap gap-3">
                  <label
                    v-for="option in trueFalseOptions(question)"
                    :key="`${choice.id}-${option.value}`"
                    class="inline-flex items-center gap-2 text-sm text-gray-700"
                  >
                    <input
                      v-model="answers[question.id].trueFalse[choice.id]"
                      :name="`question_${question.id}_${choice.id}`"
                      type="radio"
                      :value="option.value"
                    />
                    <span>{{ option.label }}</span>
                  </label>
                </div>
              </div>
            </div>

            <div v-else-if="isFillBlanksQuestion(question)" class="rounded-lg border border-gray-20 p-4 text-gray-800">
              <template
                v-for="(segment, segmentIndex) in question.fillBlanks.segments"
                :key="`${question.id}-blank-segment-${segmentIndex}`"
              >
                <span
                  v-if="segment.type === 'text'"
                  class="exercise-runtime-html inline"
                  v-html="segment.text"
                />
                <input
                  v-else
                  v-model="answers[question.id].blanks[segment.position]"
                  class="mx-1 inline-block rounded border border-gray-30 px-2 py-1 text-sm"
                  :name="`question_${question.id}_blank_${segment.position}`"
                  :style="{ width: `${Math.min(Math.max(Number(segment.inputSize || 160), 80), 320)}px` }"
                  type="text"
                />
              </template>
            </div>

            <div v-else-if="isMatchingQuestion(question)" class="space-y-3">
              <div
                v-for="prompt in question.matching.prompts"
                :key="prompt.id"
                class="grid gap-3 rounded-lg border border-gray-20 p-3 md:grid-cols-[1fr_16rem] md:items-center"
              >
                <div class="exercise-runtime-html" v-html="prompt.answer" />
                <select
                  v-model="answers[question.id].matching[prompt.id]"
                  class="rounded border border-gray-30 px-3 py-2 text-sm"
                  :name="`question_${question.id}_matching_${prompt.id}`"
                >
                  <option value="">{{ t("Select") }}</option>
                  <option
                    v-for="option in question.matching.options"
                    :key="option.id"
                    :value="option.id"
                  >
                    {{ option.label }}. {{ displayText(option.answer) }}
                  </option>
                </select>
              </div>
            </div>

            <div v-else-if="isDraggableQuestion(question)" class="space-y-3">
              <div class="rounded-lg border border-yellow-100 bg-yellow-50 p-3 text-sm text-yellow-800">
                {{ t("Sequence ordering is displayed here as a temporary list. Drag and drop submission will be migrated in the submit processor batch.") }}
              </div>
              <ol class="list-decimal space-y-2 pl-6">
                <li
                  v-for="item in question.draggable.items"
                  :key="item.id"
                  class="exercise-runtime-html"
                  v-html="item.answer"
                />
              </ol>
            </div>

            <div v-else-if="isDropdownQuestion(question)" class="space-y-3">
              <select
                v-model="answers[question.id].dropdown"
                class="rounded border border-gray-30 px-3 py-2 text-sm"
                :name="`question_${question.id}_dropdown`"
              >
                <option value="">{{ t("Select") }}</option>
                <option
                  v-for="option in question.dropdown.options"
                  :key="option.id"
                  :value="option.id"
                >
                  {{ displayText(option.answer) }}
                </option>
              </select>
            </div>

            <div v-else-if="isCalculatedQuestion(question)" class="space-y-3">
              <div
                v-if="question.calculated.text"
                class="exercise-runtime-html rounded-lg border border-gray-20 p-3 text-sm text-gray-800"
                v-html="question.calculated.text"
              />
              <input
                v-model="answers[question.id].calculated"
                class="w-full rounded border border-gray-30 px-3 py-2 text-sm md:w-80"
                :name="`question_${question.id}_calculated`"
                type="text"
              />
            </div>

            <div v-else-if="isOpenQuestion(question)" class="space-y-2">
              <textarea
                v-model="answers[question.id].text"
                class="min-h-32 w-full rounded border border-gray-30 px-3 py-2 text-sm"
                :name="`question_${question.id}_text`"
              />
            </div>

            <div v-else-if="isUploadQuestion(question)" class="space-y-2">
              <input
                class="block w-full text-sm text-gray-700"
                :name="`question_${question.id}_file`"
                type="file"
                @change="onUploadAnswerFileChange(question, $event)"
              />
              <div
                v-if="answers[question.id]?.uploadFileName"
                class="rounded-lg border border-info/30 bg-support-1 p-3 text-sm text-support-4"
              >
                {{ t("Selected file") }}: {{ answers[question.id].uploadFileName }}
              </div>
              <div
                v-if="answers[question.id]?.uploadedFiles?.length"
                class="space-y-2 rounded-lg border border-success/30 bg-success/10 p-3 text-sm text-success"
              >
                <div class="font-semibold">{{ t("Uploaded file") }}</div>
                <div
                  v-for="file in answers[question.id].uploadedFiles"
                  :key="file.id || file.name"
                >
                  {{ file.name || t("Uploaded file") }}
                </div>
              </div>
            </div>

            <div v-else-if="isOralQuestion(question)" class="space-y-2">
              <div class="rounded-lg border border-yellow-100 bg-yellow-50 p-3 text-sm text-yellow-800">
                {{ t("Audio recording and upload will be connected in the submit processor batch.") }}
              </div>
              <input
                class="block w-full text-sm text-gray-700"
                :name="`question_${question.id}_audio`"
                type="file"
                accept="audio/*"
              />
            </div>

            <div v-else-if="isAnnotationQuestion(question)" class="space-y-3">
              <img
                v-if="question.annotation.imageUrl"
                class="max-h-[32rem] max-w-full rounded-lg border border-gray-20 object-contain"
                :alt="question.annotation.imageName || t('Question image')"
                :src="question.annotation.imageUrl"
              />
              <textarea
                v-model="answers[question.id].text"
                class="min-h-28 w-full rounded border border-gray-30 px-3 py-2 text-sm"
                :name="`question_${question.id}_annotation`"
              />
            </div>

            <div v-else-if="isHotspotQuestion(question)" class="space-y-3">
              <img
                v-if="question.hotspot.imageUrl"
                class="max-h-[32rem] max-w-full rounded-lg border border-gray-20 object-contain"
                :alt="question.hotspot.imageName || t('Question image')"
                :src="question.hotspot.imageUrl"
              />
              <div class="rounded-lg border border-yellow-100 bg-yellow-50 p-3 text-sm text-yellow-800">
                {{ t("Hotspot click capture and scoring will be migrated in the submit processor batch.") }}
              </div>
            </div>

            <div v-else-if="isReadingQuestion(question)" class="space-y-3">
              <div class="rounded-lg border border-gray-20 p-3 text-sm text-gray-700">
                {{ t("Reading speed") }}: {{ question.reading.speed }} {{ t("words per minute") }}
              </div>
              <div
                class="exercise-runtime-html rounded-lg border border-gray-20 p-4 text-gray-800"
                v-html="question.reading.text || question.description"
              />
            </div>

            <div
              v-else-if="isPageBreak(question)"
              class="rounded-lg border border-gray-20 bg-gray-10 p-3 text-sm text-gray-700"
            >
              {{ t("Page break") }}
            </div>

            <div
              v-else
              class="rounded-lg border border-yellow-100 bg-yellow-50 p-3 text-sm text-yellow-800"
            >
              {{ t("This question type is pending runtime rendering in Vue.") }}
            </div>
          </div>
        </article>

        <div class="flex flex-wrap justify-between gap-2 rounded-xl border border-gray-20 bg-white p-4 shadow-sm">
          <BaseButton
            :disabled="!canMovePrevious || isSavingAnswer"
            :label="t('Previous question')"
            icon="back"
            type="secondary"
            @click="goToPreviousQuestion"
          />
          <div class="flex flex-wrap gap-2">
            <BaseButton
              v-if="!canManage && activeAttempt"
              :disabled="isSavingAnswer || !visibleQuestions.some(isDraftSaveSupported)"
              :label="isSavingAnswer ? t('Saving') : t('Save draft')"
              icon="check"
              type="success"
              @click="saveVisibleAnswers"
            />
            <BaseButton
              v-if="settings.oneQuestionPerPage"
              :disabled="!canMoveNext || isSavingAnswer"
              :label="t('Next question')"
              type="primary"
              @click="goToNextQuestion"
            />
            <BaseButton
              v-if="!canManage && activeAttempt"
              :disabled="!canSubmit || isSavingAnswer || isFinishingAttempt"
              :label="isFinishingAttempt ? t('Finishing') : t('Finish in Vue')"
              icon="check"
              type="primary"
              @click="finishAttempt"
            />
            <BaseButton
              v-if="legacyUrls.overview"
              :label="t('Continue in legacy exercise')"
              :to-url="legacyUrls.overview"
              icon="play-box-outline"
              type="secondary"
            />
          </div>
        </div>
      </form>
    </template>
  </section>
</template>

<script setup>
import { computed, onMounted, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import exerciseService from "../../services/exerciseService"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()

const isLoading = ref(false)
const errorMessage = ref("")
const title = ref("")
const description = ref("")
const settings = ref({})
const questions = ref([])
const legacyUrls = ref({})
const questionCount = ref(0)
const totalScore = ref(0)
const canManage = ref(false)
const canStartAttempt = ref(false)
const canSubmit = ref(false)
const usesLegacySubmit = ref(true)
const answers = ref({})
const activeAttempt = ref(null)
const currentQuestionIndex = ref(0)
const isStartingAttempt = ref(false)
const attemptMessage = ref("")
const attemptError = ref("")
const isSavingAnswer = ref(false)
const answerSaveError = ref("")
const answerSaveMessage = ref("")
const isFinishingAttempt = ref(false)
const finishError = ref("")
const finishMessage = ref("")
const savedQuestionIds = ref(new Set())

const visibleQuestions = computed(() => {
  if (!settings.value.oneQuestionPerPage) {
    return questions.value
  }

  const question = questions.value[currentQuestionIndex.value]

  return question ? [question] : []
})

const visibleQuestionTotal = computed(() => questions.value.length)
const canMovePrevious = computed(() => settings.value.oneQuestionPerPage && currentQuestionIndex.value > 0)
const canMoveNext = computed(() => settings.value.oneQuestionPerPage && currentQuestionIndex.value < questions.value.length - 1)

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

function getExerciseId() {
  return Number(route.params.exerciseId || 0)
}

async function loadRuntime() {
  const exerciseId = getExerciseId()
  if (!exerciseId) {
    errorMessage.value = t("Invalid exercise")
    return
  }

  isLoading.value = true
  errorMessage.value = ""

  try {
    const response = await exerciseService.getExerciseRuntime(getContextParams(), exerciseId)
    title.value = response.title || ""
    description.value = response.description || ""
    settings.value = response.settings || {}
    questions.value = Array.isArray(response.questions) ? response.questions : []
    legacyUrls.value = response.legacyUrls || {}
    questionCount.value = Number(response.questionCount || questions.value.length)
    totalScore.value = Number(response.totalScore || 0)
    canManage.value = true === response.canManage
    canStartAttempt.value = true === response.canStartAttempt
    activeAttempt.value = response.attempt || null
    canSubmit.value = true === response.canSubmit
    usesLegacySubmit.value = true === response.usesLegacySubmit && Boolean(activeAttempt.value)
    applyAttemptState(activeAttempt.value)
    initializeAnswerState()
    applySavedAnswers(activeAttempt.value?.savedAnswers || {})
  } catch (error) {
    console.error("Error loading exercise runtime", error)
    errorMessage.value = t("Could not load exercise")
  } finally {
    isLoading.value = false
  }
}

async function startAttempt() {
  const exerciseId = getExerciseId()
  if (!exerciseId) {
    attemptError.value = t("Invalid exercise")
    return
  }

  isStartingAttempt.value = true
  attemptMessage.value = ""
  attemptError.value = ""

  try {
    const response = await exerciseService.startExerciseAttempt({ exerciseId }, getContextParams(), exerciseId)
    if (response.success) {
      activeAttempt.value = response
      canSubmit.value = true === response.canFinish && true !== response.usesLegacyRuntime
      usesLegacySubmit.value = true === response.usesLegacyRuntime || false === response.canFinish
      attemptMessage.value = response.message || t("Attempt started")
      applyAttemptState(response)
      reorderQuestionsFromAttempt(response.questionIds || [])
      initializeAnswerState()
      applySavedAnswers(response.savedAnswers || {})
      return
    }

    if (response.usesLegacyRuntime && response.legacyUrls) {
      legacyUrls.value = { ...legacyUrls.value, ...response.legacyUrls }
    }

    canSubmit.value = false
    usesLegacySubmit.value = true === response.usesLegacyRuntime
    attemptError.value = response.message || t("Could not start the Vue attempt")
  } catch (error) {
    console.error("Error starting exercise attempt", error)
    attemptError.value = t("Could not start the Vue attempt")
  } finally {
    isStartingAttempt.value = false
  }
}

function applyAttemptState(attempt) {
  if (!attempt) {
    currentQuestionIndex.value = 0
    return
  }

  currentQuestionIndex.value = Math.max(0, Number(attempt.currentQuestionIndex || 0))
  if (Array.isArray(attempt.questionIds) && attempt.questionIds.length > 0) {
    reorderQuestionsFromAttempt(attempt.questionIds)
  }
}

function reorderQuestionsFromAttempt(questionIds = []) {
  if (!Array.isArray(questionIds) || questionIds.length === 0) {
    return
  }

  const questionMap = new Map(questions.value.map((question) => [Number(question.id), question]))
  const orderedQuestions = []

  for (const questionId of questionIds.map(Number)) {
    const question = questionMap.get(questionId)
    if (question) {
      orderedQuestions.push(question)
    }
  }

  if (orderedQuestions.length > 0) {
    questions.value = orderedQuestions.map((question, index) => ({
      ...question,
      position: index + 1,
    }))
    questionCount.value = questions.value.length
  }
}

async function goToPreviousQuestion() {
  if (!canMovePrevious.value) {
    return
  }

  if (await saveVisibleAnswers()) {
    currentQuestionIndex.value -= 1
  }
}

async function goToNextQuestion() {
  if (!canMoveNext.value) {
    return
  }

  if (await saveVisibleAnswers()) {
    currentQuestionIndex.value += 1
  }
}

async function saveVisibleAnswers() {
  if (canManage.value || !activeAttempt.value?.attemptId) {
    return true
  }

  const saveTargets = visibleQuestions.value.filter(isDraftSaveSupported)
  if (saveTargets.length === 0) {
    return true
  }

  isSavingAnswer.value = true
  answerSaveError.value = ""
  answerSaveMessage.value = ""

  try {
    for (const question of saveTargets) {
      await saveQuestionDraftAnswer(question)
    }

    answerSaveMessage.value = t("Draft answer saved")

    return true
  } catch (error) {
    console.error("Error saving exercise draft answer", error)
    answerSaveError.value = t("Could not save draft answer")

    return false
  } finally {
    isSavingAnswer.value = false
  }
}

async function finishAttempt() {
  const exerciseId = getExerciseId()
  const attemptId = Number(activeAttempt.value?.attemptId || 0)
  if (canManage.value || !exerciseId || !attemptId) {
    return
  }

  if (!(await saveVisibleAnswers())) {
    return
  }

  isFinishingAttempt.value = true
  finishError.value = ""
  finishMessage.value = ""

  try {
    const response = await exerciseService.finishExerciseRuntimeAttempt(
      {
        exerciseId,
        attemptId,
      },
      getContextParams(),
      exerciseId,
      attemptId,
    )

    if (!response?.success) {
      throw new Error(response?.message || "Could not finish the attempt")
    }

    activeAttempt.value = {
      ...activeAttempt.value,
      status: response.status || "completed",
    }
    canSubmit.value = false
    finishMessage.value = response.message ? t(response.message) : t("Attempt finished")

    await router.push({
      name: "ExerciseResult",
      params: {
        ...route.params,
        exerciseId,
        attemptId,
      },
      query: getContextParams(),
    })
  } catch (error) {
    console.error("Error finishing exercise attempt", error)
    finishError.value = t("Could not finish the attempt")
  } finally {
    isFinishingAttempt.value = false
  }
}

async function saveQuestionDraftAnswer(question) {
  const exerciseId = getExerciseId()
  const attemptId = Number(activeAttempt.value?.attemptId || 0)
  if (!exerciseId || !attemptId || !question?.id) {
    return
  }

  const response = isUploadQuestion(question)
    ? await saveUploadQuestionAnswer(question, exerciseId, attemptId)
    : await exerciseService.saveExerciseRuntimeAnswer(
      {
        exerciseId,
        attemptId,
        questionId: Number(question.id),
        answer: buildAnswerPayload(question),
        secondsSpent: 0,
      },
      getContextParams(),
      exerciseId,
      attemptId,
    )

  if (!response) {
    return
  }

  if (!response?.success) {
    throw new Error(response?.message || "Could not save draft answer")
  }

  if (Array.isArray(response.answeredQuestionIds)) {
    savedQuestionIds.value = new Set(response.answeredQuestionIds.map(Number))
  } else if (Array.isArray(response.savedAnswer) && response.savedAnswer.length > 0) {
    const nextSavedQuestionIds = new Set(savedQuestionIds.value)
    nextSavedQuestionIds.add(Number(question.id))
    savedQuestionIds.value = nextSavedQuestionIds
  } else {
    const nextSavedQuestionIds = new Set(savedQuestionIds.value)
    nextSavedQuestionIds.delete(Number(question.id))
    savedQuestionIds.value = nextSavedQuestionIds
  }
}


async function saveUploadQuestionAnswer(question, exerciseId, attemptId) {
  const questionAnswer = answers.value[question.id] || {}
  if (!questionAnswer.uploadFile) {
    return null
  }

  const formData = new FormData()
  formData.append("questionId", String(Number(question.id)))
  formData.append("secondsSpent", "0")
  formData.append("file", questionAnswer.uploadFile)

  const response = await exerciseService.uploadExerciseRuntimeAnswer(
    formData,
    getContextParams(),
    exerciseId,
    attemptId,
  )

  if (response?.success) {
    questionAnswer.uploadFile = null
    questionAnswer.uploadFileName = ""
    questionAnswer.uploadedFiles = Array.isArray(response.files) ? response.files : []
  }

  return response
}

function buildAnswerPayload(question) {
  const questionAnswer = answers.value[question.id] || {}

  if (isRadioChoice(question)) {
    return { choice: questionAnswer.choice }
  }

  if (isCheckboxChoice(question)) {
    return { choices: questionAnswer.choices || [] }
  }

  if (isTrueFalseQuestion(question)) {
    return { trueFalse: questionAnswer.trueFalse || {} }
  }

  if (isFillBlanksQuestion(question)) {
    return { blanks: questionAnswer.blanks || {} }
  }

  if (isMatchingQuestion(question)) {
    return { matching: questionAnswer.matching || {} }
  }

  if (isDropdownQuestion(question)) {
    return { dropdown: questionAnswer.dropdown }
  }

  if (isDraftFreeAnswerQuestion(question)) {
    return { text: questionAnswer.text || "" }
  }

  return {}
}

function isDraftSaveSupported(question) {
  return isRadioChoice(question)
    || isCheckboxChoice(question)
    || isDraftTrueFalseQuestion(question)
    || isFillBlanksQuestion(question)
    || isMatchingQuestion(question)
    || isDropdownQuestion(question)
    || isDraftFreeAnswerQuestion(question)
    || isUploadQuestion(question)
}

function isDraftTrueFalseQuestion(question) {
  return [11, 12].includes(Number(question.type))
}

function isDraftFreeAnswerQuestion(question) {
  return Number(question.type) === 5
}

function applySavedAnswers(savedAnswers = {}) {
  const savedIds = new Set()

  for (const [questionId, rows] of Object.entries(savedAnswers || {})) {
    const question = questions.value.find((item) => Number(item.id) === Number(questionId))
    if (!question || !Array.isArray(rows)) {
      continue
    }

    applySavedAnswer(question, rows)
    if (rows.length > 0) {
      savedIds.add(Number(questionId))
    }
  }

  savedQuestionIds.value = savedIds
}

function applySavedAnswer(question, rows) {
  const questionAnswer = answers.value[question.id]
  if (!questionAnswer) {
    return
  }

  if (isRadioChoice(question)) {
    questionAnswer.choice = Number(rows[0]?.answer || 0) || null
    return
  }

  if (isCheckboxChoice(question)) {
    questionAnswer.choices = rows.map((row) => Number(row.answer || 0)).filter((value) => value > 0)
    return
  }

  if (isTrueFalseQuestion(question)) {
    questionAnswer.trueFalse = {}
    for (const row of rows) {
      const parts = String(row.answer || "").split(":")
      const answerId = Number(parts[0] || 0)
      const optionValue = Number(parts[1] || 0)
      if (answerId > 0 && optionValue > 0) {
        questionAnswer.trueFalse[answerId] = optionValue
      }
    }
    return
  }

  if (isFillBlanksQuestion(question)) {
    questionAnswer.blanks = extractSavedBlankValues(rows[0]?.answer || "", question.fillBlanks.separator)
    return
  }

  if (isMatchingQuestion(question)) {
    questionAnswer.matching = {}
    for (const row of rows) {
      const promptId = Number(row.position || 0)
      const optionId = Number(row.answer || 0)
      if (promptId > 0 && optionId > 0) {
        questionAnswer.matching[promptId] = optionId
      }
    }
    return
  }

  if (isDropdownQuestion(question)) {
    questionAnswer.dropdown = Number(rows[0]?.answer || 0) || ""
    return
  }

  if (isOpenQuestion(question)) {
    questionAnswer.text = rows[0]?.answer || ""
    return
  }

  if (isUploadQuestion(question)) {
    questionAnswer.uploadedFiles = rows.length > 0 ? [{ name: t("Uploaded file") }] : []
  }
}

function extractSavedBlankValues(savedAnswer, separator = 0) {
  const [start, end] = getFillBlankSeparators(separator)
  const pattern = new RegExp(`${escapeRegExp(start)}(.*?)${escapeRegExp(end)}`, "g")
  const matches = [...String(savedAnswer || "").split("::")[0].matchAll(pattern)]
  const blanks = {}

  for (let index = 0; index < matches.length; index += 3) {
    const blankPosition = Math.floor(index / 3) + 1
    blanks[blankPosition] = decodeHtml(matches[index + 1]?.[1] || "")
  }

  return blanks
}

function getFillBlankSeparators(separator = 0) {
  const separators = [
    ["[", "]"],
    ["{", "}"],
    ["(", ")"],
    ["*", "*"],
    ["#", "#"],
    ["%", "%"],
    ["$", "$"],
  ]

  return separators[Number(separator || 0)] || separators[0]
}

function escapeRegExp(value) {
  return String(value).replace(/[.*+?^${}()|[\]\\]/g, "\\$&")
}

function formatSeconds(seconds) {
  const safeSeconds = Math.max(0, Number(seconds || 0))
  const minutes = Math.floor(safeSeconds / 60)
  const remainingSeconds = safeSeconds % 60

  return `${String(minutes).padStart(2, "0")}:${String(remainingSeconds).padStart(2, "0")}`
}

function initializeAnswerState() {
  const nextAnswers = {}

  for (const question of questions.value) {
    nextAnswers[question.id] = {
      choice: null,
      choices: [],
      trueFalse: {},
      blanks: {},
      matching: {},
      dropdown: "",
      calculated: "",
      text: "",
      uploadFile: null,
      uploadFileName: "",
      uploadedFiles: [],
    }
  }

  answers.value = nextAnswers
}


function onUploadAnswerFileChange(question, event) {
  const file = event?.target?.files?.[0] || null
  const questionAnswer = answers.value[question.id]
  if (!questionAnswer) {
    return
  }

  questionAnswer.uploadFile = file
  questionAnswer.uploadFileName = file?.name || ""
}

function questionNumberLabel(question, index) {
  if (settings.value.hideQuestionNumber) {
    return t("Question")
  }

  return t("Question {0}", [question.position || index + 1])
}

function isRadioChoice(question) {
  return [1, 10, 17].includes(Number(question.type))
}

function isCheckboxChoice(question) {
  return [2, 9, 14].includes(Number(question.type))
}

function isTrueFalseQuestion(question) {
  return [11, 12, 22].includes(Number(question.type))
}

function isFillBlanksQuestion(question) {
  return [3, 27].includes(Number(question.type)) && question.fillBlanks && Array.isArray(question.fillBlanks.segments)
}

function isMatchingQuestion(question) {
  return [4, 19, 24, 25].includes(Number(question.type)) && question.matching
}

function isDraggableQuestion(question) {
  return Number(question.type) === 18 && question.draggable
}

function isDropdownQuestion(question) {
  return [28, 29].includes(Number(question.type)) && question.dropdown
}

function isCalculatedQuestion(question) {
  return Number(question.type) === 16
}

function isOpenQuestion(question) {
  return [5, 15].includes(Number(question.type))
}

function isUploadQuestion(question) {
  return Number(question.type) === 23
}

function isOralQuestion(question) {
  return Number(question.type) === 13
}

function isAnnotationQuestion(question) {
  return Number(question.type) === 20 && question.annotation
}

function isHotspotQuestion(question) {
  return [6, 8, 26].includes(Number(question.type)) && question.hotspot
}

function isReadingQuestion(question) {
  return Number(question.type) === 21 && question.reading
}

function isPageBreak(question) {
  return Number(question.type) === 31
}

function trueFalseOptions(question) {
  if (Array.isArray(question.trueFalseOptions) && question.trueFalseOptions.length > 0) {
    return question.trueFalseOptions.map((option) => ({
      value: Number(option.position || option.id),
      label: displayText(option.title),
    }))
  }

  return [
    { value: 1, label: t("True") },
    { value: 2, label: t("False") },
    { value: 3, label: t("Don't know") },
  ]
}

function submitDisabled() {
  return false
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

onMounted(loadRuntime)

watch(
  () => [route.params.exerciseId, route.query.cid, route.query.sid, route.query.gid],
  () => loadRuntime(),
)
</script>

<style scoped>
.exercise-runtime-html :deep(img) {
  max-width: 100%;
  height: auto;
}

.exercise-runtime-html :deep(p) {
  margin-bottom: 0.5rem;
}

.exercise-runtime-html :deep(p:last-child) {
  margin-bottom: 0;
}
</style>
