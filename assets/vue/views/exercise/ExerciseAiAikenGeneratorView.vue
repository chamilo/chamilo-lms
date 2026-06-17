<template>
  <section class="space-y-6">
    <div class="flex flex-wrap items-center gap-1 rounded-xl border border-gray-20 bg-white px-2 py-1 shadow-sm w-fit">
      <BaseButton
        :label="t('Back to Tests tool')"
        :route="{ name: 'ExerciseList', params: route.params, query: getContextParams() }"
        icon="back"
        only-icon
        size="small"
        type="primary-text"
      />
    </div>

    <div class="border-b border-gray-20" />

    <section class="space-y-1">
      <h1 class="text-xl font-semibold text-gray-90">
        {{ t("AI Aiken generator") }}
      </h1>
      <p class="text-sm text-gray-600">
        {{ t("Generate an Aiken quiz from a topic or from one course document.") }}
      </p>
    </section>

    <div
      v-if="errorMessage"
      ref="errorAlert"
      class="rounded-xl border border-danger/30 bg-danger/10 p-4 text-sm text-danger"
      role="alert"
      tabindex="-1"
    >
      {{ errorMessage }}
    </div>

    <div
      v-if="successMessage"
      ref="successAlert"
      class="rounded-xl border border-success/30 bg-success/10 p-4 text-sm text-success"
      role="status"
      tabindex="-1"
    >
      <p class="font-semibold">{{ successMessage }}</p>
      <p
        v-if="importedQuestionCount > 0"
        class="mt-1"
      >
        {{ t("Imported questions") }}: {{ importedQuestionCount }}
      </p>
      <div
        v-if="createdExerciseId"
        class="mt-3 flex flex-wrap gap-2"
      >
        <BaseButton
          :label="t('Edit questions')"
          icon="edit"
          type="primary"
          :route="{
            name: 'ExerciseQuestions',
            params: { ...route.params, exerciseId: createdExerciseId },
            query: getContextParams(),
          }"
        />
        <BaseButton
          :label="t('Configure')"
          icon="settings"
          type="secondary"
          :route="{
            name: 'ExerciseEdit',
            params: { ...route.params, exerciseId: createdExerciseId },
            query: getContextParams(),
          }"
        />
      </div>
    </div>

    <div
      v-if="isLoadingConfig"
      class="rounded-xl border border-gray-20 bg-white p-6 text-sm text-gray-600 shadow-sm"
    >
      {{ t("Loading") }}...
    </div>

    <div
      v-else-if="!config.enabled"
      class="rounded-xl border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-800"
    >
      {{ t(config.message || "No AI text providers configured.") }}
    </div>

    <template v-else>
      <div class="rounded-xl border border-gray-20 bg-white p-2 shadow-sm">
        <div class="flex flex-wrap gap-2">
          <BaseButton
            :label="t('Test from topic')"
            :type="activeTab === 'topic' ? 'primary' : 'plain'"
            @click="activeTab = 'topic'"
          />
          <BaseButton
            v-if="hasDocumentTab"
            :label="t('Test from document')"
            :type="activeTab === 'document' ? 'primary' : 'plain'"
            @click="activeTab = 'document'"
          />
        </div>
      </div>

      <form
        v-if="activeTab === 'topic'"
        class="space-y-6 rounded-xl border border-gray-20 bg-white p-6 shadow-sm"
        @submit.prevent="generateFromTopic"
      >
        <div>
          <h2 class="text-lg font-semibold text-gray-90">
            {{ t("Generate from topic") }}
          </h2>
          <p class="mt-1 text-sm text-gray-600">
            {{ t("Use a topic prompt to generate a new Aiken questionnaire.") }}
          </p>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
          <BaseInputText
            id="aiken-topic-exercise-title"
            v-model="topicForm.exerciseTitle"
            :label="t('Quiz title')"
            name="exerciseTitle"
          />
          <BaseInputText
            id="aiken-topic-prompt"
            v-model="topicForm.topic"
            :label="t('Questions topic')"
            name="quizName"
          />
          <BaseInputText
            id="aiken-topic-count"
            v-model="topicForm.numberOfQuestions"
            :label="t('Number of questions')"
            name="numberOfQuestions"
            type="number"
          />
          <BaseSelect
            id="aiken-topic-question-type"
            v-model="topicForm.questionType"
            :label="t('Question type')"
            name="questionType"
            :options="questionTypeOptions"
            option-label="label"
            option-value="value"
          />
          <BaseSelect
            v-if="textProviderOptions.length > 1"
            id="aiken-topic-provider"
            v-model="topicForm.provider"
            class="md:col-span-2"
            :label="t('AI provider')"
            name="aiProvider"
            :options="textProviderOptions"
            option-label="label"
            option-value="value"
          />
          <div
            v-else-if="textProviderOptions.length === 1"
            class="rounded-xl border border-gray-20 bg-gray-10 p-4 text-sm text-gray-700 md:col-span-2"
          >
            <span class="font-semibold">{{ t("AI provider") }}:</span>
            {{ textProviderOptions[0].label }}
          </div>
        </div>

        <div class="flex justify-end">
          <BaseButton
            :is-loading="isGenerating"
            :is-submit="true"
            :label="t('Generate Aiken')"
            :icon="safeIcon('robot', 'settings')"
            type="success"
          />
        </div>
      </form>

      <form
        v-if="activeTab === 'document' && hasDocumentTab"
        class="space-y-6 rounded-xl border border-gray-20 bg-white p-6 shadow-sm"
        @submit.prevent="generateFromDocument"
      >
        <div>
          <h2 class="text-lg font-semibold text-gray-90">
            {{ t("Generate from document") }}
          </h2>
          <p class="mt-1 text-sm text-gray-600">
            {{ t("Select one course document and generate questions based only on its contents.") }}
          </p>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
          <BaseInputText
            id="aiken-document-exercise-title"
            v-model="documentForm.exerciseTitle"
            :label="t('Quiz title')"
            name="documentExerciseTitle"
          />
          <BaseInputText
            id="aiken-document-topic"
            v-model="documentForm.topic"
            :label="t('Questions topic')"
            name="documentTopic"
          />
          <BaseInputText
            id="aiken-document-count"
            v-model="documentForm.numberOfQuestions"
            :label="t('Number of questions')"
            name="documentNumberOfQuestions"
            type="number"
          />
          <BaseSelect
            id="aiken-document-question-type"
            v-model="documentForm.questionType"
            :label="t('Question type')"
            name="documentQuestionType"
            :options="questionTypeOptions"
            option-label="label"
            option-value="value"
          />
          <BaseSelect
            v-if="documentProviderOptions.length > 1"
            id="aiken-document-provider"
            v-model="documentForm.provider"
            class="md:col-span-2"
            :label="t('AI provider')"
            name="documentAiProvider"
            :options="documentProviderOptions"
            option-label="label"
            option-value="value"
          />
          <div
            v-else-if="documentProviderOptions.length === 1"
            class="rounded-xl border border-gray-20 bg-gray-10 p-4 text-sm text-gray-700 md:col-span-2"
          >
            <span class="font-semibold">{{ t("AI provider") }}:</span>
            {{ documentProviderOptions[0].label }}
          </div>
        </div>

        <div class="space-y-3 rounded-xl border border-gray-20 bg-gray-10 p-4">
          <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
              <h3 class="text-base font-semibold text-gray-90">
                {{ t("Select a document") }}
              </h3>
              <p class="text-sm text-gray-600">
                {{ t("Only supported documents will be listed here.") }}
              </p>
            </div>
            <BaseInputText
              id="aiken-document-search"
              v-model="documentSearch"
              :label="t('Search')"
              name="documentSearch"
            />
          </div>

          <div
            v-if="filteredDocuments.length === 0"
            class="rounded-xl border border-dashed border-gray-30 bg-white p-6 text-center text-sm text-gray-600"
          >
            {{ t("No documents available.") }}
          </div>
          <div
            v-else
            class="max-h-80 space-y-2 overflow-y-auto"
          >
            <label
              v-for="documentItem in filteredDocuments"
              :key="documentItem.resourceFileId"
              class="flex cursor-pointer items-start gap-3 rounded-xl border border-gray-20 bg-white px-4 py-3 hover:border-primary"
            >
              <input
                v-model="documentForm.resourceFileId"
                class="mt-1 h-4 w-4 rounded border-gray-30"
                name="resourceFileId"
                type="radio"
                :value="Number(documentItem.resourceFileId)"
                @change="documentForm.documentTitle = documentItem.title || documentItem.filename || ''"
              />
              <span class="min-w-0 flex-1">
                <span class="block truncate text-sm font-semibold text-gray-90">
                  {{ documentItem.title || documentItem.filename }}
                </span>
                <span class="mt-1 flex flex-wrap gap-2 text-xs text-gray-500">
                  <span>{{ String(documentItem.extension || '').toUpperCase() }}</span>
                  <span v-if="documentItem.filename">{{ documentItem.filename }}</span>
                </span>
              </span>
            </label>
          </div>
        </div>

        <div class="flex justify-end">
          <BaseButton
            :is-loading="isGenerating"
            :is-submit="true"
            :label="t('Generate Aiken')"
            :icon="safeIcon('robot', 'settings')"
            type="success"
          />
        </div>
      </form>

      <section
        v-if="generatedAikenText"
        ref="generatedContentSection"
        class="space-y-4 rounded-xl border border-gray-20 bg-white p-6 shadow-sm"
        tabindex="-1"
      >
        <div>
          <h2 class="text-lg font-semibold text-gray-90">
            {{ t("Generated Aiken content") }}
          </h2>
          <p class="mt-1 text-sm text-gray-600">
            {{ t("Review the generated content before importing it as a new exercise.") }}
          </p>
        </div>

        <div class="space-y-2">
          <label
            class="text-sm font-semibold text-gray-90"
            for="aiken-generated-content"
          >
            {{ t("Aiken content") }}
          </label>
          <textarea
            id="aiken-generated-content"
            v-model="generatedAikenText"
            class="min-h-80 w-full rounded-xl border border-gray-30 bg-white px-4 py-3 text-sm text-gray-90 shadow-sm focus:border-primary focus:ring-2 focus:ring-support-3"
            name="aikenText"
          />
        </div>

        <div class="grid gap-4 md:grid-cols-2">
          <BaseInputText
            id="aiken-import-exercise-title"
            v-model="generatedExerciseTitle"
            :label="t('Quiz title')"
            name="generatedExerciseTitle"
          />
          <BaseInputText
            id="aiken-total-weight"
            v-model="totalWeight"
            :label="t('Total weight')"
            name="totalWeight"
            type="number"
          />
        </div>

        <div class="flex flex-wrap justify-end gap-2">
          <BaseButton
            :label="t('Clear')"
            icon="close"
            type="plain"
            @click="clearGeneratedContent"
          />
          <BaseButton
            :disabled="!generatedAikenText.trim() || isImporting"
            :is-loading="isImporting"
            :label="t('Import')"
            icon="import"
            type="success"
            @click="importGeneratedAiken"
          />
        </div>
      </section>
    </template>
  </section>
