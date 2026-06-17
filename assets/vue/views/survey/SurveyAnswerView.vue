<template>
  <section class="space-y-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
      <div>
        <h1 class="text-3xl font-bold text-gray-90">
          {{ previewMode ? t("Survey preview") : t("Answer survey") }}
        </h1>
        <p
          v-if="survey.title"
          class="mt-1 text-sm text-gray-600"
        >
          {{ displayText(survey.title, t("Untitled")) }}
        </p>
      </div>

      <div class="flex flex-wrap items-center justify-end gap-2">
        <BaseButton
          v-if="showBackToSurveyList"
          :label="t('Back to survey list')"
          :route="buildListRoute()"
          icon="back"
          type="black"
        />
        <BaseButton
          v-if="previewMode"
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
      v-if="previewMode"
      class="rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-700"
    >
      {{ t("Preview mode does not save answers.") }}
    </div>

    <div
      v-if="isFinished"
      class="rounded-2xl border border-green-200 bg-green-50 p-6 text-green-800"
    >
      <h2 class="text-xl font-semibold">{{ t("Survey completed") }}</h2>
      <p class="mt-2">{{ displayText(survey.thanks || message || t("Thank you for answering the survey.")) }}</p>
    </div>

    <form
      v-else
      class="space-y-6"
      novalidate
      @submit.prevent="submitSurvey"
    >
      <div
        v-if="survey.intro && currentPageIndex === 0"
        class="rounded-2xl border border-gray-20 bg-white p-6 shadow-sm"
      >
        <h2 class="mb-2 text-lg font-semibold text-gray-90">{{ t("Introduction") }}</h2>
        <p class="whitespace-pre-line text-sm text-gray-700">{{ displayText(survey.intro) }}</p>
      </div>

      <div
        v-if="profileFields.length && currentPageIndex === 0"
        class="rounded-2xl border border-gray-20 bg-white p-6 shadow-sm"
      >
        <div class="mb-4 flex items-center gap-3">
          <BaseIcon
            icon="account"
            size="small"
          />
          <div>
            <h2 class="text-lg font-semibold text-gray-90">{{ t("Profile information") }}</h2>
            <p class="text-sm text-gray-600">{{ t("Please review the requested profile fields before answering the survey.") }}</p>
          </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
          <div
            v-for="field in profileFields"
            :key="field.key"
            class="space-y-1"
          >
            <label
              :for="`profile_${field.key}`"
              class="text-sm font-medium text-gray-800"
            >
              {{ t(field.label) }}
              <span
                v-if="field.required"
                class="text-red-600"
              >*</span>
            </label>

            <select
              v-if="field.type === 'select'"
              :id="`profile_${field.key}`"
              v-model="profileValues[field.key]"
              :disabled="!canInteract || field.readOnly"
              :name="`profile_${field.key}`"
              class="w-full rounded-lg border border-gray-30 px-3 py-2 text-sm"
            >
              <option value="">{{ t("Select an option") }}</option>
              <option
                v-for="option in field.options"
                :key="option.value"
                :value="option.value"
              >
                {{ option.label }}
              </option>
            </select>

            <div
              v-else-if="field.type === 'multiselect'"
              class="space-y-2 rounded-lg border border-gray-20 p-3"
            >
              <label
                v-for="option in field.options"
                :key="option.value"
                class="flex items-center gap-2"
              >
                <input
                  :checked="isProfileMultiSelected(field, option.value)"
                  :disabled="!canInteract || field.readOnly"
                  :name="`profile_${field.key}[]`"
                  :value="option.value"
                  type="checkbox"
                  @change="toggleProfileMultiValue(field, option.value, $event.target.checked)"
                />
                <span>{{ option.label }}</span>
              </label>
            </div>

            <textarea
              v-else-if="field.type === 'textarea'"
              :id="`profile_${field.key}`"
              v-model="profileValues[field.key]"
              :disabled="!canInteract || field.readOnly"
              :name="`profile_${field.key}`"
              class="min-h-24 w-full rounded-lg border border-gray-30 px-3 py-2 text-sm"
            />

            <input
              v-else
              :id="`profile_${field.key}`"
              v-model="profileValues[field.key]"
              :disabled="!canInteract || field.readOnly"
              :name="`profile_${field.key}`"
              :type="field.inputType || 'text'"
              class="w-full rounded-lg border border-gray-30 px-3 py-2 text-sm"
            />

            <p
              v-if="field.readOnly"
              class="text-xs text-gray-500"
            >
              {{ t("This profile field is read-only.") }}
            </p>
            <p
              v-if="field.helpText"
              class="text-xs text-gray-500"
            >
              {{ field.helpText }}
            </p>
          </div>
        </div>
      </div>

      <div
        v-if="isAnswered && !canSubmit"
        class="rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-700"
      >
        {{ t("You already filled this survey.") }}
      </div>

      <div class="flex flex-col gap-2 rounded-xl border border-gray-20 bg-white p-4 text-sm text-gray-700 md:flex-row md:items-center md:justify-between">
        <span>{{ t("Page") }} {{ currentPageIndex + 1 }} / {{ totalPages }}</span>
        <span v-if="survey.anonymous">{{ t("Anonymous") }}</span>
      </div>

      <div class="space-y-4">
        <article
          v-for="(question, index) in currentVisibleQuestions"
          :key="question.iid"
          class="rounded-2xl border border-gray-20 bg-white p-6 shadow-sm"
        >
          <div class="mb-4 flex items-start gap-3">
            <BaseIcon
              :icon="questionTypeIcon(question.type)"
              size="small"
            />
            <div class="min-w-0 flex-1">
              <h2 class="text-lg font-semibold text-gray-90">
                <span v-if="survey.displayQuestionNumber">{{ questionNumber(question, index) }}. </span>
                {{ displayText(question.question, t("Untitled")) }}
                <span
                  v-if="question.isRequired"
                  class="text-red-600"
                >*</span>
              </h2>
              <p
                v-if="displayText(question.comment)"
                class="mt-1 text-sm text-gray-600"
              >
                {{ displayText(question.comment) }}
              </p>
            </div>
          </div>

          <div v-if="['yesno', 'multiplechoice', 'selectivedisplay'].includes(question.type)">
            <div :class="question.display === 'horizontal' ? 'flex flex-wrap gap-4' : 'space-y-2'">
              <label
                v-for="option in question.options"
                :key="option.iid"
                class="flex cursor-pointer items-center gap-2 rounded-lg border border-gray-20 px-3 py-2 hover:bg-gray-10"
              >
                <input
                  v-model="answers[String(question.iid)]"
                  :disabled="!canInteract"
                  :name="`question_${question.iid}`"
                  :value="option.iid"
                  type="radio"
                />
                <span>{{ displayText(option.text, option.label) }}</span>
              </label>
            </div>
          </div>

          <div v-else-if="question.type === 'multiplechoiceother'">
            <div :class="question.display === 'horizontal' ? 'flex flex-wrap gap-4' : 'space-y-2'">
              <label
                v-for="option in question.options"
                :key="option.iid"
                class="flex cursor-pointer items-center gap-2 rounded-lg border border-gray-20 px-3 py-2 hover:bg-gray-10"
              >
                <input
                  v-model="answers[String(question.iid)]"
                  :disabled="!canInteract"
                  :name="`question_${question.iid}`"
                  :value="option.iid"
                  type="radio"
                />
                <span>{{ option.isOther ? t("Other") : displayText(option.text, option.label) }}</span>
              </label>
            </div>
            <input
              v-if="isOtherSelected(question)"
              v-model="otherAnswers[String(question.iid)]"
              :disabled="!canInteract"
              :name="`other_question_${question.iid}`"
              :placeholder="t('Please specify')"
              class="mt-3 w-full rounded-lg border border-gray-30 px-3 py-2 text-sm"
              type="text"
            />
          </div>

          <div v-else-if="question.type === 'multipleresponse'">
            <div :class="question.display === 'horizontal' ? 'flex flex-wrap gap-4' : 'space-y-2'">
              <label
                v-for="option in question.options"
                :key="option.iid"
                class="flex cursor-pointer items-center gap-2 rounded-lg border border-gray-20 px-3 py-2 hover:bg-gray-10"
              >
                <input
                  :checked="isChecked(question, option)"
                  :disabled="!canInteract"
                  :name="`question_${question.iid}[]`"
                  :value="option.iid"
                  type="checkbox"
                  @change="toggleMultipleAnswer(question, option, $event.target.checked)"
                />
                <span>{{ displayText(option.text, option.label) }}</span>
              </label>
            </div>
          </div>

          <div v-else-if="question.type === 'dropdown' || question.type === 'percentage'">
            <select
              v-model="answers[String(question.iid)]"
              :disabled="!canInteract"
              :name="`question_${question.iid}`"
              class="w-full rounded-lg border border-gray-30 px-3 py-2 text-sm md:w-96"
            >
              <option value="0">{{ t("Select an option") }}</option>
              <option
                v-for="option in question.options"
                :key="option.iid"
                :value="option.iid"
              >
                {{ displayText(option.text, option.label) }}
              </option>
            </select>
          </div>

          <div v-else-if="question.type === 'score'">
            <div class="space-y-3">
              <div
                v-for="option in question.options"
                :key="option.iid"
                class="grid gap-2 rounded-lg border border-gray-20 p-3 md:grid-cols-[1fr_160px] md:items-center"
              >
                <span>{{ displayText(option.text, option.label) }}</span>
                <select
                  :disabled="!canInteract"
                  :name="`question_${question.iid}_${option.iid}`"
                  :value="scoreValue(question, option)"
                  class="rounded-lg border border-gray-30 px-3 py-2 text-sm"
                  @change="setScoreValue(question, option, $event.target.value)"
                >
                  <option value="">--</option>
                  <option
                    v-for="score in scoreOptions(question)"
                    :key="score"
                    :value="score"
                  >
                    {{ score }}
                  </option>
                </select>
              </div>
            </div>
          </div>

          <textarea
            v-else-if="question.type === 'open' || question.type === 'comment'"
            v-model="answers[String(question.iid)]"
            :disabled="!canInteract"
            :name="`question_${question.iid}`"
            class="min-h-32 w-full rounded-lg border border-gray-30 px-3 py-2 text-sm"
          />

          <p
            v-else
            class="rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-700"
          >
            {{ t("This question type is not supported in the modern survey view.") }}
          </p>
        </article>
      </div>

      <div class="flex flex-wrap justify-between gap-2 border-t border-gray-20 pt-4">
        <BaseButton
          v-if="canGoBack"
          :label="t('Previous')"
          icon="back"
          type="black"
          @click="goToPreviousPage"
        />
        <span v-else />

        <div class="flex flex-wrap gap-2">
          <BaseButton
            v-if="canGoNext"
            :label="t('Next')"
            icon="next"
            type="primary"
            @click="goToNextPage"
          />
          <BaseButton
            v-else
            :disabled="previewMode || !canSubmit"
            :is-loading="isSaving"
            :label="previewMode ? t('Preview only') : t('Submit answers')"
            icon="save"
            is-submit
            type="success"
          />
        </div>
      </div>
    </form>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import surveyService from "../../services/surveyService"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()

