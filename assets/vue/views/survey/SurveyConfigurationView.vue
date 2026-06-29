<template>
  <section class="space-y-6">
    <div class="rounded-2xl border border-gray-20 bg-white p-6 shadow-sm">
      <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
        <div class="flex items-start gap-4">
          <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl bg-gray-15">
            <BaseIcon
              icon="multiple-marked"
              size="big"
            />
          </div>
          <div>
            <p class="text-sm font-semibold uppercase tracking-wide text-primary">{{ t("Surveys") }}</p>
            <h1 class="mt-1 text-2xl font-bold text-gray-90">
              {{ isEditMode ? t("Edit survey") : t("Create survey") }}
            </h1>
            <p class="mt-2 max-w-3xl text-sm text-gray-600">
              {{ t("Configure the main survey information before managing questions and invitations.") }}
            </p>
          </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
          <BaseButton
            v-if="!isLearningPathContext"
            :label="t('Back to survey list')"
            :route="buildListRoute()"
            icon="back"
            type="black"
          />
          <BaseButton
            v-if="isEditMode"
            :label="t('Edit questions')"
            :route="buildQuestionsRoute()"
            icon="edit"
            type="secondary"
          />
        </div>
      </div>
    </div>

    <div
      v-if="errorMessage"
      ref="errorAlertRef"
      class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-700"
      role="alert"
      tabindex="-1"
    >
      {{ errorMessage }}
    </div>

    <div
      v-if="successMessage"
      ref="successAlertRef"
      class="rounded-xl border border-green-200 bg-green-50 p-4 text-sm font-semibold text-green-700"
      role="status"
      tabindex="-1"
    >
      {{ successMessage }}
    </div>

    <div
      v-if="isLoading"
      class="rounded-2xl border border-gray-20 bg-white p-6 shadow-sm"
    >
      <div class="flex items-center gap-3 text-sm text-gray-600">
        <BaseIcon
          icon="refresh"
          size="small"
        />
        {{ t("Loading") }}
      </div>
    </div>

    <form
      v-else
      class="space-y-6"
      novalidate
      @submit.prevent="submitForm"
    >
      <div class="rounded-2xl border border-gray-20 bg-white p-6 shadow-sm">
        <div class="mb-6 flex items-center gap-3">
          <BaseIcon
            icon="information"
            size="small"
          />
          <h2 class="text-lg font-semibold text-gray-90">{{ t("Basic information") }}</h2>
        </div>

        <div class="grid gap-6 md:grid-cols-2">
          <BaseInputText
            id="survey_code"
            v-model="form.code"
            :disabled="isEditMode"
            :form-submitted="formSubmitted"
            :help-text="isEditMode ? t('The survey code cannot be changed after creation.') : ''"
            :is-invalid="isCodeInvalid"
            :label="isEditMode ? t('Code') : requiredLabel(t('Code'))"
            :required="!isEditMode"
            :error-text="t('Required field')"
            aria-required="true"
            maxlength="40"
            name="survey_code"
          />
        </div>

        <div class="mt-6 grid gap-6">
          <div>
            <BaseTinyEditor
              editor-id="survey_title"
              v-model="form.title"
              :editor-config="smallEditorConfig"
              :full-page="false"
              :title="requiredLabel(t('Survey title'))"
            />
            <p
              v-if="isTitleInvalid"
              class="mt-1 text-sm text-danger"
            >
              {{ t("Required field") }}
            </p>
          </div>

          <BaseTinyEditor
            editor-id="survey_subtitle"
            v-model="form.subtitle"
            :editor-config="smallEditorConfig"
            :full-page="false"
            :title="t('Survey subtitle')"
          />
        </div>
      </div>

      <div class="rounded-2xl border border-gray-20 bg-white p-6 shadow-sm">
        <div class="mb-6 flex items-center gap-3">
          <BaseIcon
            icon="agenda-event"
            size="small"
          />
          <h2 class="text-lg font-semibold text-gray-90">{{ t("Availability") }}</h2>
        </div>

        <div class="grid gap-6 md:grid-cols-2">
          <BaseCalendar
            id="available_from"
            v-model="form.availableFrom"
            :error-text="t('Invalid date')"
            :is-invalid="isAvailableFromInvalid"
            :label="requiredLabel(t('Start Date'))"
            :show-time="true"
          />

          <BaseCalendar
            id="available_until"
            v-model="form.availableUntil"
            :error-text="t('Invalid date')"
            :is-invalid="isAvailableUntilInvalid"
            :label="requiredLabel(t('End Date'))"
            :show-time="true"
          />
        </div>
      </div>

      <div class="rounded-2xl border border-gray-20 bg-white p-6 shadow-sm">
        <div class="mb-6 flex items-center gap-3">
          <BaseIcon
            icon="settings"
            size="small"
          />
          <h2 class="text-lg font-semibold text-gray-90">{{ t("Survey behavior") }}</h2>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
          <BaseCheckbox
            id="anonymous"
            v-model="form.anonymous"
            :label="t('Anonymous')"
            name="anonymous"
          />
          <BaseCheckbox
            id="one_question_per_page"
            v-model="form.oneQuestionPerPage"
            :label="t('One question per page')"
            name="one_question_per_page"
          />
          <BaseCheckbox
            id="shuffle"
            v-model="form.shuffle"
            :label="t('Enable shuffle mode')"
            name="shuffle"
          />
          <BaseCheckbox
            id="display_question_number"
            v-model="form.displayQuestionNumber"
            :label="t('Show question number')"
            name="display_question_number"
          />
        </div>

        <div class="mt-6 grid gap-6 md:grid-cols-2">
          <BaseSelect
            id="visible_results"
            v-model="form.visibleResults"
            :disabled="settings.hideReportingButton"
            :label="t('Results visibility')"
            :message-text="settings.hideReportingButton ? t('Feature disabled by administrator') : null"
            :options="visibleResultOptions"
            name="visible_results"
          />

          <BaseInputNumber
            id="duration"
            v-model="durationValue"
            :help-text="t('Use 0 for no time limit.')"
            :label="t('Duration')"
            :min="0"
            name="duration"
          />
        </div>
      </div>

      <div class="rounded-2xl border border-gray-20 bg-white p-6 shadow-sm">
        <div class="mb-6 flex items-center gap-3">
          <BaseIcon
            icon="comment"
            size="small"
          />
          <h2 class="text-lg font-semibold text-gray-90">{{ t("Survey messages") }}</h2>
        </div>

        <div class="grid gap-6">
          <BaseTinyEditor
            editor-id="survey_introduction"
            v-model="form.introduction"
            :editor-config="mediumEditorConfig"
            :full-page="false"
            :title="t('Survey introduction')"
          />

          <BaseTinyEditor
            editor-id="survey_thanks"
            v-model="form.thanks"
            :editor-config="mediumEditorConfig"
            :full-page="false"
            :title="t('Final thanks')"
          />
        </div>
      </div>

      <BaseAdvancedSettingsButton v-model="showAdvancedSettings">
        <div class="grid gap-6 md:grid-cols-2">
          <BaseSelect
            v-if="languageOptions.length > 2"
            id="resource_language"
            v-model="form.resourceLanguage"
            :allow-clear="true"
            :label="t('Language')"
            :options="languageOptions"
            name="language"
          />

          <BaseSelect
            v-if="!isEditMode"
            id="parent_id"
            v-model="form.parentId"
            :label="t('Parent Survey')"
            :options="parentSurveyOptions"
            name="parent_id"
          />

          <div
            v-if="gradebookCategoryOptions.length > 0"
            class="rounded-xl border border-gray-20 bg-white p-4 md:col-span-2"
          >
            <div class="mb-4 flex items-center gap-3">
              <BaseIcon
                icon="gradebook"
                size="small"
              />
              <h2 class="text-lg font-semibold text-gray-90">{{ t("Gradebook") }}</h2>
            </div>

            <div class="grid gap-6 md:grid-cols-3">
              <BaseCheckbox
                id="survey_qualify_gradebook"
                v-model="form.gradebookEnabled"
                :label="t('Grade in the assessment tool')"
                name="survey_qualify_gradebook"
              />

              <BaseSelect
                id="category_id"
                v-model="form.gradebookCategoryId"
                :disabled="!form.gradebookEnabled"
                :is-invalid="isGradebookCategoryInvalid"
                :label="t('Select assessment')"
                :options="gradebookCategoryOptions"
                name="category_id"
              />

              <BaseInputNumber
                id="survey_weight"
                v-model="form.gradebookWeight"
                :disabled="!form.gradebookEnabled"
                :label="t('Weight in Report')"
                :min="0"
                :step="0.1"
                name="survey_weight"
              />
            </div>
          </div>

          <div
            v-if="isEditMode && !form.anonymous && settings.showProfileFormSupported"
            class="rounded-xl border border-gray-20 bg-white p-4 md:col-span-2"
          >
            <BaseCheckbox
              id="show_form_profile"
              v-model="form.showFormProfile"
              :label="t('Show profile form')"
              name="show_form_profile"
            />

            <div
              v-if="form.showFormProfile"
              class="mt-4 space-y-3"
            >
              <p class="text-sm text-gray-600">
                {{ t("Profile fields selected here will be shown before the survey questions.") }}
              </p>

              <div
                v-if="profileFieldOptions.length"
                class="grid gap-3 md:grid-cols-2"
              >
                <BaseCheckbox
                  v-for="field in profileFieldOptions"
                  :id="`profile_field_${field.value}`"
                  :key="field.value"
                  v-model="form.selectedProfileFields"
                  :label="t(field.label)"
                  :name="`profile_field_${field.value}`"
                  :value="field.value"
                />
              </div>

              <div
                v-else
                class="rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-700"
              >
                {{ t("No profile fields are currently available.") }}
              </div>
            </div>
          </div>

          <div
            v-if="isEditMode && form.anonymous"
            class="rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-700 md:col-span-2"
          >
            {{ t("Profile field selection is available only for non-anonymous surveys.") }}
          </div>

        </div>
      </BaseAdvancedSettingsButton>

      <div
        class="flex flex-col-reverse gap-3 rounded-2xl border border-gray-20 bg-white p-6 shadow-sm md:flex-row md:items-center md:justify-end"
      >
        <BaseButton
          v-if="isLearningPathContext"
          :label="t('Cancel')"
          icon="close"
          type="black"
          @click="goToLearningPathAddItem"
        />
        <BaseButton
          v-else
          :label="t('Cancel')"
          :route="buildListRoute()"
          icon="close"
          type="black"
        />
        <BaseButton
          :is-loading="isSaving"
          :label="isEditMode ? t('Edit survey') : t('Create survey')"
          icon="save"
          is-submit
          type="success"
        />
      </div>
    </form>
  </section>