</template>

<script setup>
import { computed, nextTick, onMounted, reactive, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute } from "vue-router"
import { getCourseContext } from "../../utils/courseContext"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import { chamiloIconToClass } from "../../components/basecomponents/ChamiloIcons"
import exerciseService from "../../services/exerciseService"

const { t } = useI18n()
const route = useRoute()
const availableIcons = Object.keys(chamiloIconToClass)

const config = ref({})
const activeTab = ref("topic")
const isLoadingConfig = ref(true)
const isGenerating = ref(false)
const isImporting = ref(false)
const errorMessage = ref("")
const successMessage = ref("")
const generatedAikenText = ref("")
const generatedExerciseTitle = ref("")
const totalWeight = ref("20")
const documentSearch = ref("")
const createdExerciseId = ref(null)
const importedQuestionCount = ref(0)
const skippedQuestionCount = ref(0)
const errorAlert = ref(null)
const successAlert = ref(null)
const generatedContentSection = ref(null)

const topicForm = reactive({
  exerciseTitle: "",
  topic: "",
  numberOfQuestions: "10",
  questionType: "multiple_choice",
  provider: "",
})

const documentForm = reactive({
  exerciseTitle: "",
  topic: "",
  numberOfQuestions: "10",
  questionType: "multiple_choice",
  provider: "",
  resourceFileId: 0,
  documentTitle: "",
})

