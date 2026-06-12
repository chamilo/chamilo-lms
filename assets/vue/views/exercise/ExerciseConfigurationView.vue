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
        v-if="isEditMode"
        :label="t('Edit questions')"
        :route="questionsRoute"
        icon="edit"
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
      class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"
    >
      {{ errorMessage }}
    </div>

    <div
      v-if="successMessage"
      class="rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-700"
    >
      {{ successMessage }}
    </div>

    <form
      v-if="!isLoading"
      class="space-y-5"
      @submit.prevent="saveConfiguration"
    >
      <h1 class="text-2xl font-semibold text-gray-90">
        {{ isEditMode ? t("Edit test") : t("Create a new test") }}
      </h1>

      <div class="border-b border-gray-20" />

      <BaseInputText
        id="exercise-title"
        v-model="form.title"
        :is-invalid="formSubmitted && !form.title.trim()"
        :label="t('* Test name')"
        name="title"
        required
      />

      <button
        class="inline-flex items-center gap-2 text-sm font-semibold text-primary hover:text-primary-dark"
        type="button"
        @click="showAdvancedSettings = !showAdvancedSettings"
      >
        <BaseIcon
          :icon="showAdvancedSettings ? 'chevron-down' : 'chevron-right'"
          size="small"
        />
        <span>{{ showAdvancedSettings ? t("Hide advanced settings") : t("Advanced settings") }}</span>
      </button>

      <div
        v-if="showAdvancedSettings"
        class="space-y-6"
      >
        <section class="space-y-3 rounded-xl border border-gray-20 bg-white p-4 shadow-sm">
          <h2 class="text-base font-semibold text-gray-90">{{ t("Context") }}</h2>
          <BaseTinyEditor
            editor-id="exercise-description"
            v-model="form.description"
            :editor-config="mainEditorConfig"
            :full-page="false"
            :title="t('Give a context to the test')"
          />
        </section>

        <section class="space-y-4 rounded-xl border border-gray-20 bg-white p-4 shadow-sm">
          <h2 class="text-base font-semibold text-gray-90">{{ t("Feedback and results") }}</h2>

          <div class="grid gap-6 xl:grid-cols-2">
            <FieldGroup :title="t('Feedback')">
              <RadioChoice
                v-for="option in feedbackOptions"
                :key="`feedback-${option.value}`"
                v-model="form.feedbackType"
                name="feedbackType"
                :option="option"
              />
              <p class="mt-2 text-xs text-primary">
                {{
                  t(
                    "How should we show the feedback/comment for each question? This option defines how it will be shown to the learner when taking the test.",
                  )
                }}
              </p>
            </FieldGroup>

            <FieldGroup :title="t('Show score to learner')">
              <RadioChoice
                v-for="option in resultOptions"
                :key="`result-${option.value}`"
                v-model="form.resultsDisabled"
                name="resultsDisabled"
                :option="option"
              />
            </FieldGroup>
          </div>

          <FieldGroup :title="t('Results page configuration')">
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
              <BaseCheckbox
                id="exercise-hide-expected-answers"
                v-model="form.pageResultConfiguration.hideExpectedAnswers"
                :label="t('Hide expected answers column')"
                name="hideExpectedAnswers"
              />
              <BaseCheckbox
                id="exercise-hide-total-score"
                v-model="form.pageResultConfiguration.hideTotalScore"
                :label="t('Hide total score')"
                name="hideTotalScore"
              />
              <BaseCheckbox
                id="exercise-hide-question-score"
                v-model="form.pageResultConfiguration.hideQuestionScore"
                :label="t('Hide question score')"
                name="hideQuestionScore"
              />
              <BaseCheckbox
                id="exercise-hide-category-table"
                v-model="form.pageResultConfiguration.hideCategoryTable"
                :label="t('Hide category table')"
                name="hideCategoryTable"
              />
              <BaseCheckbox
                id="exercise-hide-correct-answered-questions"
                v-model="form.pageResultConfiguration.hideCorrectAnsweredQuestions"
                :label="t('Hide correct answered questions')"
                name="hideCorrectAnsweredQuestions"
              />
            </div>
          </FieldGroup>
        </section>

        <section class="space-y-4 rounded-xl border border-gray-20 bg-white p-4 shadow-sm">
          <h2 class="text-base font-semibold text-gray-90">{{ t("Questions behavior") }}</h2>

          <div class="grid gap-6 xl:grid-cols-2">
            <FieldGroup :title="t('Questions per page')">
              <RadioChoice
                v-for="option in typeOptions"
                :key="`type-${option.value}`"
                v-model="form.type"
                name="type"
                :option="option"
              />
            </FieldGroup>

            <div class="space-y-4">
              <BaseSelect
                id="exercise-question-selection-type"
                v-model="form.questionSelectionType"
                :label="t('Question selection type')"
                name="questionSelectionType"
                :options="questionSelectionTypeOptions"
                option-label="label"
                option-value="value"
              />

              <BaseInputNumber
                v-if="showRandomQuestionCount"
                id="exercise-random"
                v-model="form.random"
                :help-text="t('Use 0 to keep all available questions. Use -1 for all questions where legacy accepts it.')"
                :label="t('Random questions')"
                name="random"
                :min="-1"
              />

              <BaseSelect
                v-if="showCategorySelectionOptions"
                id="exercise-random-by-category"
                v-model="form.randomByCategory"
                :label="t('Random by category')"
                name="randomByCategory"
                :options="randomByCategoryOptions"
                option-label="label"
                option-value="value"
              />
            </div>
          </div>

          <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
            <FieldGroup :title="t('Shuffle answers')">
              <RadioChoice
                v-for="option in booleanOptions"
                :key="`random-answers-${option.booleanValue}`"
                v-model="form.randomAnswers"
                name="randomAnswers"
                :option="option"
                value-key="booleanValue"
              />
            </FieldGroup>

            <FieldGroup :title="t('Display questions category')">
              <RadioChoice
                v-for="option in booleanOptions"
                :key="`display-category-${option.booleanValue}`"
                v-model="form.displayCategoryName"
                name="displayCategoryName"
                :option="option"
                value-key="booleanValue"
              />
            </FieldGroup>

            <FieldGroup :title="t('Hide question title')">
              <RadioChoice
                v-for="option in booleanOptions"
                :key="`hide-question-title-${option.booleanValue}`"
                v-model="form.hideQuestionTitle"
                name="hideQuestionTitle"
                :option="option"
                value-key="booleanValue"
              />
            </FieldGroup>
          </div>

          <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
            <BaseCheckbox
              v-if="settings.allowShowPreviousButtonSetting"
              id="exercise-show-previous-button"
              v-model="form.showPreviousButton"
              :label="t('Show previous button')"
              name="showPreviousButton"
            />
            <BaseCheckbox
              v-if="settings.allowHideQuestionNumberSetting"
              id="exercise-hide-question-number"
              v-model="form.hideQuestionNumber"
              :label="t('Hide question number')"
              name="hideQuestionNumber"
            />
            <BaseCheckbox
              id="exercise-auto-launch"
              v-model="form.autoLaunch"
              :label="t('Auto launch')"
              name="autoLaunch"
            />
          </div>
        </section>

        <section class="space-y-4 rounded-xl border border-gray-20 bg-white p-4 shadow-sm">
          <h2 class="text-base font-semibold text-gray-90">{{ t("Attempts and timing") }}</h2>

          <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
            <BaseInputNumber
              id="exercise-max-attempt"
              v-model="form.maxAttempt"
              :label="t('Max number of attempts')"
              name="maxAttempt"
              :min="0"
            />

            <BaseCheckbox
              id="exercise-enable-start-time"
              v-model="form.enableStartTime"
              :label="t('Enable start time')"
              name="enableStartTime"
            />

            <BaseCheckbox
              id="exercise-enable-end-time"
              v-model="form.enableEndTime"
              :label="t('Enable end time')"
              name="enableEndTime"
            />
          </div>

          <div class="grid gap-6 md:grid-cols-2">
            <BaseInputText
              v-if="form.enableStartTime"
              id="exercise-start-time"
              v-model="form.startTime"
              :label="t('Available from')"
              name="startTime"
              type="datetime-local"
            />

            <BaseInputText
              v-if="form.enableEndTime"
              id="exercise-end-time"
              v-model="form.endTime"
              :label="t('Until')"
              name="endTime"
              type="datetime-local"
            />
          </div>

          <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
            <BaseCheckbox
              id="exercise-propagate-negative-results"
              v-model="form.propagateNeg"
              :label="t('Propagate negative results between questions')"
              name="propagateNeg"
            />

            <BaseSelect
              id="exercise-save-correct-answers"
              v-model="form.saveCorrectAnswers"
              :label="t('Save answers')"
              name="saveCorrectAnswers"
              :options="saveCorrectAnswerOptions"
              option-label="label"
              option-value="value"
            />

            <BaseCheckbox
              id="exercise-review-answers"
              v-model="form.reviewAnswers"
              :label="t('Review my answers')"
              name="reviewAnswers"
            />

            <BaseCheckbox
              id="exercise-enable-time-control"
              v-model="form.enableTimeControl"
              :label="t('Enable time control')"
              name="enableTimeControl"
            />

            <BaseInputNumber
              v-if="form.enableTimeControl"
              id="exercise-expired-time"
              v-model="form.expiredTime"
              :help-text="t('Legacy stores this value as total duration in minutes.')"
              :label="t('Total duration in minutes of the test')"
              name="expiredTime"
              :min="0"
            />

            <BaseCheckbox
              id="exercise-prevent-backwards"
              v-model="form.preventBackwards"
              :label="t('Prevent moving backwards between questions')"
              name="preventBackwards"
            />
          </div>
        </section>

        <section class="space-y-4 rounded-xl border border-gray-20 bg-white p-4 shadow-sm">
          <h2 class="text-base font-semibold text-gray-90">{{ t("Score and final messages") }}</h2>

          <BaseInputNumber
            id="exercise-pass-percentage"
            v-model="form.passPercentage"
            :label="t('Pass percentage')"
            name="passPercentage"
            :max="100"
            :min="0"
          />
          <p class="-mt-3 text-sm text-gray-50">%</p>

          <BaseTinyEditor
            editor-id="exercise-text-when-finished"
            v-model="form.textWhenFinished"
            :editor-config="messageEditorConfig"
            :full-page="false"
            :title="t('Text appearing at the end of the test when the user has succeeded or if no pass percentage was set.')"
          />

          <BaseTinyEditor
            editor-id="exercise-text-when-finished-failure"
            v-model="form.textWhenFinishedFailure"
            :editor-config="messageEditorConfig"
            :full-page="false"
            :title="t('Text appearing at the end of the test when the user has failed.')"
          />
        </section>

        <section class="space-y-4 rounded-xl border border-gray-20 bg-white p-4 shadow-sm">
          <h2 class="text-base font-semibold text-gray-90">{{ t("Extra settings") }}</h2>

          <div class="grid gap-6 md:grid-cols-2">
            <BaseSelect
              v-if="settings.allowExerciseCategories && categoryOptions.length > 1"
              id="exercise-category"
              v-model="form.categoryId"
              :label="t('Category')"
              name="categoryId"
              :options="categoryOptions"
              option-label="label"
              option-value="value"
            />

            <BaseCheckbox
              id="exercise-hide-attempts-table"
              v-model="form.hideAttemptsTable"
              :label="t('Hide attempts table on start page')"
              name="hideAttemptsTable"
            />
          </div>

          <FieldGroup
            v-if="settings.allowNotificationSettingPerExercise"
            :title="t('E-mail notifications')"
          >
            <div class="grid gap-3 md:grid-cols-2">
              <label
                v-for="option in notificationOptions"
                :key="`notification-${option.value}`"
                class="flex cursor-pointer items-start gap-2 text-sm text-gray-90"
              >
                <input
                  :checked="isNotificationSelected(option.value)"
                  class="mt-0.5"
                  name="notifications[]"
                  type="checkbox"
                  :value="option.value"
                  @change="toggleNotification(option.value, $event.target.checked)"
                />
                <span>{{ option.label }}</span>
              </label>
            </div>
          </FieldGroup>
        </section>
      </div>

      <div class="flex flex-col-reverse items-stretch gap-3 pt-4 md:flex-row md:items-center md:justify-end">
        <BaseButton
          :label="t('Cancel')"
          :route="{ name: 'ExerciseList', params: route.params, query: getContextParams() }"
          icon="close"
          type="black"
        />
        <BaseButton
          :is-loading="isSaving"
          :label="t('Proceed to questions')"
          icon="edit"
          :is-submit="true"
          type="success"
        />
      </div>

      <p class="text-xs text-gray-90">* {{ t("Required field") }}</p>
    </form>
  </section>
