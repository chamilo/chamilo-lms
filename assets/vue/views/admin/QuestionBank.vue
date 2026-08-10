<template>
  <section class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-20 pb-4">
      <div class="flex items-center gap-1 rounded-xl border border-gray-20 bg-white px-2 py-1 shadow-sm">
        <BaseButton
          :label="t('Administration')"
          :route="backRoute"
          icon="back"
          only-icon
          size="small"
          type="primary-text"
        />
      </div>

      <div class="flex items-center gap-1 rounded-xl border border-gray-20 bg-white px-2 py-1 shadow-sm">
        <BaseButton
          :disabled="!searched || exportingPdf"
          :is-loading="exportingPdf"
          :label="t('Export to PDF')"
          icon="file-pdf"
          only-icon
          size="small"
          type="primary-text"
          @click="downloadPdf"
        />
      </div>
    </div>

    <section class="space-y-1">
      <h1 class="text-2xl font-semibold text-gray-90">{{ t("Questions") }}</h1>
      <p class="text-sm text-gray-600">{{ t("Use filters to narrow down questions.") }}</p>
    </section>

    <div
      v-if="errorMessage"
      class="rounded-xl border border-danger/30 bg-danger/10 p-4 text-sm text-danger"
    >
      {{ errorMessage }}
    </div>

    <form
      class="overflow-hidden rounded-2xl border border-gray-20 bg-white shadow-sm"
      @submit.prevent="search"
    >
      <div class="border-b border-gray-20 bg-gray-10 px-5 py-4">
        <h2 class="text-lg font-semibold text-gray-90">{{ t("Search") }}</h2>
        <p class="mt-1 text-sm text-gray-600">{{ t("Use filters to narrow down questions.") }}</p>
      </div>

      <div class="grid gap-4 p-5 md:grid-cols-2 xl:grid-cols-3">
        <BaseInputText
          id="admin-question-bank-id"
          v-model="filters.id"
          :label="t('Id')"
          name="id"
          type="number"
        />
        <BaseInputText
          id="admin-question-bank-title"
          v-model="filters.title"
          :label="t('Title')"
          name="title"
        />
        <BaseInputText
          id="admin-question-bank-description"
          v-model="filters.description"
          :label="t('Description')"
          name="description"
        />
        <BaseSelect
          id="admin-question-bank-course"
          v-model="filters.selectedCourse"
          :label="t('Course')"
          name="selected_course"
          :options="courseOptions"
          option-label="label"
          option-value="value"
          :message-text="t('Course in which the question was initially created.')"
        />
        <BaseSelect
          id="admin-question-bank-difficulty"
          v-model="filters.questionLevel"
          :label="t('Difficulty')"
          name="question_level"
          :options="difficultyOptions"
          option-label="label"
          option-value="value"
        />
        <BaseSelect
          id="admin-question-bank-answer-type"
          v-model="filters.answerType"
          :label="t('Answer type')"
          name="answer_type"
          :options="questionTypeOptions"
          option-label="label"
          option-value="value"
        />

        <template
          v-for="field in extraFields"
          :key="field.variable"
        >
          <div
            v-if="isDoubleSelectField(field)"
            class="grid gap-3 md:grid-cols-2"
          >
            <BaseSelect
              :id="`admin-question-bank-extra-${field.variable}-first`"
              v-model="doubleExtraValues[field.variable].first"
              :label="t(field.label)"
              :name="`extra_${field.variable}`"
              :options="doubleFirstOptions(field)"
              option-label="label"
              option-value="value"
              :message-text="field.help ? t(field.help) : null"
              @change="clearDoubleSecond(field.variable)"
            />
            <BaseSelect
              :id="`admin-question-bank-extra-${field.variable}-second`"
              v-model="doubleExtraValues[field.variable].second"
              :disabled="!doubleExtraValues[field.variable].first"
              :label="t('Select')"
              :name="`extra_${field.variable}_second`"
              :options="doubleSecondOptions(field)"
              option-label="label"
              option-value="value"
            />
          </div>

          <BaseSelect
            v-else-if="isOptionField(field)"
            :id="`admin-question-bank-extra-${field.variable}`"
            v-model="filters.extraValues[`extra_${field.variable}`]"
            :label="t(field.label)"
            :name="`extra_${field.variable}`"
            :options="extraFieldOptions(field)"
            option-label="label"
            option-value="value"
            :message-text="field.help ? t(field.help) : null"
          />

          <div
            v-else-if="isMultipleField(field)"
            class="field"
          >
            <label
              :for="`admin-question-bank-extra-${field.variable}`"
              class="mb-1 block text-sm font-medium text-gray-700"
            >
              {{ t(field.label) }}
            </label>
            <select
              :id="`admin-question-bank-extra-${field.variable}`"
              v-model="multipleExtraValues[field.variable]"
              class="form-control min-h-28 w-full"
              multiple
              :name="`extra_${field.variable}[]`"
            >
              <option
                v-for="option in field.options"
                :key="`${field.variable}-${option.value}`"
                :value="String(option.value)"
              >
                {{ t(option.label) }}
              </option>
            </select>
            <small
              v-if="field.help"
              class="form-text text-muted"
            >
              {{ t(field.help) }}
            </small>
          </div>

          <BaseInputText
            v-else
            :id="`admin-question-bank-extra-${field.variable}`"
            v-model="filters.extraValues[`extra_${field.variable}`]"
            :label="t(field.label)"
            :name="`extra_${field.variable}`"
            :type="extraInputType(field)"
            :help-text="field.help ? t(field.help) : ''"
          />
        </template>
      </div>

      <div class="flex flex-wrap justify-end gap-2 border-t border-gray-20 bg-gray-10 px-5 py-4">
        <BaseButton
          :label="t('Clear')"
          icon="close"
          type="secondary"
          @click="clearFilters"
        />
        <BaseButton
          :is-loading="loading"
          :label="t('Search')"
          icon="search"
          is-submit
          name="search"
        />
      </div>
    </form>

    <section
      v-if="loading && !searched"
      class="rounded-2xl border border-gray-20 bg-white p-10 text-center shadow-sm"
    >
      <i class="mdi mdi-loading mdi-spin text-3xl text-primary" />
      <p class="mt-3 text-sm text-gray-600">{{ t("Loading") }}</p>
    </section>

    <section
      v-else-if="!searched"
      class="rounded-2xl border border-dashed border-gray-30 bg-white p-10 text-center"
    >
      <i class="mdi mdi-comment-question-outline text-4xl text-gray-400" />
      <h2 class="mt-3 text-lg font-semibold text-gray-800">{{ t("Questions") }}</h2>
      <p class="mt-1 text-sm text-gray-600">{{ t("Use filters to narrow down questions.") }}</p>
    </section>

    <section
      v-else-if="questions.length === 0"
      class="rounded-2xl border border-gray-20 bg-white p-10 text-center shadow-sm"
    >
      <i class="mdi mdi-magnify-close text-4xl text-gray-400" />
      <h2 class="mt-3 text-lg font-semibold text-gray-800">{{ t("No results found") }}</h2>
      <p class="mt-1 text-sm text-gray-600">{{ t("Try adjusting your filters and search again.") }}</p>
    </section>

    <section
      v-else
      class="space-y-4"
      aria-live="polite"
    >
      <div class="flex flex-wrap items-center justify-between gap-2">
        <p class="text-sm text-gray-600">
          {{ resultRangeText }}
        </p>
        <BaseSelect
          id="admin-question-bank-items-per-page"
          v-model="itemsPerPage"
          :label="t('Results per page')"
          name="itemsPerPage"
          :options="itemsPerPageOptions"
          option-label="label"
          option-value="value"
          @change="changeItemsPerPage"
        />
      </div>

      <article
        v-for="question in questions"
        :key="question.id"
        class="overflow-hidden rounded-xl border border-gray-20 bg-white shadow-sm"
      >
        <header class="flex items-start gap-2 border-b border-gray-20 bg-gray-10 p-3">
          <button
            type="button"
            class="flex min-w-0 flex-1 items-start gap-3 rounded-lg text-left focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40"
            :aria-controls="`admin-question-bank-details-${question.id}`"
            :aria-expanded="isQuestionExpanded(question.id)"
            @click="toggleQuestion(question.id)"
          >
            <i
              aria-hidden="true"
              class="mdi mt-2 shrink-0 text-lg text-primary transition-transform duration-200"
              :class="isQuestionExpanded(question.id) ? 'mdi-chevron-down' : 'mdi-chevron-right'"
            />

            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg border border-gray-20 bg-white">
              <img
                v-if="question.typeIcon"
                :alt="t(question.typeLabel)"
                class="h-8 w-8 object-contain"
                :src="`/img/icons/64/${question.typeIcon}`"
                @error="useFallbackIcon"
              />
            </div>

            <div class="min-w-0 flex-1">
              <div class="flex flex-wrap items-center gap-2 text-xs text-gray-600">
                <span class="rounded-full bg-support-1 px-2 py-1 font-semibold text-support-4">#{{ question.id }}</span>
                <span class="rounded-full bg-gray-20 px-2 py-1">{{ t(question.typeLabel) }}</span>
                <span class="rounded-full bg-gray-20 px-2 py-1">{{ t("Difficulty") }}: {{ question.difficulty }}</span>
                <span
                  v-if="question.source?.courseCode"
                  class="rounded-full bg-gray-20 px-2 py-1"
                >
                  {{ t("Source") }}: {{ question.source.courseCode }}
                </span>
                <span
                  v-if="question.orphan"
                  class="rounded-full bg-warning/10 px-2 py-1 font-semibold text-warning"
                >
                  {{ t("Orphan question") }}
                </span>
              </div>

              <div
                class="question-rich-text mt-2 line-clamp-2 text-base font-semibold text-gray-90"
                v-html="question.titleHtml"
              />
            </div>
          </button>

          <div
            class="flex shrink-0 items-center gap-1 rounded-lg border border-gray-20 bg-white px-2 py-1"
            @click.stop
          >
            <BaseButton
              :disabled="!question.canEdit"
              :label="t('Edit')"
              icon="edit"
              only-icon
              size="small"
              type="primary-text"
              @click="openQuestionEditor(question)"
            />
            <BaseButton
              :disabled="!question.canDelete || deletingQuestionId === question.id"
              :is-loading="deletingQuestionId === question.id"
              :label="t('Delete')"
              icon="delete"
              only-icon
              size="small"
              type="danger-text"
              @click="confirmDelete(question)"
            />
          </div>
        </header>

        <div
          v-show="isQuestionExpanded(question.id)"
          :id="`admin-question-bank-details-${question.id}`"
          class="border-t border-gray-10"
        >
          <div
            v-if="question.descriptionHtml"
            class="question-rich-text border-b border-gray-20 bg-white px-5 py-4 text-sm text-gray-700"
            v-html="question.descriptionHtml"
          />

          <div class="grid gap-5 p-5 xl:grid-cols-2">
            <section>
              <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-600">
                {{ t("Answers") }}
              </h3>
              <div
                v-if="question.answers.length === 0"
                class="rounded-xl border border-dashed border-gray-30 p-4 text-sm text-gray-500"
              >
                {{ t("No answers") }}
              </div>
              <ol
                v-else
                class="space-y-2"
              >
                <li
                  v-for="answer in question.answers"
                  :key="`${question.id}-${answer.position}`"
                  class="rounded-xl border border-gray-20 bg-gray-10 p-3"
                >
                  <div
                    class="question-rich-text text-sm text-gray-800"
                    v-html="answer.html"
                  />
                  <div class="mt-2 flex flex-wrap gap-2 text-xs">
                    <span
                      v-if="answer.correct"
                      class="rounded-full bg-success/10 px-2 py-1 font-semibold text-success"
                    >
                      {{ t("Correct answer") }}
                    </span>
                    <span class="rounded-full bg-white px-2 py-1 text-gray-600">
                      {{ t("Score") }}: {{ formatScore(answer.score) }}
                    </span>
                  </div>
                </li>
              </ol>
            </section>

            <section>
              <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-600">
                {{ t("Tests using this question") }}
              </h3>
              <div
                v-if="question.exerciseReferences.length === 0"
                class="rounded-xl border border-dashed border-gray-30 p-4 text-sm text-gray-500"
              >
                {{ t("Orphan question") }}
              </div>
              <ul
                v-else
                class="space-y-2"
              >
                <li
                  v-for="reference in question.exerciseReferences"
                  :key="`${question.id}-${reference.exerciseId}-${reference.courseId}-${reference.sessionId}`"
                  class="flex items-start justify-between gap-3 rounded-xl border border-gray-20 p-3"
                >
                  <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-gray-800">
                      {{ reference.exerciseTitle || `${t("Test")} #${reference.exerciseId}` }}
                    </p>
                    <p class="mt-1 text-xs text-gray-600">
                      {{ reference.courseTitle || reference.courseCode }}
                      <span v-if="reference.courseCode">[{{ reference.courseCode }}]</span>
                    </p>
                    <span
                      v-if="reference.deleted"
                      class="mt-2 inline-flex rounded-full bg-danger/10 px-2 py-1 text-xs font-semibold text-danger"
                    >
                      {{ t("The test has been deleted") }}
                    </span>
                  </div>
                  <BaseButton
                    v-if="reference.courseNodeId && !reference.deleted"
                    :label="t('Edit')"
                    icon="link-external"
                    only-icon
                    size="small"
                    type="primary-text"
                    @click="openExerciseQuestionEditor(question, reference)"
                  />
                </li>
              </ul>
            </section>
          </div>
        </div>
      </article>

      <div
        v-if="totalItems > itemsPerPage"
        class="question-bank-paginator rounded-xl border border-gray-20 bg-white px-3 py-3 shadow-sm"
      >
        <Paginator
          :first="(page - 1) * itemsPerPage"
          :rows="itemsPerPage"
          template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink"
          :total-records="totalItems"
          @page="changePage"
        />
      </div>
    </section>
  </section>