const survey = ref({})
const questions = ref([])
const pages = ref([[]])
const answers = ref({})
const otherAnswers = ref({})
const profileFields = ref([])
const profileValues = ref({})
const csrfToken = ref("")
const settings = ref({})
const canSubmit = ref(false)
const isAnswered = ref(false)
const isFinished = ref(false)
const message = ref("")
const isLoading = ref(false)
const isSaving = ref(false)
const errorMessage = ref("")
const successMessage = ref("")
const currentPageIndex = ref(0)

const surveyId = computed(() => Number(route.params.surveyId || 0))
const previewMode = computed(() => route.name === "SurveyPreview" || route.query.preview === "1")
const isLearningPathContext = computed(() => {
  const origin = String(getQueryValue(route.query.origin) || "")
  const returnToLp = String(getQueryValue(route.query.returnToLp) || "")

  return (
    origin === "learnpath" ||
    returnToLp === "1" ||
    Boolean(getQueryValue(route.query.lp_id)) ||
    Boolean(getQueryValue(route.query.lpItemId || route.query.lp_item_id))
  )
})
const isPublicAnswerContext = computed(() => {
  return (
    Boolean(getQueryValue(route.query.invitationCode || route.query.invitationcode)) ||
    Boolean(getQueryValue(route.query.publicCid)) ||
    Boolean(getQueryValue(route.query.publicSid)) ||
    Boolean(getQueryValue(route.query.publicGid))
  )
})
const showBackToSurveyList = computed(() => {
  return !isLearningPathContext.value && !isPublicAnswerContext.value
})
const canInteract = computed(() => previewMode.value || canSubmit.value)
const totalPages = computed(() => Math.max(1, pages.value.length))
const currentQuestionIds = computed(() => pages.value[currentPageIndex.value] || [])
const currentQuestions = computed(() => questions.value.filter((question) => currentQuestionIds.value.includes(Number(question.iid))))
const currentVisibleQuestions = computed(() => currentQuestions.value.filter((question) => isQuestionVisible(question)))
const canGoNext = computed(() => currentPageIndex.value < totalPages.value - 1)
const canGoBack = computed(() => settings.value.backwardsEnabled && currentPageIndex.value > 0)