</template>

<script setup>
import { computed, defineComponent, h, onMounted, reactive, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseCheckbox from "../../components/basecomponents/BaseCheckbox.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import BaseInputNumber from "../../components/basecomponents/BaseInputNumber.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseTinyEditor from "../../components/basecomponents/BaseTinyEditor.vue"
import exerciseService from "../../services/exerciseService"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()

const isLoading = ref(false)
const isSaving = ref(false)
const formSubmitted = ref(false)
const showAdvancedSettings = ref(false)
const errorMessage = ref("")
const successMessage = ref("")
const csrfToken = ref("")
const questionsUrl = ref("")
const settings = ref({})
const options = ref({})

const mainEditorConfig = {
  height: 320,
  menubar: false,
}

const messageEditorConfig = {
  height: 260,
  menubar: false,
}

const FieldGroup = defineComponent({
  name: "FieldGroup",
  props: {
    title: {
      type: String,
      required: true,
    },
  },
  setup(props, { slots }) {
    return () =>
      h("fieldset", { class: "space-y-2" }, [
        h("legend", { class: "mb-2 text-sm font-semibold text-gray-90" }, props.title),
        slots.default?.(),
      ])
  },
})

const RadioChoice = defineComponent({
  name: "RadioChoice",
  props: {
    modelValue: {
      type: [String, Number, Boolean],
      required: true,
    },
    name: {
      type: String,
      required: true,
    },
    option: {
      type: Object,
      required: true,
    },
    valueKey: {
      type: String,
      default: "value",
    },
  },
  emits: ["update:modelValue"],
  setup(props, { emit }) {
    return () => {
      const value = props.option[props.valueKey]
      const id = `exercise-${props.name}-${String(value).replace(/[^a-zA-Z0-9_-]/g, "-")}`

      return h("label", { class: "flex cursor-pointer items-start gap-2 text-sm text-gray-90", for: id }, [
        h("input", {
          id,
          checked: props.modelValue === value,
          class: "mt-0.5",
          name: props.name,
          type: "radio",
          value: String(value),
          onChange: () => emit("update:modelValue", value),
        }),
        h("span", props.option.label),
      ])
    }
  },
})

const form = reactive({
  title: "",
  description: "",
  type: 2,
  categoryId: 0,
  startTime: "",
  endTime: "",
  enableStartTime: false,
  enableEndTime: false,
  duration: 0,
  maxAttempt: 0,
  passPercentage: 0,
  random: 0,
  randomByCategory: 0,
  randomAnswers: false,
  showPreviousButton: true,
  preventBackwards: false,
  hideAttemptsTable: false,
  autoLaunch: false,
  notifications: [],
  accessCondition: "",
  sound: "",
  feedbackType: 0,
  resultsDisabled: 0,
  questionSelectionType: 1,
  displayCategoryName: true,
  hideQuestionTitle: false,
  hideQuestionNumber: false,
  propagateNeg: false,
  saveCorrectAnswers: 0,
  reviewAnswers: false,
  enableTimeControl: false,
  expiredTime: 0,
  displayChartDegreeCertainty: 0,
  sendEmailChartDegreeCertainty: 0,
  notDisplayBalancePercentageCategorieQuestion: 0,
  displayChartDegreeCertaintyCategory: 0,
  gatherQuestionsCategories: 0,
  pageResultConfiguration: {
    hideExpectedAnswers: false,
    hideTotalScore: false,
    hideQuestionScore: false,
    hideCategoryTable: false,
    hideCorrectAnsweredQuestions: false,
  },
  textWhenFinished: "",
  textWhenFinishedFailure: "",
})

const exerciseId = computed(() => Number(getQueryValue(route.params.exerciseId) || 0))
const isEditMode = computed(() => exerciseId.value > 0)
const questionsRoute = computed(() => ({
  name: "ExerciseQuestions",
  params: { ...route.params, exerciseId: exerciseId.value },
  query: getContextParams(),
}))
const typeOptions = computed(() =>
  (options.value.typeOptions || []).map((option) => ({
    value: Number(option.value),
    label: t(option.label),
  })),
)
const categoryOptions = computed(() => [
  { value: 0, label: t("No category") },
  ...(options.value.categoryOptions || []).map((option) => ({
    value: Number(option.value),
    label: option.label,
  })),
])
const feedbackOptions = computed(() => mapTranslatedOptions(options.value.feedbackOptions || []))
const resultOptions = computed(() => mapTranslatedOptions(options.value.resultOptions || []))
const questionSelectionTypeOptions = computed(() => mapTranslatedOptions(options.value.questionSelectionTypeOptions || []))
const randomByCategoryOptions = computed(() => mapTranslatedOptions(options.value.randomByCategoryOptions || []))
const notificationOptions = computed(() => mapTranslatedOptions(options.value.notificationOptions || []))
const saveCorrectAnswerOptions = computed(() => mapTranslatedOptions(options.value.saveCorrectAnswerOptions || []))
const showRandomQuestionCount = computed(() => [2, 3, 4, 5, 6].includes(Number(form.questionSelectionType)))
const showCategorySelectionOptions = computed(() => 3 <= Number(form.questionSelectionType))
const booleanOptions = computed(() => [
  { booleanValue: true, label: t("Yes") },
  { booleanValue: false, label: t("No") },
])

function mapTranslatedOptions(items) {
  return items.map((option) => ({
    value: Number(option.value),
    label: t(option.label),
  }))
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

function fillForm(data) {
  form.title = data.title || ""
  form.description = data.description || ""
  form.type = Number(data.type || 2)
  form.categoryId = Number(data.categoryId || 0)
  form.startTime = data.startTime || ""
  form.endTime = data.endTime || ""
  form.enableStartTime = Boolean(data.startTime)
  form.enableEndTime = Boolean(data.endTime)
  form.duration = Number(data.duration || 0)
  form.maxAttempt = Number(data.maxAttempt ?? 0)
  form.passPercentage = Number(data.passPercentage || 0)
  form.random = Number(data.random || 0)
  form.randomByCategory = Number(data.randomByCategory || 0)
  form.randomAnswers = true === data.randomAnswers
  form.showPreviousButton = true === data.showPreviousButton
  form.preventBackwards = true === data.preventBackwards
  form.hideAttemptsTable = true === data.hideAttemptsTable
  form.autoLaunch = true === data.autoLaunch
  form.notifications = normalizeNotificationValues(data.notifications || [])
  form.accessCondition = data.accessCondition || ""
  form.sound = data.sound || ""
  form.feedbackType = Number(data.feedbackType || 0)
  form.resultsDisabled = Number(data.resultsDisabled || 0)
  form.questionSelectionType = Number(data.questionSelectionType || 1)
  form.displayCategoryName = true === data.displayCategoryName
  form.hideQuestionTitle = true === data.hideQuestionTitle
  form.hideQuestionNumber = true === data.hideQuestionNumber
  form.propagateNeg = true === data.propagateNeg
  form.saveCorrectAnswers = Number(data.saveCorrectAnswers || 0)
  form.reviewAnswers = true === data.reviewAnswers
  form.expiredTime = Number(data.expiredTime || 0)
  form.displayChartDegreeCertainty = Number(data.displayChartDegreeCertainty || 0)
  form.sendEmailChartDegreeCertainty = Number(data.sendEmailChartDegreeCertainty || 0)
  form.notDisplayBalancePercentageCategorieQuestion = Number(data.notDisplayBalancePercentageCategorieQuestion || 0)
  form.displayChartDegreeCertaintyCategory = Number(data.displayChartDegreeCertaintyCategory || 0)
  form.gatherQuestionsCategories = Number(data.gatherQuestionsCategories || 0)
  form.enableTimeControl = 0 < form.expiredTime
  form.pageResultConfiguration = buildPageResultConfiguration(data.pageResultConfiguration || {})
  form.textWhenFinished = data.textWhenFinished || ""
  form.textWhenFinishedFailure = data.textWhenFinishedFailure || ""
  csrfToken.value = data.csrfToken || ""
  questionsUrl.value = data.questionsUrl || ""
  settings.value = data.settings || {}
  options.value = data.options || {}
}

function normalizeNotificationValues(values) {
  if (!Array.isArray(values)) {
    return []
  }

  return [...new Set(values.map((value) => Number(value)).filter((value) => value > 0))]
}

function isNotificationSelected(value) {
  return form.notifications.includes(Number(value))
}

function toggleNotification(value, checked) {
  const normalized = Number(value)
  if (checked && !form.notifications.includes(normalized)) {
    form.notifications = [...form.notifications, normalized]
    return
  }

  if (!checked) {
    form.notifications = form.notifications.filter((item) => item !== normalized)
  }
}

function buildPageResultConfiguration(source) {
  return {
    hideExpectedAnswers: true === source.hideExpectedAnswers,
    hideTotalScore: true === source.hideTotalScore,
    hideQuestionScore: true === source.hideQuestionScore,
    hideCategoryTable: true === source.hideCategoryTable,
    hideCorrectAnsweredQuestions: true === source.hideCorrectAnsweredQuestions,
  }
}

function buildPayload() {
  return {
    exerciseId: isEditMode.value ? exerciseId.value : null,
    title: form.title,
    description: form.description,
    type: Number(form.type || 2),
    categoryId: Number(form.categoryId || 0),
    startTime: form.enableStartTime ? form.startTime || null : null,
    endTime: form.enableEndTime ? form.endTime || null : null,
    duration: Number(form.duration || 0),
    maxAttempt: Number(form.maxAttempt || 0),
    passPercentage: Number(form.passPercentage || 0),
    random: Number(form.random || 0),
    randomByCategory: Number(form.randomByCategory || 0),
    randomAnswers: form.randomAnswers,
    showPreviousButton: form.showPreviousButton,
    preventBackwards: form.preventBackwards,
    hideAttemptsTable: form.hideAttemptsTable,
    autoLaunch: form.autoLaunch,
    notifications: normalizeNotificationValues(form.notifications),
    accessCondition: form.accessCondition,
    sound: form.sound,
    feedbackType: Number(form.feedbackType || 0),
    resultsDisabled: Number(form.resultsDisabled || 0),
    questionSelectionType: Number(form.questionSelectionType || 1),
    displayCategoryName: form.displayCategoryName,
    hideQuestionTitle: form.hideQuestionTitle,
    hideQuestionNumber: form.hideQuestionNumber,
    propagateNeg: form.propagateNeg,
    saveCorrectAnswers: Number(form.saveCorrectAnswers || 0),
    reviewAnswers: form.reviewAnswers,
    expiredTime: form.enableTimeControl ? Number(form.expiredTime || 0) : 0,
    displayChartDegreeCertainty: Number(form.displayChartDegreeCertainty || 0),
    sendEmailChartDegreeCertainty: Number(form.sendEmailChartDegreeCertainty || 0),
    notDisplayBalancePercentageCategorieQuestion: Number(form.notDisplayBalancePercentageCategorieQuestion || 0),
    displayChartDegreeCertaintyCategory: Number(form.displayChartDegreeCertaintyCategory || 0),
    gatherQuestionsCategories: Number(form.gatherQuestionsCategories || 0),
    pageResultConfiguration: buildPageResultConfiguration(form.pageResultConfiguration),
    textWhenFinished: form.textWhenFinished,
    textWhenFinishedFailure: form.textWhenFinishedFailure,
    csrfToken: csrfToken.value,
  }
}

async function loadConfiguration() {
  isLoading.value = true
  errorMessage.value = ""

  try {
    const response = await exerciseService.getExerciseConfiguration(getContextParams(), isEditMode.value ? exerciseId.value : null)
    fillForm(response)
  } catch (error) {
    console.error("Error loading exercise configuration", error)
    errorMessage.value = t("Could not load exercise configuration")
  } finally {
    isLoading.value = false
  }
}

async function saveConfiguration() {
  formSubmitted.value = true
  successMessage.value = ""
  errorMessage.value = ""

  if (!form.title.trim()) {
    errorMessage.value = t("The exercise title is required.")
    return
  }

  isSaving.value = true

  try {
    const response = await exerciseService.saveExerciseConfiguration(
      buildPayload(),
      getContextParams(),
      isEditMode.value ? exerciseId.value : null,
    )

    successMessage.value = t("Exercise settings saved.")
    fillForm(response)

    if (response.exerciseId) {
      await router.push({
        name: "ExerciseQuestions",
        params: { ...route.params, exerciseId: response.exerciseId },
        query: getContextParams(),
      })
    }
  } catch (error) {
    console.error("Error saving exercise configuration", error)
    errorMessage.value = error?.response?.data?.detail || error?.response?.data?.["hydra:description"] || t("Could not save exercise")
  } finally {
    isSaving.value = false
  }
}

watch(
  () => form.feedbackType,
  (feedbackType) => {
    if ([1, 3].includes(Number(feedbackType))) {
      form.resultsDisabled = 0
    }

    if (1 === Number(feedbackType)) {
      form.type = 2
    }
  },
)

watch(
  () => form.questionSelectionType,
  (selectionType) => {
    if (3 > Number(selectionType)) {
      form.randomByCategory = 0
    }
  },
)

onMounted(loadConfiguration)
</script>
