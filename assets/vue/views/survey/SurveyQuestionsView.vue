<template>
  <section class="space-y-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
      <div>
        <h1 class="text-3xl font-bold text-gray-90">{{ t("Survey questions") }}</h1>
        <p
          v-if="survey.title"
          class="mt-1 text-sm text-gray-600"
        >
          {{ displayText(survey.title, t("Untitled")) }}
        </p>
      </div>

      <div class="flex flex-wrap items-center justify-end gap-2">
        <BaseButton
          v-if="!isLearningPathContext"
          :label="t('Back to survey list')"
          :route="buildListRoute()"
          icon="back"
          type="black"
        />
        <BaseButton
          v-if="isLearningPathContext"
          :disabled="questions.length === 0"
          :label="t('Finish and return to learning path')"
          icon="check"
          type="success"
          @click="finishLearningPathSurvey"
        />
        <BaseButton
          :label="t('Configure')"
          :route="buildConfigureRoute()"
          icon="settings"
          type="secondary"
        />
      </div>
    </div>

    <div class="border-b border-gray-20" />

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

    <div
      v-if="isLearningPathContext && questions.length === 0 && !isLoading"
      class="rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-700"
    >
      {{ t("Add at least one question before returning to the learning path.") }}
    </div>

    <div
      v-if="hasAnswers && !settings.allowAnsweredQuestionEdit"
      class="rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-700"
    >
      {{ t("This survey already has answers. Question editing is disabled by configuration.") }}
    </div>

    <div
      v-if="canEdit"
      class="rounded-2xl border border-gray-20 bg-white p-6 shadow-sm"
    >
      <div class="mb-4 flex flex-col gap-1">
        <h2 class="text-lg font-semibold text-gray-90">{{ t("Questions") }}</h2>
        <p class="text-sm text-gray-600">{{ t("Choose the type of question you want to add.") }}</p>
      </div>

      <div class="flex flex-wrap gap-4">
        <button
          v-for="questionType in questionTypeCards"
          :key="questionType.value"
          :title="questionType.label"
          class="group flex h-24 w-24 flex-col items-center justify-center gap-2 rounded-xl border border-gray-20 bg-white p-3 text-center shadow-sm transition hover:-translate-y-0.5 hover:border-primary hover:shadow-md focus:outline-none focus:ring-2 focus:ring-primary"
          type="button"
          @click="startCreate(questionType.value)"
        >
          <BaseIcon
            :icon="questionType.icon"
            size="big"
            zoom-trigger="group"
          />
          <span class="text-xs font-semibold leading-tight text-gray-700 group-hover:text-primary">
            {{ questionType.label }}
          </span>
        </button>
      </div>
    </div>

    <form
      v-if="canEdit && isFormVisible"
      ref="formSection"
      class="scroll-mt-6 space-y-6 rounded-2xl border border-gray-20 bg-white p-6 shadow-sm"
      novalidate
      @submit.prevent="submitQuestion"
    >
      <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div class="flex items-center gap-3">
          <BaseIcon
            icon="edit"
            size="small"
          />
          <h2 class="text-lg font-semibold text-gray-90">
            {{ isEditing ? t("Edit question") : t("Add a question") }}
          </h2>
        </div>
        <BaseButton
          :label="t('Cancel')"
          icon="close"
          type="black"
          @click="cancelForm"
        />
      </div>

      <div class="grid gap-6 md:grid-cols-2">
        <BaseSelect
          id="survey_question_type"
          v-model="form.type"
          :disabled="isEditing"
          :label="t('Question type')"
          :options="questionTypeOptions"
          name="question_type"
        />

        <BaseSelect
          v-if="requiresDisplay"
          id="survey_question_display"
          v-model="form.display"
          :label="t('Display')"
          :options="displayOptions"
          name="question_display"
        />
      </div>

      <div
        v-if="typeHelpText"
        class="rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-700"
      >
        {{ typeHelpText }}
      </div>

      <BaseTinyEditor
        editor-id="survey_question_text"
        v-model="form.question"
        :editor-config="smallEditorConfig"
        :full-page="false"
        :title="t('Question')"
      />

      <div
        v-if="allowsParent"
        class="grid gap-6 md:grid-cols-2"
      >
        <BaseSelect
          id="survey_question_parent"
          v-model="form.parentQuestionId"
          :label="t('Parent')"
          :options="availableParentQuestions"
          name="parent_id"
        />

        <BaseSelect
          v-if="Number(form.parentQuestionId || 0) > 0"
          id="survey_question_parent_option"
          v-model="form.parentOptionId"
          :label="t('Option')"
          :options="availableParentOptions"
          name="parent_option_id"
        />
      </div>

      <div class="grid gap-6 md:grid-cols-2">
        <BaseCheckbox
          v-if="showsMandatoryField"
          id="survey_question_required"
          v-model="form.isRequired"
          :label="t('Mandatory?')"
          name="is_required"
        />

        <BaseInputNumber
          v-if="requiresMaxValue"
          id="survey_question_max_value"
          v-model="form.maxValue"
          :label="t('Maximum score')"
          :max="100"
          :min="1"
          name="maximum_score"
        />
      </div>

      <div
        v-if="needsOptions"
        class="rounded-xl border border-gray-20 bg-gray-10 p-4"
      >
        <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
          <div>
            <h3 class="font-semibold text-gray-90">{{ t("Answer options") }}</h3>
            <p
              v-if="form.type === 'multiplechoiceother'"
              class="mt-1 text-xs text-gray-600"
            >
              {{ t("The free text option is added automatically.") }}
            </p>
          </div>
          <BaseButton
            v-if="canAddOptions"
            :label="t('Add option')"
            icon="plus"
            size="small"
            type="success"
            @click="addOption"
          />
        </div>

        <div class="space-y-3">
          <div
            v-for="(option, index) in editableOptions"
            :key="option.localId"
            class="grid gap-3 rounded-lg border border-gray-20 bg-white p-3 md:grid-cols-[1fr_auto]"
          >
            <BaseInputText
              v-if="usesPlainOptionInput"
              :id="`survey_question_option_${index}`"
              v-model="option.text"
              :label="t('Option') + ' ' + (index + 1)"
              :name="`answers_${index}`"
            />
            <BaseTinyEditor
              v-else
              :editor-id="`survey_question_option_${index}`"
              v-model="option.text"
              :editor-config="optionEditorConfig"
              :full-page="false"
              :title="t('Option') + ' ' + (index + 1)"
            />
            <div class="flex items-center justify-end gap-1">
              <BaseButton
                :disabled="index === 0"
                :label="t('Move up')"
                icon="up"
                only-icon
                size="small"
                type="secondary-text"
                @click="moveOption(index, -1)"
              />
              <BaseButton
                :disabled="index === editableOptions.length - 1"
                :label="t('Move down')"
                icon="down"
                only-icon
                size="small"
                type="secondary-text"
                @click="moveOption(index, 1)"
              />
              <BaseButton
                v-if="canRemoveOptions"
                :label="t('Delete')"
                icon="delete"
                only-icon
                size="small"
                type="danger-text"
                @click="removeOption(index)"
              />
            </div>
          </div>
        </div>
      </div>

      <div class="flex flex-wrap justify-end gap-2 border-t border-gray-20 pt-4">
        <BaseButton
          :label="t('Cancel')"
          icon="close"
          type="black"
          @click="cancelForm"
        />
        <BaseButton
          :is-loading="isSaving"
          :label="isEditing ? t('Edit question') : t('Create question')"
          icon="save"
          is-submit
          type="success"
        />
      </div>
    </form>


    <BaseTable
      :is-loading="isLoading"
      :text-for-empty="t('No questions found')"
      :total-items="questions.length"
      :values="questions"
      data-key="iid"
    >
      <Column
        :header="t('Question')"
        field="question"
        sortable
      >
        <template #body="{ data }">
          <div class="min-w-80">
            <div class="flex items-start gap-2">
              <BaseIcon
                :icon="questionTypeIcon(data.type)"
                size="small"
              />
              <div>
                <p class="font-semibold text-gray-90">
                  {{ displayText(data.question, t("Untitled")) }}
                </p>
                <p
                  v-if="displayText(data.comment)"
                  class="mt-1 text-xs text-gray-500"
                >
                  {{ displayText(data.comment) }}
                </p>
                <div class="mt-2 flex flex-wrap gap-2 text-xs">
                  <span class="rounded-full bg-gray-100 px-2 py-0.5 text-gray-700">
                    {{ t(data.typeLabel || data.type) }}
                  </span>
                  <span
                    v-if="data.isRequired"
                    class="rounded-full bg-red-100 px-2 py-0.5 text-red-700"
                  >
                    {{ t("Mandatory") }}
                  </span>
                  <span
                    v-if="!data.isSupported"
                    class="rounded-full bg-blue-100 px-2 py-0.5 text-blue-700"
                  >
                    {{ t("Unsupported question type") }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </template>
      </Column>

      <Column
        :header="t('Type')"
        field="typeLabel"
        sortable
      >
        <template #body="{ data }">
          {{ t(data.typeLabel || data.type) }}
        </template>
      </Column>

      <Column
        :header="t('Options')"
        field="optionCount"
        sortable
      >
        <template #body="{ data }">
          <span class="font-semibold text-gray-800">{{ data.optionCount || 0 }}</span>
        </template>
      </Column>

      <Column
        :header="t('Order')"
        field="sort"
        sortable
      >
        <template #body="{ data }">
          <span class="font-mono text-xs text-gray-700">{{ data.sort }}</span>
        </template>
      </Column>

      <Column
        :header="t('Actions')"
        class="w-48"
      >
        <template #body="{ data }">
          <div class="flex flex-wrap justify-end gap-1">
            <BaseButton
              v-if="data.canEdit"
              :label="t('Edit')"
              icon="edit"
              only-icon
              size="small"
              type="secondary-text"
              @click="startEdit(data)"
            />
            <BaseButton
              v-if="data.canCopy"
              :label="t('Copy')"
              icon="copy"
              only-icon
              size="small"
              type="secondary-text"
              @click="copyQuestion(data)"
            />
            <BaseButton
              v-if="data.canMoveUp"
              :label="t('Move up')"
              icon="up"
              only-icon
              size="small"
              type="secondary-text"
              @click="moveQuestion(data, 'up')"
            />
            <BaseButton
              v-if="data.canMoveDown"
              :label="t('Move down')"
              icon="down"
              only-icon
              size="small"
              type="secondary-text"
              @click="moveQuestion(data, 'down')"
            />
            <BaseButton
              v-if="data.canDelete"
              :label="t('Delete')"
              icon="delete"
              only-icon
              size="small"
              type="danger-text"
              @click="confirmDelete(data)"
            />
          </div>
        </template>
      </Column>
    </BaseTable>

  </section>
</template>

<script setup>
import { computed, nextTick, onMounted, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseCheckbox from "../../components/basecomponents/BaseCheckbox.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import BaseInputNumber from "../../components/basecomponents/BaseInputNumber.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import BaseTinyEditor from "../../components/basecomponents/BaseTinyEditor.vue"
import { useConfirmation } from "../../composables/useConfirmation"
import surveyService from "../../services/surveyService"

const { t } = useI18n()
const route = useRoute()
const { requireConfirmation } = useConfirmation()

const survey = ref({})
const questions = ref([])
const settings = ref({})
const choices = ref({})
const csrfToken = ref("")
const canEdit = ref(false)
const hasAnswers = ref(false)
const isLoading = ref(false)
const isSaving = ref(false)
const isFormVisible = ref(false)
const isEditing = ref(false)
const errorMessage = ref("")
const successMessage = ref("")
const form = ref(createEmptyForm())
const formSection = ref(null)

const surveyId = computed(() => Number(route.params.surveyId || 0))
const questionTypeOptions = computed(() => translateOptions(choices.value.types || []))
const questionTypeCards = computed(() =>
  questionTypeOptions.value.map((questionType) => ({
    ...questionType,
    icon: questionTypeIcon(questionType.value),
  })),
)
const displayOptions = computed(() => translateOptions(choices.value.display || []))
const parentQuestions = computed(() => translateOptions(choices.value.parentQuestions || []))
const parentOptionMap = computed(() => choices.value.parentOptions || {})
const needsOptions = computed(() => optionTypes.includes(form.value.type) && form.value.type !== "percentage")
const requiresDisplay = computed(() => displayTypes.includes(form.value.type))
const requiresMaxValue = computed(() => form.value.type === "score")
const allowsParent = computed(() => form.value.type !== "pagebreak")
const showsMandatoryField = computed(() => ["yesno", "multiplechoice"].includes(form.value.type))
const usesPlainOptionInput = computed(() => form.value.type === "dropdown")
const editableOptions = computed(() => form.value.options.filter((option) => !option.isOther))
const canAddOptions = computed(() => !["yesno", "percentage", "selectivedisplay"].includes(form.value.type))
const canRemoveOptions = computed(() => !["yesno", "percentage", "selectivedisplay"].includes(form.value.type) && editableOptions.value.length > 2)
const availableParentQuestions = computed(() => {
  const currentSort = Number(form.value.sort || Number.MAX_SAFE_INTEGER)
  const currentId = Number(form.value.questionId || 0)

  return parentQuestions.value.filter((question) => {
    const value = Number(question.value || 0)
    if (value === 0) {
      return true
    }

    if (currentId > 0 && value === currentId) {
      return false
    }

    return Number(question.sort || 0) < currentSort
  })
})
const availableParentOptions = computed(() => {
  const parentQuestionId = Number(form.value.parentQuestionId || 0)

  return translateOptions(parentOptionMap.value[parentQuestionId] || [])
})
const typeHelpText = computed(() => {
  if (form.value.type === "open") {
    return t("You can use the tags {{class_name}} and {{student_full_name}} in the question.")
  }

  if (form.value.type === "selectivedisplay") {
    return t("This question is shown conditionally while answering the survey.")
  }

  if (form.value.type === "multiplechoiceother") {
    return t("The free text option is added automatically.")
  }

  if (form.value.type === "percentage") {
    return t("The percentage options from 1 to 100 are generated automatically.")
  }

  return ""
})

const optionTypes = [
  "yesno",
  "multiplechoice",
  "multipleresponse",
  "dropdown",
  "score",
  "percentage",
  "multiplechoiceother",
  "selectivedisplay",
]
const displayTypes = ["yesno", "multiplechoice", "multipleresponse", "multiplechoiceother", "selectivedisplay"]
const smallEditorConfig = {
  height: 160,
  menubar: false,
}
const optionEditorConfig = {
  height: 260,
  min_height: 220,
  menubar: false,
  resize: true,
}

function getQueryValue(value) {
  if (Array.isArray(value)) {
    return value[0] || ""
  }

  return value || ""
}

function cleanQueryParams(params = {}) {
  const cleanParams = {}

  for (const [key, value] of Object.entries(params)) {
    if (value !== undefined && value !== null && String(value) !== "") {
      cleanParams[key] = value
    }
  }

  return cleanParams
}

function getContextParams(extra = {}) {
  return cleanQueryParams({
    cid: getQueryValue(route.query.cid),
    sid: getQueryValue(route.query.sid),
    gid: getQueryValue(route.query.gid),
    origin: getQueryValue(route.query.origin),
    lp_id: getQueryValue(route.query.lp_id),
    lpItemId: getQueryValue(route.query.lpItemId || route.query.lp_item_id),
    type: getQueryValue(route.query.type),
    returnToLp: getQueryValue(route.query.returnToLp),
    isStudentView: getQueryValue(route.query.isStudentView),
    ...extra,
  })
}

const learningPathId = computed(() => Number(getQueryValue(route.query.lp_id) || 0))

const isLearningPathContext = computed(() => {
  return getQueryValue(route.query.origin) === "learnpath" && learningPathId.value > 0
})

function createEmptyForm() {
  return {
    questionId: null,
    question: "",
    comment: "",
    type: "open",
    display: "vertical",
    isRequired: false,
    maxValue: 5,
    parentQuestionId: 0,
    parentOptionId: 0,
    sort: Number.MAX_SAFE_INTEGER,
    options: [],
  }
}

function buildListRoute() {
  return {
    name: "SurveyList",
    params: { node: route.params.node },
    query: getContextParams(),
  }
}

function buildConfigureRoute() {
  return {
    name: "SurveyEdit",
    params: {
      node: route.params.node,
      surveyId: surveyId.value,
    },
    query: getContextParams(),
  }
}

function buildLearningPathQuery(extra = {}) {
  const query = new URLSearchParams()

  query.set("action", extra.action || "add_item")
  query.set("type", extra.type || "step")
  query.set("lp_id", String(learningPathId.value))

  for (const key of ["cid", "sid", "gid"]) {
    const value = getQueryValue(route.query[key])

    if (String(value) !== "") {
      query.set(key, String(value))
    }
  }

  query.set("isStudentView", "false")

  if (extra.surveyId) {
    query.set("survey_id", String(extra.surveyId))
  }

  return query.toString()
}

function buildLearningPathAddSurveyUrl() {
  return `/main/lp/lp_controller.php?${buildLearningPathQuery({ type: "survey", surveyId: surveyId.value })}`
}

function finishLearningPathSurvey() {
  if (!isLearningPathContext.value || questions.value.length === 0) {
    return
  }

  window.location.href = buildLearningPathAddSurveyUrl()
}

function translateOptions(items) {
  return items.map((item) => ({
    ...item,
    label: item.label ? t(item.label) : "",
  }))
}

function normalizeResponse(data) {
  survey.value = data.survey || {}
  questions.value = Array.isArray(data.questions) ? data.questions : []
  settings.value = data.settings || {}
  choices.value = data.choices || {}
  csrfToken.value = data.csrfToken || csrfToken.value
  canEdit.value = true === data.canEdit
  hasAnswers.value = true === data.hasAnswers
}

async function loadQuestions() {
  isLoading.value = true
  errorMessage.value = ""

  try {
    const data = await surveyService.getSurveyQuestions(getContextParams(), surveyId.value)
    normalizeResponse(data)
  } catch (error) {
    console.error("Error loading survey questions", error)
    errorMessage.value = error?.response?.data?.detail || t("Could not load survey questions")
  } finally {
    isLoading.value = false
  }
}

function startCreate(type = "open") {
  isEditing.value = false
  form.value = createEmptyForm()
  form.value.type = type
  applyTypeDefaults(form.value.type)
  if (settings.value.markQuestionAsRequired) {
    form.value.isRequired = true
  }
  successMessage.value = ""
  errorMessage.value = ""
  showForm()
}

function startEdit(question) {
  isEditing.value = true
  form.value = {
    questionId: question.iid,
    question: question.question || "",
    comment: question.comment || "",
    type: question.type || "open",
    display: question.display || "vertical",
    isRequired: true === question.isRequired,
    maxValue: Number(question.maxValue || 5),
    parentQuestionId: Number(question.parentQuestionId || 0),
    parentOptionId: Number(question.parentOptionId || 0),
    sort: Number(question.sort || Number.MAX_SAFE_INTEGER),
    options: normalizeOptionsForForm(question.options || []),
  }
  successMessage.value = ""
  errorMessage.value = ""
  showForm()
}

function showForm() {
  isFormVisible.value = true
  void focusFormStart()
}

async function focusFormStart() {
  await nextTick()

  const section = formSection.value
  if (!section) {
    return
  }

  section.scrollIntoView({ behavior: "smooth", block: "start" })

  window.setTimeout(() => {
    const questionTypeInput = section.querySelector("#survey_question_type")
    const questionTypeControl = questionTypeInput?.closest(".p-select") || questionTypeInput

    if (questionTypeControl && typeof questionTypeControl.focus === "function" && !isEditing.value) {
      questionTypeControl.focus({ preventScroll: true })
    }

    section.scrollIntoView({ behavior: "auto", block: "start" })
  }, 180)
}

function cancelForm() {
  isFormVisible.value = false
  isEditing.value = false
  form.value = createEmptyForm()
}

function normalizeOptionsForForm(options) {
  return options
    .filter((option) => !option.isOther)
    .map((option) => ({
      iid: Number(option.iid || 0),
      localId: `${Date.now()}_${Math.random()}`,
      text: option.text || "",
      value: Number(option.value || 0),
      isOther: false,
    }))
}

function createOption(text = "") {
  return {
    iid: 0,
    localId: `${Date.now()}_${Math.random()}`,
    text,
    value: 0,
    isOther: false,
  }
}

function applyTypeDefaults(type) {
  form.value.display = displayTypes.includes(type) ? "horizontal" : ""
  form.value.maxValue = type === "score" ? Number(form.value.maxValue || 5) : 0

  if (!allowsParent.value) {
    form.value.parentQuestionId = 0
    form.value.parentOptionId = 0
  }

  if (!optionTypes.includes(type) || type === "percentage") {
    form.value.options = []
    return
  }

  if (["yesno", "selectivedisplay"].includes(type)) {
    form.value.options = [createOption(t("Yes")), createOption(t("No"))]
    return
  }

  if (form.value.options.length === 0) {
    form.value.options = [createOption(t("Option") + " 1"), createOption(t("Option") + " 2")]
  }
}

function addOption() {
  form.value.options.push(createOption(t("Option") + " " + (form.value.options.length + 1)))
}

function removeOption(index) {
  form.value.options.splice(index, 1)
}

function moveOption(index, offset) {
  const target = index + offset
  if (target < 0 || target >= form.value.options.length) {
    return
  }

  const options = [...form.value.options]
  const item = options[index]
  options[index] = options[target]
  options[target] = item
  form.value.options = options
}

function stripHtml(value) {
  const element = document.createElement("div")
  element.innerHTML = value || ""

  return element.textContent || element.innerText || ""
}

function decodeHtml(value) {
  if (!value) {
    return ""
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

function validateForm() {
  if (!stripHtml(form.value.question).trim()) {
    errorMessage.value = t("The question text is required.")
    return false
  }

  if (form.value.type === "score" && Number(form.value.maxValue || 0) <= 0) {
    errorMessage.value = t("The maximum score is required.")
    return false
  }

  if (Number(form.value.parentQuestionId || 0) > 0 && Number(form.value.parentOptionId || 0) <= 0) {
    errorMessage.value = t("Please select a parent option.")
    return false
  }

  if (needsOptions.value) {
    const filledOptions = editableOptions.value.filter((option) => stripHtml(option.text).trim())
    const minimumOptions = form.value.type === "score" ? 1 : 2
    if (filledOptions.length < minimumOptions) {
      errorMessage.value = t("Please fill all answer options.")
      return false
    }
  }

  return true
}

function buildPayload() {
  const payload = {
    question: form.value.question,
    comment: form.value.comment,
    type: form.value.type,
    display: form.value.display,
    maxValue: Number(form.value.maxValue || 0),
    parentQuestionId: Number(form.value.parentQuestionId || 0),
    parentOptionId: Number(form.value.parentOptionId || 0),
    options: editableOptions.value.map((option) => ({
      iid: Number(option.iid || 0),
      text: option.text,
      value: Number(option.value || 0),
    })),
    csrfToken: csrfToken.value,
  }

  if (showsMandatoryField.value) {
    payload.isRequired = form.value.isRequired
  }

  return payload
}

async function submitQuestion() {
  errorMessage.value = ""
  successMessage.value = ""

  if (!validateForm()) {
    return
  }

  isSaving.value = true

  try {
    const data = await surveyService.saveSurveyQuestion(
      buildPayload(),
      getContextParams(),
      surveyId.value,
      isEditing.value ? form.value.questionId : null,
    )
    normalizeResponse(data)
    await loadQuestions()
    cancelForm()
    successMessage.value = isEditing.value ? t("QuestionUpdated") : t("The question has been added.")
  } catch (error) {
    console.error("Error saving survey question", error)
    errorMessage.value = error?.response?.data?.detail || t("Could not save survey question")
  } finally {
    isSaving.value = false
  }
}

function confirmDelete(question) {
  requireConfirmation({
    message: t("Are you sure you want to delete the question?"),
    accept: () => deleteQuestion(question),
  })
}

async function deleteQuestion(question) {
  try {
    await surveyService.deleteSurveyQuestion(getContextParams(), surveyId.value, question.iid, csrfToken.value)
    await loadQuestions()
    successMessage.value = t("Deleted")
  } catch (error) {
    console.error("Error deleting survey question", error)
    errorMessage.value = error?.response?.data?.detail || t("Could not delete survey question")
  }
}

async function moveQuestion(question, direction) {
  try {
    const data = await surveyService.moveSurveyQuestion(getContextParams(), surveyId.value, question.iid, direction, csrfToken.value)
    normalizeResponse(data)
    successMessage.value = t("The question has been moved")
  } catch (error) {
    console.error("Error moving survey question", error)
    errorMessage.value = error?.response?.data?.detail || t("Could not move survey question")
  }
}

async function copyQuestion(question) {
  try {
    const data = await surveyService.copySurveyQuestion(getContextParams(), surveyId.value, question.iid, csrfToken.value)
    normalizeResponse(data)
    successMessage.value = t("The question has been added.")
  } catch (error) {
    console.error("Error copying survey question", error)
    errorMessage.value = error?.response?.data?.detail || t("Could not copy survey question")
  }
}

function questionTypeIcon(type) {
  switch (type) {
    case "yesno":
    case "personality":
    case "selectivedisplay":
      return "thumbs-up-down"
    case "multiplechoice":
      return "format-list-bulleted"
    case "multipleresponse":
      return "format-list-bulleted-square"
    case "multiplechoiceother":
      return "format-list-bulleted-type"
    case "open":
      return "form-textarea"
    case "dropdown":
      return "form-dropdown"
    case "percentage":
      return "percent-box-outline"
    case "score":
      return "format-annotation-plus"
    case "comment":
      return "format-align-top"
    case "pagebreak":
      return "format-page-break"
    default:
      return "edit"
  }
}
watch(
  () => form.value.type,
  (newType, oldType) => {
    if (!isFormVisible.value || isEditing.value || newType === oldType) {
      return
    }
    applyTypeDefaults(newType)
  },
)

watch(
  () => form.value.parentQuestionId,
  (newValue, oldValue) => {
    if (!isFormVisible.value || newValue === oldValue) {
      return
    }

    form.value.parentOptionId = 0
  },
)

onMounted(loadQuestions)
</script>