function getQueryValue(value) {
  return Array.isArray(value) ? value[0] : value
}

function getContextParams() {
  return {
    cid: getQueryValue(route.query.cid),
    sid: getQueryValue(route.query.sid),
    gid: getQueryValue(route.query.gid),
    publicCid: getQueryValue(route.query.publicCid),
    publicSid: getQueryValue(route.query.publicSid),
    publicGid: getQueryValue(route.query.publicGid),
    invitationCode: getQueryValue(route.query.invitationCode || route.query.invitationcode),
    lpItemId: getQueryValue(route.query.lpItemId || route.query.lp_item_id),
    lp_id: getQueryValue(route.query.lp_id),
    origin: getQueryValue(route.query.origin),
    type: getQueryValue(route.query.type),
    returnToLp: getQueryValue(route.query.returnToLp),
    embedded: getQueryValue(route.query.embedded),
    isStudentView: getQueryValue(route.query.isStudentView),
    preview: previewMode.value ? 1 : undefined,
  }
}

function buildListRoute() {
  return {
    name: "SurveyList",
    params: { node: route.params.node },
    query: {
      ...getContextParams(),
      invitationCode: undefined,
      preview: undefined,
    },
  }
}

function buildConfigureRoute() {
  return {
    name: "SurveyEdit",
    params: {
      node: route.params.node,
      surveyId: surveyId.value,
    },
    query: {
      ...getContextParams(),
      invitationCode: undefined,
      preview: undefined,
    },
  }
}

