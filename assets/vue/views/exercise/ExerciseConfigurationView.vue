<template>
  <section class="space-y-5">
    <div class="flex flex-wrap items-center gap-1 rounded-xl border border-gray-20 bg-white px-2 py-1 shadow-sm w-fit">
      <BaseButton
        :label="backButtonLabel"
        :route="learningPathContext ? null : { name: 'ExerciseList', params: route.params, query: getContextParams() }"
        :to-url="learningPathContext ? learningPathBackUrl : null"
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
        :label="t('Test name') + ' *'"
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
                :disabled="isFieldLocked('random')"
                :help-text="t('Use 0 to keep all available questions. Use -1 where all available questions are allowed.')"
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

          <div
            v-if="showCategorySelectionOptions"
            class="space-y-3 rounded-lg border border-gray-20 bg-gray-5 p-3"
          >
            <div class="flex flex-col gap-1 md:flex-row md:items-center md:justify-between">
              <h3 class="text-sm font-semibold text-gray-90">{{ t("Question categories matrix") }}</h3>
              <p class="text-xs text-gray-50">{{ t("-1 = All questions will be selected.") }}</p>
            </div>

            <div
              v-if="hasCategoryMatrixWarning"
              class="rounded-lg border border-yellow-200 bg-yellow-50 p-3 text-sm text-yellow-800"
            >
              {{ t("Make sure you have enough questions in your categories.") }}
            </div>

            <div
              v-if="form.categoryMatrix.length"
              class="overflow-x-auto"
            >
              <table class="min-w-full divide-y divide-gray-20 text-sm">
                <thead>
                  <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-50">
                    <th class="px-3 py-2">{{ t("Categories") }}</th>
                    <th class="px-3 py-2">{{ t("Available questions") }}</th>
                    <th class="px-3 py-2">{{ t("Number of questions") }}</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-20 bg-white">
                  <tr
                    v-for="row in form.categoryMatrix"
                    :key="`category-matrix-${row.categoryId}`"
                  >
                    <td class="px-3 py-2 font-medium text-gray-90">{{ row.title }}</td>
                    <td class="px-3 py-2 text-gray-70">{{ row.availableQuestions }}</td>
                    <td class="px-3 py-2">
                      <BaseInputNumber
                        :id="`exercise-category-matrix-${row.categoryId}`"
                        v-model="row.countQuestions"
                        class="max-w-32"
                        :label="t('Number of questions')"
                        :name="`categoryMatrix[${row.categoryId}]`"
                        :min="-1"
                      />
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <p
              v-else
              class="text-sm text-gray-50"
            >
              {{ t("No question categories are available for this exercise yet.") }}
            </p>
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
              :label="t('Auto-launch')"
              name="autoLaunch"
            />
          </div>
        </section>

        <section class="space-y-4 rounded-xl border border-gray-20 bg-white p-4 shadow-sm">
          <h2 class="text-base font-semibold text-gray-90">{{ t("Attempts and timing") }}</h2>

          <p
            v-if="effectiveLockedFields.length"
            class="text-sm text-gray-50"
          >
            {{ t("Some options are locked after the exercise has been created.") }}
          </p>

          <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
            <BaseInputNumber
              id="exercise-max-attempt"
              v-model="form.maxAttempt"
              :disabled="isFieldLocked('maxAttempt')"
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
              :disabled="isFieldLocked('propagateNeg')"
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
              :disabled="isFieldLocked('reviewAnswers')"
              :label="t('Review my answers')"
              name="reviewAnswers"
            />

            <BaseCheckbox
              id="exercise-enable-time-control"
              v-model="form.enableTimeControl"
              :disabled="isFieldLocked('enableTimeControl')"
              :label="t('Enable time control')"
              name="enableTimeControl"
            />

            <BaseInputNumber
              v-if="form.enableTimeControl"
              id="exercise-expired-time"
              v-model="form.expiredTime"
              :disabled="isFieldLocked('expiredTime')"
              :help-text="t('This value is stored as total duration in minutes.')"
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

          <div
            v-if="gradebookCategoryOptions.length > 0"
            class="space-y-3 rounded-lg border border-gray-20 bg-gray-5 p-3"
          >
            <h3 class="text-sm font-semibold text-gray-90">{{ t("Assessment") }}</h3>
            <BaseCheckbox
              id="exercise-add-to-gradebook"
              v-model="form.addToGradebook"
              :label="t('Add this test to the assessment tool')"
              name="addToGradebook"
            />
            <div
              v-if="form.addToGradebook"
              class="grid gap-4 md:grid-cols-3"
            >
              <BaseSelect
                id="exercise-gradebook-category"
                v-model="form.gradebookCategoryId"
                :label="t('Assessment')"
                name="gradebookCategoryId"
                :options="gradebookCategoryOptions"
                option-label="label"
                option-value="value"
              />
              <BaseInputNumber
                id="exercise-gradebook-weight"
                v-model="form.gradebookWeight"
                :label="t('Weight')"
                name="gradebookWeight"
                :min="0"
              />
              <BaseCheckbox
                id="exercise-gradebook-visible"
                v-model="form.gradebookVisible"
                :label="t('Visible')"
                name="gradebookVisible"
              />
            </div>
          </div>

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

            <BaseSelect
              v-if="languageOptions.length > 0"
              id="exercise-language"
              v-model="form.language"
              :label="t('Language')"
              name="language"
              :options="languageOptions"
              option-label="label"
              option-value="value"
            />

            <BaseCheckbox
              id="exercise-hide-attempts-table"
              v-model="form.hideAttemptsTable"
              :label="t('Hide attempts table on start page')"
              name="hideAttemptsTable"
            />

            <BaseCheckbox
              v-if="isEditMode"
              id="exercise-update-title-in-learning-paths"
              v-model="form.updateTitleInLearningPaths"
              :label="t('Update this title in learning paths')"
              name="updateTitleInLearningPaths"
            />
          </div>

          <div
            v-if="skillOptions.length || extraFieldDefinitions.length"
            class="space-y-4 rounded-lg border border-gray-20 bg-gray-5 p-3"
          >
            <h3 class="text-sm font-semibold text-gray-90">{{ t("Metadata") }}</h3>

            <BaseMultiSelect
              v-if="skillOptions.length"
              v-model="form.skillIds"
              input-id="exercise-skills"
              :label="t('Skills')"
              :options="skillOptions"
              option-label="label"
              option-value="value"
            />

            <div
              v-if="extraFieldDefinitions.length"
              class="grid gap-4 md:grid-cols-2"
            >
              <template
                v-for="field in extraFieldDefinitions"
                :key="`exercise-extra-field-${field.variable}`"
              >
                <BaseTextArea
                  v-if="isExtraTextAreaField(field)"
                  :id="`exercise-extra-${field.variable}`"
                  v-model="form.extraFieldValues[field.variable]"
                  :label="field.label"
                  :name="`extra_${field.variable}`"
                  rows="4"
                />

                <BaseMultiSelect
                  v-else-if="isExtraMultiChoiceField(field)"
                  v-model="form.extraFieldValues[field.variable]"
                  :input-id="`exercise-extra-${field.variable}`"
                  :label="field.label"
                  :options="field.options"
                  option-label="label"
                  option-value="value"
                />

                <BaseSelect
                  v-else-if="isExtraChoiceField(field)"
                  :id="`exercise-extra-${field.variable}`"
                  v-model="form.extraFieldValues[field.variable]"
                  :label="field.label"
                  :name="`extra_${field.variable}`"
                  :options="field.options"
                  option-label="label"
                  option-value="value"
                />

                <BaseCheckbox
                  v-else-if="isExtraCheckboxField(field)"
                  :id="`exercise-extra-${field.variable}`"
                  v-model="form.extraFieldValues[field.variable]"
                  :label="field.label"
                  :name="`extra_${field.variable}`"
                />

                <BaseInputText
                  v-else
                  :id="`exercise-extra-${field.variable}`"
                  v-model="form.extraFieldValues[field.variable]"
                  :label="field.label"
                  :name="`extra_${field.variable}`"
                  :type="getExtraFieldInputType(field)"
                />
              </template>
            </div>
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
          :label="learningPathContext ? t('Cancel and return to learning path') : t('Cancel')"
          :route="learningPathContext ? null : { name: 'ExerciseList', params: route.params, query: getContextParams() }"
          :to-url="learningPathContext ? learningPathBackUrl : null"
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
import BaseMultiSelect from "../../components/basecomponents/BaseMultiSelect.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseTextArea from "../../components/basecomponents/BaseTextArea.vue"
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
const lockedFields = ref([])
const defaultLockedFieldsOnEdit = [
  "random",
  "maxAttempt",
  "propagateNeg",
  "enableTimeControl",
  "expiredTime",
  "reviewAnswers",
]
const EXTRA_FIELD_TEXTAREA = 2
const EXTRA_FIELD_RADIO = 3
const EXTRA_FIELD_SELECT = 4
const EXTRA_FIELD_SELECT_MULTIPLE = 5
const EXTRA_FIELD_DATE = 6
const EXTRA_FIELD_DATETIME = 7
const EXTRA_FIELD_CHECKBOX = 13
const EXTRA_FIELD_INTEGER = 15
const EXTRA_FIELD_FLOAT = 17

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
  language: "",
  updateTitleInLearningPaths: false,
  skillIds: [],
  extraFieldValues: {},
  extraNotification: "",
  startTime: "",
  endTime: "",
  enableStartTime: false,
  enableEndTime: false,
  duration: 0,
  maxAttempt: 0,
  passPercentage: 0,
  random: 0,
  randomByCategory: 0,
  categoryMatrix: [],
  randomAnswers: false,
  showPreviousButton: true,
  preventBackwards: false,
  hideAttemptsTable: false,
  autoLaunch: false,
  addToGradebook: false,
  gradebookCategoryId: null,
  gradebookWeight: 100,
  gradebookVisible: true,
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
const learningPathContext = computed(() => isLearningPathContext())
const learningPathBackUrl = computed(() => buildLearningPathBackUrl())
const backButtonLabel = computed(() => (learningPathContext.value ? t("Back to learning path") : t("Return to exercises list")))
const typeOptions = computed(() => {
  const items = (options.value.typeOptions || []).map((option) => ({
    value: Number(option.value),
    label: t(option.label),
  }))

  if ([1, 3].includes(Number(form.feedbackType))) {
    return items.filter((option) => 2 === option.value)
  }

  return items
})
const categoryOptions = computed(() => [
  { value: 0, label: t("No category") },
  ...(options.value.categoryOptions || []).map((option) => ({
    value: Number(option.value),
    label: option.label,
  })),
])
const gradebookCategoryOptions = computed(() =>
  (options.value.gradebookCategoryOptions || []).map((option) => ({
    value: Number(option.value),
    label: option.label,
  })),
)
const languageOptions = computed(() =>
  (options.value.languageOptions || []).map((option) => ({
    value: option.value || "",
    label: t(option.label),
  })),
)
const skillOptions = computed(() =>
  (options.value.skillOptions || []).map((option) => ({
    value: Number(option.value),
    label: option.label,
  })),
)
const extraFieldDefinitions = computed(() => normalizeExtraFieldDefinitions(options.value.extraFieldDefinitions || []))
const extraNotificationOptions = computed(() => {
  const items = (options.value.extraNotificationOptions || []).map((option) => ({
    value: String(option.value || ""),
    label: option.label,
  }))

  return items.length ? [{ value: "", label: t("Please select an option") }, ...items] : []
})
const feedbackOptions = computed(() => mapTranslatedOptions(options.value.feedbackOptions || []))
const resultOptions = computed(() => {
  const items = mapTranslatedOptions(options.value.resultOptions || [])

  if ([1, 3].includes(Number(form.feedbackType))) {
    return items.filter((option) => [0, 1, 2].includes(option.value))
  }

  return items
})
const questionSelectionTypeOptions = computed(() => mapTranslatedOptions(options.value.questionSelectionTypeOptions || []))
const randomByCategoryOptions = computed(() => mapTranslatedOptions(options.value.randomByCategoryOptions || []))
const notificationOptions = computed(() => mapTranslatedOptions(options.value.notificationOptions || []))
const saveCorrectAnswerOptions = computed(() => mapTranslatedOptions(options.value.saveCorrectAnswerOptions || []))
const showRandomQuestionCount = computed(() => [2, 3, 4, 5, 6].includes(Number(form.questionSelectionType)))
const showCategorySelectionOptions = computed(() => 3 <= Number(form.questionSelectionType))
const hasCategoryMatrixWarning = computed(() =>
  form.categoryMatrix.some((row) => {
    const count = Number(row.countQuestions || 0)
    const available = Number(row.availableQuestions || 0)

    return count > available && -1 !== count
  }),
)
const booleanOptions = computed(() => [
  { booleanValue: true, label: t("Yes") },
  { booleanValue: false, label: t("No") },
])
const effectiveLockedFields = computed(() => {
  if (lockedFields.value.length > 0) {
    return lockedFields.value
  }

  return isEditMode.value ? defaultLockedFieldsOnEdit : []
})

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
  const params = {
    cid: getQueryValue(route.query.cid),
    sid: getQueryValue(route.query.sid),
    gid: getQueryValue(route.query.gid),
  }

  for (const key of ["origin", "lp_id", "learnpath_id", "node", "type", "returnToLp", "isStudentView", "gradebook"]) {
    const value = getQueryValue(route.query[key])
    if (value !== undefined && value !== null && String(value) !== "") {
      params[key] = value
    }
  }

  return params
}