</template>

<script setup>
import { computed, nextTick, onMounted, ref } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import BaseAdvancedSettingsButton from "../../components/basecomponents/BaseAdvancedSettingsButton.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseCalendar from "../../components/basecomponents/BaseCalendar.vue"
import BaseCheckbox from "../../components/basecomponents/BaseCheckbox.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import BaseInputNumber from "../../components/basecomponents/BaseInputNumber.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseTinyEditor from "../../components/basecomponents/BaseTinyEditor.vue"
import surveyService from "../../services/surveyService"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()

const isLoading = ref(false)
const isSaving = ref(false)
const formSubmitted = ref(false)
const errorMessage = ref("")
const successMessage = ref("")
const errorAlertRef = ref(null)
const successAlertRef = ref(null)
const firstInvalidFieldId = ref("")
const questionUrl = ref("")
const settings = ref({})
const options = ref({})
const csrfToken = ref("")
const showAdvancedSettings = ref(false)

const form = ref(createEmptyForm())

const isEditMode = computed(() => Number(route.params.surveyId || 0) > 0)
const surveyId = computed(() => Number(route.params.surveyId || 0))
const isCodeInvalid = computed(() => formSubmitted.value && !isEditMode.value && !form.value.code.trim())
const isTitleInvalid = computed(() => formSubmitted.value && !stripHtml(form.value.title).trim())
const isAvailableFromInvalid = computed(() => formSubmitted.value && !form.value.availableFrom)
const isAvailableUntilInvalid = computed(() => formSubmitted.value && !form.value.availableUntil)
const isGradebookCategoryInvalid = computed(
  () => formSubmitted.value && form.value.gradebookEnabled && !form.value.gradebookCategoryId,
)