function normalizeResponse(data) {
  survey.value = data.survey || {}
  questions.value = Array.isArray(data.questions) ? data.questions : []
  pages.value = Array.isArray(data.pages) && data.pages.length ? data.pages : [[]]
  csrfToken.value = data.csrfToken || csrfToken.value
  settings.value = data.settings || {}
  canSubmit.value = true === data.canSubmit
  isAnswered.value = true === data.isAnswered
  isFinished.value = true === data.isFinished
  message.value = data.message || ""
  answers.value = data.answers || {}
  otherAnswers.value = extractOtherAnswers(data.answers || {})
  profileFields.value = Array.isArray(data.profileFields) ? data.profileFields : []
  profileValues.value = buildProfileValues(profileFields.value)
}


function buildProfileValues(fields) {
  const values = {}
  fields.forEach((field) => {
    values[field.key] = field.value ?? (field.type === "multiselect" ? [] : "")
  })

  return values
}

function isProfileMultiSelected(field, value) {
  const values = Array.isArray(profileValues.value[field.key]) ? profileValues.value[field.key].map(String) : []

  return values.includes(String(value))
}

function toggleProfileMultiValue(field, value, checked) {
  const currentValues = Array.isArray(profileValues.value[field.key]) ? [...profileValues.value[field.key]].map(String) : []
  const stringValue = String(value)

  if (checked && !currentValues.includes(stringValue)) {
    currentValues.push(stringValue)
  }

  if (!checked) {
    const index = currentValues.indexOf(stringValue)
    if (index >= 0) {
      currentValues.splice(index, 1)
    }
  }

  profileValues.value[field.key] = currentValues
}