function isLearningPathContext() {
  const origin = String(getQueryValue(route.query.origin) || "").toLowerCase()
  const returnToLp = String(getQueryValue(route.query.returnToLp) || "").toLowerCase()
  const lpId = Number(getQueryValue(route.query.lp_id) || getQueryValue(route.query.learnpath_id) || 0)

  return lpId > 0 && (origin === "learnpath" || ["1", "true", "yes"].includes(returnToLp))
}

function buildLearningPathBackUrl() {
  const params = new URLSearchParams()
  params.set("action", "build")
  params.set("type", getQueryValue(route.query.type) || "step")
  params.set("lp_id", getQueryValue(route.query.lp_id) || getQueryValue(route.query.learnpath_id) || "0")

  for (const key of ["cid", "sid", "gid", "gradebook", "origin", "node", "isStudentView"]) {
    const value = getQueryValue(route.query[key])
    if (value !== undefined && value !== null && String(value) !== "") {
      params.set(key, String(value))
    }
  }

  return `/main/lp/lp_controller.php?${params.toString()}#resource_tab-2`
}

function fillForm(data) {
  form.title = data.title || ""
  form.description = data.description || ""
  form.type = Number(data.type || 2)
  form.categoryId = Number(data.categoryId || 0)
  form.language = data.language || ""
  form.updateTitleInLearningPaths = false
  form.skillIds = normalizeIdValues(data.skillIds || [])
  form.extraNotification = data.extraNotification || ""
  form.startTime = data.startTime || ""
  form.endTime = data.endTime || ""
  form.enableStartTime = Boolean(data.startTime)
  form.enableEndTime = Boolean(data.endTime)
  form.duration = Number(data.duration || 0)
  form.maxAttempt = Number(data.maxAttempt ?? 0)
  form.passPercentage = Number(data.passPercentage || 0)
  form.random = Number(data.random || 0)
  form.randomByCategory = Number(data.randomByCategory || 0)
  form.categoryMatrix = normalizeCategoryMatrix(data.categoryMatrix || [])
  form.randomAnswers = true === data.randomAnswers
  form.showPreviousButton = true === data.showPreviousButton
  form.preventBackwards = true === data.preventBackwards
  form.hideAttemptsTable = true === data.hideAttemptsTable
  form.autoLaunch = true === data.autoLaunch
  form.addToGradebook = true === data.addToGradebook
  form.gradebookCategoryId = data.gradebookCategoryId ? Number(data.gradebookCategoryId) : null
  form.gradebookWeight = Number(data.gradebookWeight || 100)
  form.gradebookVisible = false !== data.gradebookVisible
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
  form.extraFieldValues = normalizeExtraFieldValues(data.extraFieldValues || {}, extraFieldDefinitions.value)
  lockedFields.value = Array.isArray(data.lockedFields) ? data.lockedFields : []
}