const textProviderOptions = computed(() => Array.isArray(config.value.textProviders) ? config.value.textProviders : [])
const documentProviderOptions = computed(() => Array.isArray(config.value.documentProviders) ? config.value.documentProviders : [])
const questionTypeOptions = computed(() => Array.isArray(config.value.questionTypes) ? config.value.questionTypes : [])
const documents = computed(() => Array.isArray(config.value.documents) ? config.value.documents : [])
const hasDocumentTab = computed(() => documentProviderOptions.value.length > 0)
const filteredDocuments = computed(() => {
  const query = documentSearch.value.trim().toLowerCase()
  if (!query) {
    return documents.value
  }

  return documents.value.filter((documentItem) => {
    const title = String(documentItem.title || documentItem.filename || "").toLowerCase()

    return title.includes(query)
  })
})

function safeIcon(icon, fallback = "information") {
  if (icon && availableIcons.includes(icon)) {
    return icon
  }

  if (fallback && availableIcons.includes(fallback)) {
    return fallback
  }

  return availableIcons[0] || "information"
}

function getContextParams() {
  return getCourseContext(route)
}

function normalizePositiveInt(value, fallback = 1, max = 100) {
  const numericValue = Number.parseInt(String(value || ""), 10)
  if (Number.isNaN(numericValue) || numericValue <= 0) {
    return fallback
  }

  return Math.min(numericValue, max)
}