function extractOtherAnswers(values) {
  const items = {}
  Object.entries(values).forEach(([key, value]) => {
    if (key.startsWith("other_")) {
      items[key.replace("other_", "")] = value
    }
  })

  return items
}

async function loadSurvey() {
  isLoading.value = true
  errorMessage.value = ""

  try {
    const data = await surveyService.getSurveyAnswer(getContextParams(), surveyId.value)
    normalizeResponse(data)
  } catch (error) {
    const backendMessage = error?.response?.data?.detail || error?.response?.data?.error || ""

    if (String(backendMessage).includes("Meeting poll answers must be opened in the meeting view")) {
      await router.replace({
        name: "SurveyMeeting",
        params: {
          node: route.params.node,
          surveyId: surveyId.value,
        },
        query: {
          ...getContextParams(),
          preview: previewMode.value ? 1 : undefined,
        },
      })

      return
    }

    console.error("Error loading survey answer form", error)
    errorMessage.value = backendMessage || t("Could not load survey")
  } finally {
    isLoading.value = false
  }
}

function displayText(value, fallback = "") {
  if (!value) {
    return fallback
  }

  const textarea = document.createElement("textarea")
  textarea.innerHTML = String(value).replace(/<[^>]*>/g, " ")

  return textarea.value.replace(/\s+/g, " ").trim() || fallback
}

function questionNumber(question, index) {
  const globalIndex = questions.value.findIndex((item) => Number(item.iid) === Number(question.iid))

  return globalIndex >= 0 ? globalIndex + 1 : index + 1
}

function isQuestionVisible(question) {
  const parentQuestionId = Number(question.parentQuestionId || 0)
  const parentOptionId = Number(question.parentOptionId || 0)
  if (parentQuestionId <= 0 || parentOptionId <= 0) {
    return true
  }

  const parentAnswer = answers.value[String(parentQuestionId)]
  if (Array.isArray(parentAnswer)) {
    return parentAnswer.map(Number).includes(parentOptionId)
  }

  if (parentAnswer && typeof parentAnswer === "object") {
    return Object.prototype.hasOwnProperty.call(parentAnswer, String(parentOptionId))
  }

  return Number(parentAnswer || 0) === parentOptionId
}

function isChecked(question, option) {
  const value = answers.value[String(question.iid)]

  return Array.isArray(value) && value.map(Number).includes(Number(option.iid))
}

function toggleMultipleAnswer(question, option, checked) {
  const questionId = String(question.iid)
  const values = Array.isArray(answers.value[questionId]) ? [...answers.value[questionId]].map(Number) : []
  const optionId = Number(option.iid)

  if (checked && !values.includes(optionId)) {
    values.push(optionId)
  }

  if (!checked) {
    const index = values.indexOf(optionId)
    if (index >= 0) {
      values.splice(index, 1)
    }
  }

  answers.value[questionId] = values
}

function scoreValue(question, option) {
  const questionId = String(question.iid)
  const values = answers.value[questionId] || {}

  return values[String(option.iid)] || ""
}