function normalizeIdValues(values) {
  if (!Array.isArray(values)) {
    return []
  }

  return [...new Set(values.map((value) => Number(value)).filter((value) => value > 0))]
}

function normalizeExtraFieldDefinitions(definitions) {
  if (!Array.isArray(definitions)) {
    return []
  }

  return definitions
    .filter((field) => field && field.variable)
    .map((field) => ({
      variable: field.variable,
      label: field.label || field.variable,
      type: Number(field.type || 1),
      defaultValue: field.defaultValue ?? "",
      options: Array.isArray(field.options)
        ? field.options.map((option) => ({ value: String(option.value ?? ""), label: option.label || String(option.value ?? "") }))
        : [],
    }))
}

function normalizeExtraFieldValues(values, definitions) {
  const normalized = {}

  definitions.forEach((field) => {
    const value = values[field.variable] ?? field.defaultValue ?? ""

    if (isExtraMultiChoiceField(field)) {
      normalized[field.variable] = Array.isArray(value)
        ? value.map((item) => String(item)).filter((item) => item !== "")
        : String(value || "")
            .split(";")
            .filter((item) => item !== "")
      return
    }

    if (isExtraCheckboxField(field)) {
      normalized[field.variable] = true === value || "1" === String(value) || "true" === String(value).toLowerCase()
      return
    }

    normalized[field.variable] = value ?? ""
  })

  return normalized
}

