<template>
  <section class="space-y-5">
    <div class="exercise-question-toolbar flex w-fit flex-wrap items-center gap-1 rounded-xl border border-gray-20 bg-white px-2 py-1 shadow-sm">
      <BaseButton
        class="exercise-question-toolbar__button"
        :label="t('Return to exercises list')"
        :route="{ name: 'ExerciseList', params: route.params, query: getContextParams() }"
        icon="back"
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

    <form
      v-if="!isLoading"
      class="space-y-6"
      @submit.prevent="createQuestion"
    >
      <section class="space-y-3 border-b border-gray-20 pb-4">
        <h1 class="text-2xl font-semibold text-gray-90">
          {{ t("Add this question to the test") }}
        </h1>
      </section>

      <section class="space-y-5 rounded-xl border border-gray-20 bg-white p-4 shadow-sm">
        <BaseSelect
          id="exercise-global-question-type"
          v-model="selectedQuestionType"
          :label="t('Question type')"
          name="question_type_hidden"
          :options="questionTypeOptions"
        />

        <BaseSelect
          id="exercise-global-question-exercise"
          v-model="selectedExerciseId"
          :label="t('Test') + ' *'"
          name="exercise"
          :options="exerciseOptions"
        />

        <div class="flex items-center gap-2">
          <input
            id="exercise-global-question-is-content"
            v-model="generateDefaultContent"
            class="h-4 w-4 rounded border-gray-25 text-primary focus:ring-primary"
            name="is_content"
            type="checkbox"
          />
          <label
            class="text-sm text-gray-90"
            for="exercise-global-question-is-content"
          >
            {{ t("Generate default content") }}
          </label>
        </div>
      </section>

      <div class="flex justify-end">
        <BaseButton
          :label="t('Create a question')"
          icon="plus"
          :is-submit="true"
          type="success"
        />
      </div>

      <p class="text-sm font-semibold text-gray-90">
        * {{ t("Required field") }}
      </p>
    </form>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import exerciseService from "../../services/exerciseService"

const IMMEDIATE_FEEDBACK = 1
const UNIQUE_ANSWER = 1

const { t } = useI18n()
const route = useRoute()
const router = useRouter()

const isLoading = ref(false)
const errorMessage = ref("")
const questionTypes = ref([])
const exercises = ref([])
const selectedQuestionType = ref(UNIQUE_ANSWER)
const selectedExerciseId = ref(0)
const generateDefaultContent = ref(true)

const questionTypeOptions = computed(() =>
  questionTypes.value.map((questionType) => ({
    label: t(questionType.label),
    value: Number(questionType.type),
  })),
)

const exerciseOptions = computed(() => [
  { label: `-${t("Select exercise")}-`, value: 0 },
  ...exercises.value.map((exercise) => ({
    label: exercise.title,
    value: Number(exercise.id),
  })),
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

function selectedExercise() {
  const exerciseId = Number(selectedExerciseId.value || 0)

  return exercises.value.find((exercise) => Number(exercise.id) === exerciseId) || null
}

async function createQuestion() {
  const exerciseId = Number(selectedExerciseId.value || 0)
  const questionType = Number(selectedQuestionType.value || 0)

  if (exerciseId <= 0) {
    errorMessage.value = t("You have to select a test")
    return
  }

  if (questionType <= 0) {
    errorMessage.value = t("Invalid question type")
    return
  }

  const exercise = selectedExercise()
  if (exercise && Number(exercise.feedbackType || 0) === IMMEDIATE_FEEDBACK && questionType !== UNIQUE_ANSWER) {
    errorMessage.value = t("Invalid question type")
    return
  }

  await router.push({
    name: "ExerciseQuestionCreate",
    params: { node: route.params.node, exerciseId, questionType },
    query: {
      ...getContextParams(),
      isContent: generateDefaultContent.value ? 1 : 0,
    },
  })
}

async function loadForm() {
  isLoading.value = true
  errorMessage.value = ""

  try {
    const response = await exerciseService.getExerciseGlobalQuestionTypes(getContextParams())
    questionTypes.value = Array.isArray(response.questionTypes) ? response.questionTypes : []
    exercises.value = Array.isArray(response.exercises) ? response.exercises : []
    selectedQuestionType.value = Number(questionTypes.value[0]?.type || UNIQUE_ANSWER)
    selectedExerciseId.value = 0
  } catch (error) {
    console.error("Error loading global exercise question form", error)
    errorMessage.value = error?.response?.data?.detail || error?.response?.data?.["hydra:description"] || t("Could not load exercise questions")
  } finally {
    isLoading.value = false
  }
}

onMounted(loadForm)
</script>