function setScoreValue(question, option, value) {
  const questionId = String(question.iid)
  const values = { ...(answers.value[questionId] || {}) }

  if (String(value) === "") {
    delete values[String(option.iid)]
  } else {
    values[String(option.iid)] = value
  }

  answers.value[questionId] = values
}

function scoreOptions(question) {
  const maxValue = Math.max(1, Number(question.maxValue || 5))

  return Array.from({ length: maxValue }, (_, index) => index + 1)
}

function isOtherSelected(question) {
  const selectedOptionId = Number(answers.value[String(question.iid)] || 0)
  const selectedOption = question.options.find((option) => Number(option.iid) === selectedOptionId)

  return true === selectedOption?.isOther
}

function isMandatoryQuestionType(type) {
  return ["yesno", "multiplechoice"].includes(type)
}

function validateProfileFields() {
  if (!profileFields.value.length) {
    return true
  }

  for (const field of profileFields.value) {
    if (!field.required) {
      continue
    }

    const value = profileValues.value[field.key]
    const hasValue = Array.isArray(value) ? value.length > 0 : String(value ?? "").trim() !== ""
    if (!hasValue) {
      errorMessage.value = t("Please complete the required profile fields.")

      return false
    }
  }

  return true
}

function validateCurrentPage() {
  if (previewMode.value) {
    return true
  }

  if (currentPageIndex.value === 0 && !validateProfileFields()) {
    return false
  }

  for (const question of currentVisibleQuestions.value) {
    if (!isMandatoryQuestionType(question.type) || !question.isRequired) {
      continue
    }

    const questionId = String(question.iid)
    const value = answers.value[questionId]
    if (question.type === "multiplechoiceother" && String(otherAnswers.value[questionId] || "").trim() !== "") {
      continue
    }

    if (!hasAnswerValue(question.type, value)) {
      errorMessage.value = t("Please answer all mandatory questions.")
      return false
    }
  }

  return true
}

function hasAnswerValue(type, value) {
  if (type === "pagebreak") {
    return true
  }

  if (type === "open" || type === "comment") {
    return value !== undefined && value !== null && String(value).trim() !== ""
  }

  if (type === "multipleresponse") {
    return Array.isArray(value) && value.length > 0
  }

  if (type === "score") {
    return value && typeof value === "object" && Object.values(value).some((score) => Number(score || 0) > 0)
  }

  return value !== undefined && value !== null && String(value).trim() !== "" && Number(value || 0) !== 0
}

function goToNextPage() {
  errorMessage.value = ""
  if (!validateCurrentPage()) {
    return
  }

  currentPageIndex.value += 1
  window.scrollTo({ top: 0, behavior: "smooth" })
}

function goToPreviousPage() {
  if (!canGoBack.value) {
    return
  }

  currentPageIndex.value -= 1
  window.scrollTo({ top: 0, behavior: "smooth" })
}

async function submitSurvey() {
  errorMessage.value = ""
  successMessage.value = ""

  if (previewMode.value || !canSubmit.value) {
    return
  }

  if (!validateAllPages()) {
    return
  }

  isSaving.value = true

  try {
    const data = await surveyService.submitSurveyAnswer(
      {
        answers: answers.value,
        otherAnswers: otherAnswers.value,
        profileValues: profileValues.value,
        csrfToken: csrfToken.value,
      },
      getContextParams(),
      surveyId.value,
    )
    normalizeResponse(data)
    successMessage.value = t("Survey completed")
  } catch (error) {
    console.error("Error submitting survey answers", error)
    errorMessage.value = error?.response?.data?.detail || t("Could not save survey answers")
  } finally {
    isSaving.value = false
  }
}

function validateAllPages() {
  for (let pageIndex = 0; pageIndex < pages.value.length; pageIndex++) {
    currentPageIndex.value = pageIndex
    if (!validateCurrentPage()) {
      return false
    }
  }

  return true
}

function questionTypeIcon(type) {
  switch (type) {
    case "yesno":
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
    default:
      return "edit"
  }
}

onMounted(loadSurvey)
</script>