function normalizeExtraFieldValuesForPayload() {
  const normalized = {}

  extraFieldDefinitions.value.forEach((field) => {
    const value = form.extraFieldValues[field.variable]

    if (isExtraMultiChoiceField(field)) {
      normalized[field.variable] = Array.isArray(value) ? value : []
      return
    }

    normalized[field.variable] = value ?? ""
  })

  return normalized
}

function isExtraTextAreaField(field) {
  return EXTRA_FIELD_TEXTAREA === Number(field.type)
}

function isExtraChoiceField(field) {
  return [EXTRA_FIELD_RADIO, EXTRA_FIELD_SELECT].includes(Number(field.type))
}

function isExtraMultiChoiceField(field) {
  return EXTRA_FIELD_SELECT_MULTIPLE === Number(field.type) || (EXTRA_FIELD_CHECKBOX === Number(field.type) && field.options.length > 0)
}

function isExtraCheckboxField(field) {
  return EXTRA_FIELD_CHECKBOX === Number(field.type) && 0 === field.options.length
}

function getExtraFieldInputType(field) {
  const type = Number(field.type)

  if (EXTRA_FIELD_INTEGER === type || EXTRA_FIELD_FLOAT === type) {
    return "number"
  }

  if (EXTRA_FIELD_DATE === type) {
    return "date"
  }

  if (EXTRA_FIELD_DATETIME === type) {
    return "datetime-local"
  }

  return "text"
}