</template>

<script setup>
import Paginator from "primevue/paginator"
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import { useConfirmation } from "../../composables/useConfirmation"
import { useNotification } from "../../composables/notification"
import adminQuestionBankService from "../../services/adminQuestionBankService"
import { useSecurityStore } from "../../store/securityStore"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const securityStore = useSecurityStore()
const { requireConfirmation } = useConfirmation()
const { showErrorNotification, showSuccessNotification } = useNotification()

const loading = ref(false)
const exportingPdf = ref(false)
const deletingQuestionId = ref(null)
const errorMessage = ref("")
const searched = ref(false)
const questions = ref([])
const courseOptions = ref([{ value: -1, label: "All" }])
const difficultyOptions = ref([{ value: -1, label: "All" }])
const questionTypeOptions = ref([{ value: -1, label: "All" }])
const extraFields = ref([])
const totalItems = ref(0)
const page = ref(1)
const itemsPerPage = ref(20)
const multipleExtraValues = reactive({})
const doubleExtraValues = reactive({})
const expandedQuestionIds = reactive(new Set())
let activeController = null
let suppressNextRouteReload = false

const filters = reactive({
  id: "",
  title: "",
  description: "",
  selectedCourse: -1,
  questionLevel: -1,
  answerType: -1,
  extraValues: {},
})