const visibleResultOptions = computed(() => translateOptions(options.value.visibleResults || []))
const languageOptions = computed(() => translateOptions(options.value.languages || []))
const parentSurveyOptions = computed(() => normalizeOptions(options.value.parentSurveys || []))
const gradebookCategoryOptions = computed(() => normalizeOptions(options.value.gradebookCategories || []))
const profileFieldOptions = computed(() => translateOptions(options.value.profileFields || []))

const durationValue = computed({
  get() {
    return Number(form.value.duration || 0)
  },
  set(value) {
    form.value.duration = Number(value || 0)
  },
})

const smallEditorConfig = {
  height: 160,
  menubar: false,
}

const mediumEditorConfig = {
  height: 220,
  menubar: false,
}

function createEmptyForm() {
  return {
    surveyId: null,
    code: "",
    title: "",
    subtitle: "",
    surveyLanguage: "",
    resourceLanguage: "",
    availableFrom: null,
    availableUntil: null,
    anonymous: false,
    visibleResults: 0,
    introduction: "",
    thanks: "",
    surveyType: 0,
    parentId: 0,
    oneQuestionPerPage: false,
    shuffle: false,
    displayQuestionNumber: true,
    showFormProfile: false,
    selectedProfileFields: [],
    duration: 0,
    gradebookEnabled: false,
    gradebookCategoryId: null,
    gradebookWeight: 0,
  }
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

const learningPathId = computed(() => Number(getQueryValue(route.query.lp_id) || 0))

const isLearningPathContext = computed(() => {
  return getQueryValue(route.query.origin) === "learnpath" && learningPathId.value > 0
})

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

function buildLearningPathAddItemUrl() {
  return `/main/lp/lp_controller.php?${buildLearningPathQuery()}`
}

function buildLearningPathQuestionsRoute(surveyId) {
  return {
    name: "SurveyQuestions",
    params: {
      node: route.params.node,
      surveyId,
    },
    query: getContextParams({ lpItemId: undefined }),
  }
}

function goToLearningPathAddItem() {
  window.location.href = buildLearningPathAddItemUrl()
}

function buildQuestionsRoute() {
  return {
    name: "SurveyQuestions",
    params: {
      node: route.params.node,
      surveyId: surveyId.value,
    },
    query: getContextParams(),
  }
}

function buildListRoute() {
  return {
    name: "SurveyList",
    params: { node: route.params.node },
    query: getContextParams(),
  }
}

function translateOptions(items) {
  return items.map((item) => ({
    ...item,
    label: item.label ? t(item.label) : "",
  }))
}

function normalizeOptions(items) {
  return items.map((item) => ({
    ...item,
    label: stripHtml(item.label || "").trim(),
  }))
}

function requiredLabel(label) {
  return `${label} *`
}

function toDate(value) {
  if (!value) {
    return null
  }

  const date = new Date(value)

  return Number.isNaN(date.getTime()) ? null : date
}

function toPayloadDate(value) {
  if (!value) {
    return null
  }

  if (value instanceof Date) {
    return value.toISOString()
  }

  const date = new Date(value)

  return Number.isNaN(date.getTime()) ? null : date.toISOString()
}

function stripHtml(value) {
  const element = document.createElement("div")
  element.innerHTML = value || ""

  return element.textContent || element.innerText || ""
}

function normalizeForm(data) {
  form.value = {
    surveyId: data.surveyId || null,
    code: data.code || "",
    title: data.title || "",
    subtitle: data.subtitle || "",
    surveyLanguage: data.surveyLanguage || "",
    resourceLanguage: data.resourceLanguage || "",
    availableFrom: toDate(data.availableFrom),
    availableUntil: toDate(data.availableUntil),
    anonymous: true === data.anonymous,
    visibleResults: Number(data.visibleResults || 0),
    introduction: data.introduction || "",
    thanks: data.thanks || "",
    surveyType: Number(data.surveyType || 0),
    parentId: Number(data.parentId || 0),
    oneQuestionPerPage: true === data.oneQuestionPerPage,
    shuffle: true === data.shuffle,
    displayQuestionNumber: data.displayQuestionNumber !== false,
    showFormProfile: true === data.showFormProfile,
    selectedProfileFields: Array.isArray(data.selectedProfileFields) ? data.selectedProfileFields : [],
    duration: Number(data.duration || 0),
    gradebookEnabled: true === data.gradebookEnabled,
    gradebookCategoryId: data.gradebookCategoryId || null,
    gradebookWeight: Number(data.gradebookWeight || 0),
  }

  settings.value = data.settings || {}
  options.value = data.options || {}
  csrfToken.value = data.csrfToken || ""
  questionUrl.value = data.questionUrl || ""
}

async function loadConfiguration() {
  isLoading.value = true
  errorMessage.value = ""

  try {
    const data = await surveyService.getSurveyConfiguration(getContextParams(), isEditMode.value ? surveyId.value : null)
    normalizeForm(data)
  } catch (error) {
    console.error("Error loading survey configuration", error)
    errorMessage.value = error?.response?.data?.detail || t("Could not load survey configuration")
  } finally {
    isLoading.value = false
  }
}

function validateForm() {
  firstInvalidFieldId.value = ""

  if (!isEditMode.value && !form.value.code.trim()) {
    errorMessage.value = t("The survey code is required.")
    firstInvalidFieldId.value = "survey_code"

    return false
  }

  if (!stripHtml(form.value.title).trim()) {
    errorMessage.value = t("The survey title is required.")
    firstInvalidFieldId.value = "survey_title"

    return false
  }

  if (!form.value.availableFrom) {
    errorMessage.value = t("Invalid date")
    firstInvalidFieldId.value = "available_from"

    return false
  }

  if (!form.value.availableUntil) {
    errorMessage.value = t("Invalid date")
    firstInvalidFieldId.value = "available_until"

    return false
  }

  if (form.value.availableFrom > form.value.availableUntil) {
    errorMessage.value = t("The first date should be before the end date")
    firstInvalidFieldId.value = "available_from"

    return false
  }

  if (form.value.gradebookEnabled && !form.value.gradebookCategoryId) {
    errorMessage.value = t("Select assessment")
    firstInvalidFieldId.value = "category_id"
    showAdvancedSettings.value = true

    return false
  }

  return true
}

function buildPayload() {
  const payload = {
    ...form.value,
    duration: durationValue.value > 0 ? durationValue.value : null,
    availableFrom: toPayloadDate(form.value.availableFrom),
    availableUntil: toPayloadDate(form.value.availableUntil),
    csrfToken: csrfToken.value,
  }

  if (payload.anonymous || !payload.showFormProfile) {
    payload.showFormProfile = false
    payload.selectedProfileFields = []
  }

  return payload
}

async function scrollToFeedback(target = "error") {
  await nextTick()

  const alert = "success" === target ? successAlertRef.value : errorAlertRef.value
  if (alert?.scrollIntoView) {
    alert.scrollIntoView({ behavior: "smooth", block: "center" })
    alert.focus?.({ preventScroll: true })
  }
}

async function focusFirstInvalidField() {
  await nextTick()

  const selector = firstInvalidFieldId.value ? `#${firstInvalidFieldId.value}` : "[aria-invalid='true']"
  const element = document.querySelector(selector)
  const focusTarget = element?.querySelector?.("input, textarea, select, button, [contenteditable='true']") || element

  focusTarget?.focus?.({ preventScroll: true })
}

async function submitForm() {
  formSubmitted.value = true
  errorMessage.value = ""
  successMessage.value = ""

  if (!validateForm()) {
    await scrollToFeedback()
    await focusFirstInvalidField()

    return
  }

  isSaving.value = true

  try {
    const saved = await surveyService.saveSurveyConfiguration(
      buildPayload(),
      getContextParams(),
      isEditMode.value ? surveyId.value : null,
    )

    questionUrl.value = saved.questionUrl || questionUrl.value
    csrfToken.value = saved.csrfToken || csrfToken.value

    if (!isEditMode.value && saved.surveyId) {
      if (isLearningPathContext.value) {
        await router.push(buildLearningPathQuestionsRoute(saved.surveyId))

        return
      }

      await router.push({
        name: "SurveyQuestions",
        params: {
          node: route.params.node,
          surveyId: saved.surveyId,
        },
        query: getContextParams(),
      })

      return
    }

    successMessage.value = t("The survey has been saved successfully")
    await scrollToFeedback("success")
  } catch (error) {
    console.error("Error saving survey configuration", error)
    errorMessage.value = error?.response?.data?.detail || t("Could not save survey configuration")
    await scrollToFeedback()
  } finally {
    isSaving.value = false
  }
}

onMounted(loadConfiguration)
</script>