function normalizeCategoryMatrix(rows) {
  if (!Array.isArray(rows)) {
    return []
  }

  return rows.map((row) => ({
    categoryId: Number(row.categoryId || 0),
    title: row.title || t("General"),
    availableQuestions: Number(row.availableQuestions || 0),
    countQuestions: Number(row.countQuestions ?? -1),
  }))
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

function isFieldLocked(fieldName) {
  return effectiveLockedFields.value.includes(fieldName)
}

function buildPayload() {
  return {
    exerciseId: isEditMode.value ? exerciseId.value : null,
    title: form.title,
    description: form.description,
    type: Number(form.type || 2),
    categoryId: Number(form.categoryId || 0),
    language: form.language || "",
    updateTitleInLearningPaths: form.updateTitleInLearningPaths,
    skillIds: normalizeIdValues(form.skillIds),
    extraFieldValues: normalizeExtraFieldValuesForPayload(),
    extraNotification: form.extraNotification || "",
    startTime: form.enableStartTime ? form.startTime || null : null,
    endTime: form.enableEndTime ? form.endTime || null : null,
    duration: Number(form.duration || 0),
    maxAttempt: Number(form.maxAttempt || 0),
    passPercentage: Number(form.passPercentage || 0),
    random: Number(form.random || 0),
    randomByCategory: Number(form.randomByCategory || 0),
    categoryMatrix: normalizeCategoryMatrix(form.categoryMatrix),
    randomAnswers: form.randomAnswers,
    showPreviousButton: form.showPreviousButton,
    preventBackwards: form.preventBackwards,
    hideAttemptsTable: form.hideAttemptsTable,
    autoLaunch: form.autoLaunch,
    addToGradebook: form.addToGradebook,
    gradebookCategoryId: form.addToGradebook ? Number(form.gradebookCategoryId || 0) : null,
    gradebookWeight: Number(form.gradebookWeight || 0),
    gradebookVisible: form.gradebookVisible,
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
    errorMessage.value = t("The title is required.")
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

    if ([1, 3].includes(Number(feedbackType))) {
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