const itemsPerPageOptions = [
  { value: 10, label: "10" },
  { value: 20, label: "20" },
  { value: 50, label: "50" },
  { value: 100, label: "100" },
]

const backRoute = computed(() => (securityStore.isAdmin ? { name: "AdminIndex" } : { name: "Home" }))
const resultRangeText = computed(() => {
  if (totalItems.value === 0) {
    return t("No results found")
  }

  const start = (page.value - 1) * itemsPerPage.value + 1
  const end = Math.min(page.value * itemsPerPage.value, totalItems.value)

  return `${start} - ${end} / ${totalItems.value}`
})

function readRouteQuery() {
  filters.id = String(route.query.id ?? "")
  filters.title = String(route.query.title ?? "")
  filters.description = String(route.query.description ?? "")
  filters.selectedCourse = numberFromQuery(route.query.selected_course, -1)
  filters.questionLevel = numberFromQuery(route.query.question_level, -1)
  filters.answerType = numberFromQuery(route.query.answer_type, -1)
  page.value = Math.max(1, numberFromQuery(route.query.page, 1))
  itemsPerPage.value = Math.max(5, numberFromQuery(route.query.itemsPerPage, 20))
  searched.value = numberFromQuery(route.query.form_sent, 0) === 1

  filters.extraValues = {}
  Object.entries(route.query).forEach(([key, value]) => {
    if (key.startsWith("extra_")) {
      filters.extraValues[key] = Array.isArray(value) ? value.join(";") : String(value ?? "")
    }
  })
}