function firstProvider(options) {
  return options.length > 0 ? String(options[0].value || "") : ""
}

function selectedTopicProvider() {
  return topicForm.provider || firstProvider(textProviderOptions.value)
}

function selectedDocumentProvider() {
  return documentForm.provider || firstProvider(documentProviderOptions.value)
}

function clearMessages() {
  errorMessage.value = ""
  successMessage.value = ""
}

async function focusElement(elementRef) {
  await nextTick()

  const element = elementRef.value
  if (!element) {
    return
  }

  element.scrollIntoView({ behavior: "smooth", block: "center" })
  element.focus({ preventScroll: true })
}

function setErrorMessage(message) {
  errorMessage.value = message
  void focusElement(errorAlert)
}

async function setSuccessMessage(message) {
  successMessage.value = message
  await focusElement(successAlert)
}

async function focusGeneratedContent() {
  await focusElement(generatedContentSection)
}

function resetImportResult() {
  createdExerciseId.value = null
  importedQuestionCount.value = 0
  skippedQuestionCount.value = 0
}

function setGeneratedContent(text, exerciseTitle) {
  generatedAikenText.value = String(text || "").trim()
  generatedExerciseTitle.value = String(exerciseTitle || "").trim()
  totalWeight.value = String(config.value.defaultTotalWeight || 20)
  resetImportResult()
}

function clearGeneratedContent() {
  generatedAikenText.value = ""
  generatedExerciseTitle.value = ""
  resetImportResult()
}

function ensureGenerationFields(mode) {
  if (mode === "topic") {
    if (!topicForm.exerciseTitle.trim() || !topicForm.topic.trim()) {
      setErrorMessage(t("Required field"))
      return false
    }

    return true
  }

  if (!documentForm.exerciseTitle.trim() || Number(documentForm.resourceFileId || 0) <= 0) {
    setErrorMessage(t("Required field"))
    return false
  }

  return true
}

async function loadGeneratorData() {
  isLoadingConfig.value = true
  clearMessages()

  try {
    const response = await exerciseService.getExerciseAiAikenGenerator(getContextParams())
    config.value = response || {}
    topicForm.numberOfQuestions = String(config.value.defaultNumberOfQuestions || 10)
    documentForm.numberOfQuestions = String(config.value.defaultNumberOfQuestions || 10)
    totalWeight.value = String(config.value.defaultTotalWeight || 20)
    topicForm.provider = firstProvider(textProviderOptions.value)
    documentForm.provider = firstProvider(documentProviderOptions.value)
    topicForm.questionType = questionTypeOptions.value[0]?.value || "multiple_choice"
    documentForm.questionType = questionTypeOptions.value[0]?.value || "multiple_choice"
  } catch (error) {
    console.error("Error loading AI Aiken generator data", error)
    setErrorMessage(error?.response?.data?.detail || error?.response?.data?.["hydra:description"] || t("Could not load AI Aiken generator"))
  } finally {
    isLoadingConfig.value = false
  }
}