function numberFromQuery(value, fallback) {
  const parsed = Number(value)

  return Number.isFinite(parsed) ? parsed : fallback
}

function buildQuery({ targetPage = page.value, submitted = true } = {}) {
  syncMultipleExtraValues()

  const query = {
    form_sent: submitted ? 1 : undefined,
    page: targetPage,
    itemsPerPage: itemsPerPage.value,
    id: filters.id || undefined,
    title: filters.title || undefined,
    description: filters.description || undefined,
    selected_course: filters.selectedCourse,
    question_level: filters.questionLevel,
    answer_type: filters.answerType,
    ...filters.extraValues,
  }

  return Object.fromEntries(
    Object.entries(query).filter(([, value]) => value !== undefined && value !== null && value !== ""),
  )
}

function syncMultipleExtraValues() {
  extraFields.value.forEach((field) => {
    if (isMultipleField(field)) {
      filters.extraValues[`extra_${field.variable}`] = (multipleExtraValues[field.variable] ?? []).join(";")
    }

    if (isDoubleSelectField(field)) {
      const value = doubleExtraValues[field.variable] ?? { first: "", second: "" }
      filters.extraValues[`extra_${field.variable}`] =
        value.first && value.second ? `${value.first}::${value.second}` : ""
    }
  })
}

function hydrateMultipleExtraValues() {
  extraFields.value.forEach((field) => {
    const value = String(filters.extraValues[`extra_${field.variable}`] ?? "")

    if (isMultipleField(field)) {
      multipleExtraValues[field.variable] = value
        .split(";")
        .map((item) => item.trim())
        .filter(Boolean)
    }

    if (isDoubleSelectField(field)) {
      const [first = "", second = ""] = value.split("::", 2)
      doubleExtraValues[field.variable] = { first, second }
    }
  })
}

async function loadData({ updateUrl = false } = {}) {
  activeController?.abort()
  activeController = new AbortController()
  loading.value = true
  errorMessage.value = ""

  try {
    const query = buildQuery({ submitted: searched.value })
    if (updateUrl) {
      const targetFullPath = router.resolve({ path: route.path, query }).fullPath
      if (targetFullPath !== route.fullPath) {
        suppressNextRouteReload = true
        await router.replace({ query })
      }
    }

    const data = await adminQuestionBankService.getData(query, activeController.signal)
    questions.value = Array.isArray(data.items) ? data.items : []
    expandedQuestionIds.clear()
    courseOptions.value = translateOptions(data.courseOptions)
    difficultyOptions.value = translateOptions(data.difficultyOptions)
    questionTypeOptions.value = translateOptions(data.questionTypeOptions)
    extraFields.value = Array.isArray(data.extraFields) ? data.extraFields : []
    totalItems.value = Number(data.totalItems ?? 0)
    page.value = Number(data.page ?? page.value)
    itemsPerPage.value = Number(data.itemsPerPage ?? itemsPerPage.value)
    searched.value = Boolean(data.searched)
    hydrateMultipleExtraValues()
  } catch (error) {
    if (error?.name === "CanceledError" || error?.name === "AbortError") {
      return
    }

    errorMessage.value = error?.response?.data?.detail || error?.response?.data?.message || t("An error occurred")
  } finally {
    loading.value = false
  }
}

function translateOptions(options) {
  if (!Array.isArray(options)) {
    return []
  }

  return options.map((option) => ({ ...option, label: t(option.label) }))
}

async function search() {
  searched.value = true
  page.value = 1
  await loadData({ updateUrl: true })
}

async function clearFilters() {
  filters.id = ""
  filters.title = ""
  filters.description = ""
  filters.selectedCourse = -1
  filters.questionLevel = -1
  filters.answerType = -1
  filters.extraValues = {}
  Object.keys(multipleExtraValues).forEach((key) => {
    multipleExtraValues[key] = []
  })
  Object.keys(doubleExtraValues).forEach((key) => {
    doubleExtraValues[key] = { first: "", second: "" }
  })
  page.value = 1
  searched.value = false
  questions.value = []
  totalItems.value = 0
  await router.replace({ query: {} })
  await loadData()
}