async function generateFromTopic() {
  clearMessages()
  resetImportResult()

  if (!ensureGenerationFields("topic")) {
    return
  }

  isGenerating.value = true

  try {
    const response = await exerciseService.generateExerciseAikenFromTopic({
      quiz_name: topicForm.topic.trim(),
      nro_questions: normalizePositiveInt(topicForm.numberOfQuestions, 1, Number(config.value.maxNumberOfQuestions || 100)),
      question_type: topicForm.questionType,
      language: config.value.language || "en",
      ai_provider: selectedTopicProvider(),
      cid: Number(getContextParams().cid || 0),
      sid: Number(getContextParams().sid || 0),
    })

    if (response?.success) {
      setGeneratedContent(response.text || "", topicForm.exerciseTitle)
      await focusGeneratedContent()
      return
    }

    setErrorMessage(response?.text || t("Could not generate Aiken content"))
  } catch (error) {
    console.error("Error generating Aiken content", error)
    setErrorMessage(error?.response?.data?.text || error?.response?.data?.detail || t("Could not generate Aiken content"))
  } finally {
    isGenerating.value = false
  }
}

async function generateFromDocument() {
  clearMessages()
  resetImportResult()

  if (!ensureGenerationFields("document")) {
    return
  }

  isGenerating.value = true

  try {
    const response = await exerciseService.generateExerciseAikenFromDocument({
      prompt: documentForm.topic.trim(),
      quiz_name: documentForm.topic.trim(),
      nro_questions: normalizePositiveInt(documentForm.numberOfQuestions, 1, Number(config.value.maxNumberOfQuestions || 100)),
      question_type: documentForm.questionType,
      language: config.value.language || "en",
      ai_provider: selectedDocumentProvider(),
      resource_file_id: Number(documentForm.resourceFileId || 0),
      document_title: documentForm.documentTitle,
      cid: Number(getContextParams().cid || 0),
      sid: Number(getContextParams().sid || 0),
      gid: Number(getContextParams().gid || 0),
    })

    if (response?.success) {
      setGeneratedContent(response.text || "", documentForm.exerciseTitle)
      await focusGeneratedContent()
      return
    }

    setErrorMessage(response?.text || t("Could not generate Aiken content"))
  } catch (error) {
    console.error("Error generating Aiken content from document", error)
    setErrorMessage(error?.response?.data?.text || error?.response?.data?.detail || t("Could not generate Aiken content"))
  } finally {
    isGenerating.value = false
  }
}

async function importGeneratedAiken() {
  clearMessages()

  const text = generatedAikenText.value.trim()
  if (!text) {
    setErrorMessage(t("Required field"))
    return
  }

  const exerciseTitle = generatedExerciseTitle.value.trim() || t("Imported Aiken quiz")
  const filename = `${exerciseTitle.replace(/[^a-z0-9_-]+/gi, "_").replace(/^_+|_+$/g, "") || "aiken_quiz"}.txt`
  const file = new File([text], filename, { type: "text/plain" })
  const formData = new FormData()
  formData.append("submittedCsrfToken", config.value.csrfToken || "")
  formData.append("file", file)
  formData.append("totalWeight", totalWeight.value)
  formData.append("exerciseTitle", exerciseTitle)
  formData.append("exercise_title", exerciseTitle)
  formData.append("quiz_name", exerciseTitle)

  isImporting.value = true

  try {
    const response = await exerciseService.importExerciseQuestions("aiken", formData, getContextParams())
    createdExerciseId.value = response.exerciseId || null
    importedQuestionCount.value = Number(response.importedQuestionCount || 0)
    skippedQuestionCount.value = Number(response.skippedQuestionCount || 0)
    await setSuccessMessage(t(response.message || "Aiken quiz imported"))
  } catch (error) {
    console.error("Error importing generated Aiken content", error)
    setErrorMessage(error?.response?.data?.detail || error?.response?.data?.["hydra:description"] || t("Could not import generated Aiken content"))
  } finally {
    isImporting.value = false
  }
}

watch(
  () => documentForm.resourceFileId,
  (resourceFileId) => {
    const selectedDocument = documents.value.find((documentItem) => Number(documentItem.resourceFileId) === Number(resourceFileId))
    documentForm.documentTitle = selectedDocument?.title || selectedDocument?.filename || ""
  },
)

onMounted(loadGeneratorData)
</script>