async function changePage(event) {
  page.value = Number(event.page) + 1
  await loadData({ updateUrl: true })
  window.scrollTo({ top: 0, behavior: "smooth" })
}

async function changeItemsPerPage() {
  page.value = 1
  await loadData({ updateUrl: true })
}

function isQuestionExpanded(questionId) {
  return expandedQuestionIds.has(Number(questionId))
}

function toggleQuestion(questionId) {
  const id = Number(questionId)

  if (expandedQuestionIds.has(id)) {
    expandedQuestionIds.delete(id)

    return
  }

  expandedQuestionIds.add(id)
}

function confirmDelete(question) {
  requireConfirmation({
    title: t("Delete"),
    message: `${t("Please confirm your choice")} #${question.id}`,
    accept: async () => {
      deletingQuestionId.value = question.id
      try {
        await adminQuestionBankService.deleteQuestion(question.id)
        showSuccessNotification(`${t("Deleted")} #${question.id}`)

        if (questions.value.length === 1 && page.value > 1) {
          page.value -= 1
        }
        await loadData({ updateUrl: true })
      } catch (error) {
        showErrorNotification(error?.response?.data?.detail || error?.response?.data?.message || t("Delete failed"))
      } finally {
        deletingQuestionId.value = null
      }
    },
  })
}

function openQuestionEditor(question) {
  const exerciseReference = question.exerciseReferences.find(
    (reference) => reference.active && reference.courseNodeId && reference.courseId && reference.exerciseId,
  )
  if (exerciseReference) {
    openExerciseQuestionEditor(question, exerciseReference)

    return
  }

  const source = question.source
  if (!source?.courseNodeId || !source?.courseId) {
    return
  }

  const href = router.resolve({
    name: "ExerciseGlobalQuestionEdit",
    params: {
      node: source.courseNodeId,
      questionId: question.id,
    },
    query: {
      cid: source.courseId,
      sid: source.sessionId || 0,
      gid: 0,
    },
  }).href

  window.open(href, "_blank", "noopener,noreferrer")
}

function openExerciseQuestionEditor(question, reference) {
  if (!reference.courseNodeId || !reference.courseId || !reference.exerciseId) {
    return
  }

  const href = router.resolve({
    name: "ExerciseQuestionEdit",
    params: {
      node: reference.courseNodeId,
      exerciseId: reference.exerciseId,
      questionId: question.id,
    },
    query: {
      cid: reference.courseId,
      sid: reference.sessionId || 0,
      gid: 0,
    },
  }).href

  window.open(href, "_blank", "noopener,noreferrer")
}

async function downloadPdf() {
  if (!searched.value || exportingPdf.value) {
    return
  }

  exportingPdf.value = true
  try {
    const response = await adminQuestionBankService.exportPdf(buildQuery())
    const blob = new Blob([response.data], { type: "application/pdf" })
    const url = URL.createObjectURL(blob)
    const anchor = document.createElement("a")
    const contentDisposition = String(response.headers?.["content-disposition"] ?? "")
    const match = contentDisposition.match(/filename\*?=(?:UTF-8''|")?([^";]+)/i)
    anchor.href = url
    anchor.download = decodeURIComponent(match?.[1] ?? "questions-export.pdf")
    document.body.appendChild(anchor)
    anchor.click()
    anchor.remove()
    URL.revokeObjectURL(url)
  } catch (error) {
    showErrorNotification(error?.response?.data?.detail || t("An error occurred"))
  } finally {
    exportingPdf.value = false
  }
}

function isDoubleSelectField(field) {
  return Number(field.type) === 8
}

function isOptionField(field) {
  return [3, 4, 13].includes(Number(field.type))
}

function isMultipleField(field) {
  return [5, 10].includes(Number(field.type))
}

function extraFieldOptions(field) {
  const options = Array.isArray(field.options)
    ? field.options.map((option) => ({ ...option, label: t(option.label) }))
    : []

  if (Number(field.type) === 13) {
    return [
      { value: "", label: t("All") },
      { value: "1", label: t("Yes") },
    ]
  }

  return [{ value: "", label: t("All") }, ...options]
}

function doubleFirstOptions(field) {
  const options = Array.isArray(field.options) ? field.options : []
  const rootOptions = options.filter((option) => !String(option.parent ?? "").trim() || String(option.parent) === "0")
  const source =
    rootOptions.length > 0
      ? rootOptions
      : options.filter((option) => {
          const parent = String(option.parent ?? "")

          return !options.some((candidate) => String(candidate.id) === parent || String(candidate.value) === parent)
        })

  return [{ value: "", label: t("All") }, ...source.map((option) => ({ ...option, label: t(option.label) }))]
}

function doubleSecondOptions(field) {
  const selected = doubleExtraValues[field.variable]?.first
  if (!selected) {
    return [{ value: "", label: t("All") }]
  }

  const firstOption = (field.options ?? []).find((option) => String(option.value) === String(selected))
  const matches = (field.options ?? []).filter((option) => {
    const parent = String(option.parent ?? "")

    return parent === String(selected) || parent === String(firstOption?.id ?? "")
  })

  return [{ value: "", label: t("All") }, ...matches.map((option) => ({ ...option, label: t(option.label) }))]
}

function clearDoubleSecond(variable) {
  if (!doubleExtraValues[variable]) {
    doubleExtraValues[variable] = { first: "", second: "" }
  }
  doubleExtraValues[variable].second = ""
}

function extraInputType(field) {
  switch (Number(field.type)) {
    case 6:
      return "date"
    case 7:
      return "datetime-local"
    case 15:
    case 17:
    case 28:
      return "number"
    default:
      return "text"
  }
}

function formatScore(score) {
  const value = Number(score ?? 0)

  return Number.isInteger(value) ? String(value) : value.toFixed(2).replace(/0+$/, "").replace(/\.$/, "")
}

function useFallbackIcon(event) {
  event.target.src = "/img/icons/64/quiz.png"
}

watch(
  () => route.fullPath,
  async () => {
    readRouteQuery()

    if (suppressNextRouteReload) {
      suppressNextRouteReload = false

      return
    }

    await loadData()
  },
)

onMounted(async () => {
  readRouteQuery()
  await loadData()
})

onBeforeUnmount(() => {
  activeController?.abort()
})
</script>

<style scoped>
.question-rich-text :deep(p) {
  margin: 0 0 0.5rem;
}

.question-rich-text :deep(p:last-child) {
  margin-bottom: 0;
}

.question-rich-text :deep(img) {
  max-width: 100%;
  height: auto;
  border-radius: 0.5rem;
}

.question-rich-text :deep(mark) {
  border-radius: 0.25rem;
  background: rgb(254 240 138);
  padding: 0.125rem 0.25rem;
}

.question-bank-paginator :deep(.p-paginator) {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: center;
  gap: 0.375rem;
  background: transparent;
  padding: 0;
}

.question-bank-paginator :deep(.p-paginator-first),
.question-bank-paginator :deep(.p-paginator-prev),
.question-bank-paginator :deep(.p-paginator-page),
.question-bank-paginator :deep(.p-paginator-next),
.question-bank-paginator :deep(.p-paginator-last) {
  display: inline-flex;
  min-width: 2.5rem;
  height: 2.5rem;
  align-items: center;
  justify-content: center;
  border: 1px solid rgb(226 232 240);
  border-radius: 0.5rem;
  background: rgb(255 255 255);
  color: rgb(51 65 85);
  font-weight: 600;
  transition:
    background-color 150ms ease,
    border-color 150ms ease,
    color 150ms ease;
}

.question-bank-paginator :deep(.p-paginator-first:hover:not(:disabled)),
.question-bank-paginator :deep(.p-paginator-prev:hover:not(:disabled)),
.question-bank-paginator :deep(.p-paginator-page:hover:not(.p-paginator-page-selected)),
.question-bank-paginator :deep(.p-paginator-next:hover:not(:disabled)),
.question-bank-paginator :deep(.p-paginator-last:hover:not(:disabled)) {
  border-color: rgb(148 163 184);
  background: rgb(248 250 252);
}

.question-bank-paginator :deep(.p-paginator-page-selected) {
  border-color: rgb(37 99 235);
  background: rgb(37 99 235);
  color: rgb(255 255 255);
}

.question-bank-paginator :deep(button:disabled) {
  cursor: not-allowed;
  opacity: 0.4;
}
</style>
