<template>
  <section class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div
        v-if="!isLearnpathContext"
        class="exercise-overview-toolbar flex flex-wrap items-center gap-1 rounded-xl border border-gray-20 bg-white px-2 py-1 shadow-sm"
      >
        <BaseButton
          :label="t('Back to exercises')"
          :route="{ name: 'ExerciseList', params: route.params, query: getContextParams() }"
          icon="back"
          only-icon
          size="small"
          type="primary-text"
        />
        <BaseButton
          v-if="overview.canManage"
          :label="t('Edit')"
          :route="{ name: 'ExerciseEdit', params: route.params, query: getContextParams() }"
          icon="edit"
          only-icon
          size="small"
          type="secondary-text"
        />
        <BaseButton
          v-if="overview.canReport"
          :label="t('Results and feedback')"
          :route="{ name: 'ExerciseReport', params: route.params, query: getContextParams() }"
          icon="tracking"
          only-icon
          size="small"
          type="primary-text"
        />
      </div>
    </div>

    <div
      v-if="errorMessage"
      class="rounded-xl border border-danger/30 bg-danger/10 p-4 text-sm text-danger"
    >
      {{ errorMessage }}
    </div>

    <div
      v-if="isLoading"
      class="rounded-xl border border-gray-20 bg-white p-6 text-sm text-gray-600 shadow-sm"
    >
      {{ t("Loading") }}
    </div>

    <template v-else>
      <article class="exercise-overview rounded-xl border border-gray-20 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
          <div class="space-y-3">
            <div class="flex items-center gap-3">
              <span
                :class="chamiloIconToClass['multiple-marked']"
                class="ch-tool-icon-gradient text-3xl"
                aria-hidden="true"
              />
              <h1 class="text-2xl font-semibold text-gray-90">
                {{ displayText(overview.title, t("Untitled")) }}
              </h1>
            </div>
            <div
              v-if="displayText(overview.description)"
              class="max-w-4xl text-sm text-gray-700"
            >
              {{ displayText(overview.description) }}
            </div>
            <div class="flex flex-wrap gap-3 text-xs font-medium text-gray-700">
              <span>{{ t("Questions") }}: {{ overview.questionCount || 0 }}</span>
              <span>{{ t("Total score") }}: {{ formatScore(overview.maxScore) }}</span>
              <span v-if="overview.oneQuestionPerPage">{{ t("One question per page") }}</span>
              <span v-if="overview.duration">{{ t("Duration") }}: {{ t("{0} min", [overview.duration]) }}</span>
            </div>
          </div>
          <div class="flex flex-wrap gap-2">
            <span :class="['rounded-full px-3 py-1 text-xs font-semibold', availabilityBadgeClass(overview.availabilityStatus)]">
              {{ availabilityLabel(overview.availabilityStatus) }}
            </span>
            <span
              v-if="!overview.visible"
              class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700"
            >
              {{ t("Hidden") }}
            </span>
          </div>
        </div>
      </article>

      <div
        v-if="overview.notice"
        :class="overviewAlertClass('info')"
      >
        {{ t(overview.notice) }}
      </div>

      <div
        v-if="overview.maxAttempt > 0"
        :class="overviewAlertClass(overview.attemptLimitReached ? 'danger' : 'info')"
      >
        {{ t("Attempts") }}: {{ overview.currentUserAttemptCount || 0 }} / {{ overview.maxAttempt || 0 }}
      </div>

      <div
        v-if="overview.canStart"
        class="rounded-xl border border-gray-20 bg-white p-5 shadow-sm"
      >
        <BaseButton
          :disabled="isStartingAttempt"
          :label="isStartingAttempt ? t('Starting') : t(overview.startButtonLabel || 'Start test')"
          icon="play-box-outline"
          size="normal"
          type="success"
          @click="startAndOpenPlayer"
        />
      </div>

      <section
        v-if="overview.showAttemptsTable"
        class="rounded-xl border border-gray-20 bg-white p-5 shadow-sm"
      >
        <h2 class="mb-4 text-base font-semibold text-gray-90">{{ t("Attempt history") }}</h2>
        <BaseTable
          :values="overview.currentUserAttempts"
          :total-items="overview.currentUserAttempts.length"
          data-key="attemptId"
          :text-for-empty="t('No attempts yet')"
        >
          <Column
            :header="t('Attempt')"
            field="number"
          >
            <template #body="{ data }">
              {{ data.number }}
            </template>
          </Column>
          <Column :header="t('Start date')">
            <template #body="{ data }">
              {{ formatDate(data.startDate) }}
            </template>
          </Column>
          <Column :header="t('IP')">
            <template #body="{ data }">
              {{ data.userIp || "-" }}
            </template>
          </Column>
          <Column
            v-if="overview.showScoreColumn"
            :header="t('Score')"
          >
            <template #body="{ data }">
              {{ formatScore(data.score) }} / {{ formatScore(data.maxScore) }}
            </template>
          </Column>
          <Column
            v-if="overview.showDetailsColumn"
            :header="t('Details')"
          >
            <template #body="{ data }">
              <div class="flex flex-wrap items-center gap-2">
                <BaseButton
                  :label="t('Show')"
                  :route="{
                    name: 'ExerciseResult',
                    params: { ...route.params, exerciseId: getExerciseId(), attemptId: data.attemptId },
                    query: getContextParams(),
                  }"
                  icon="information"
                  size="small"
                  type="plain"
                />
                <span
                  v-if="data.showValidationStatus"
                  :class="[
                    'rounded-full px-2 py-0.5 text-xs font-semibold',
                    data.revised ? 'bg-success/10 text-success' : 'bg-info/10 text-info',
                  ]"
                >
                  {{ data.revised ? t("Validated") : t("Not validated") }}
                </span>
              </div>
            </template>
          </Column>
        </BaseTable>
      </section>
    </template>
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import { chamiloIconToClass } from "../../components/basecomponents/ChamiloIcons"
import exerciseService from "../../services/exerciseService"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()

const isLoading = ref(false)
const isStartingAttempt = ref(false)
const errorMessage = ref("")
const overview = reactive({
  exerciseId: 0,
  title: "",
  description: "",
  visible: false,
  categoryTitle: "",
  questionCount: 0,
  attemptCount: 0,
  currentUserAttemptCount: 0,
  currentUserAttempts: [],
  averageScore: 0,
  maxScore: 0,
  passPercentage: 0,
  startTime: null,
  endTime: null,
  duration: null,
  maxAttempt: 0,
  feedbackType: 0,
  resultsDisabled: 0,
  oneQuestionPerPage: false,
  randomAnswers: false,
  random: 0,
  randomByCategory: 0,
  canManage: false,
  canOpen: false,
  canStart: false,
  canReport: false,
  availabilityStatus: "open",
  showAttemptsTable: false,
  showScoreColumn: false,
  showDetailsColumn: false,
  attemptLimitReached: false,
  startButtonLabel: "Start test",
  notice: "",
})

function getQueryValue(value) {
  const normalizedValue = Array.isArray(value) ? value[0] : value

  if (typeof normalizedValue === "string" && normalizedValue.includes("¬_multiple_attempt=")) {
    return normalizedValue.split("¬_multiple_attempt=")[0]
  }

  return normalizedValue
}

function addOptionalQueryParam(params, key) {
  const value = getQueryValue(route.query[key])
  if (value !== undefined && value !== null && value !== "") {
    params[key] = value
  }
}

function getContextParams() {
  const params = {
    cid: getQueryValue(route.query.cid),
    sid: getQueryValue(route.query.sid),
    gid: getQueryValue(route.query.gid),
  }

  addOptionalQueryParam(params, "origin")
  addOptionalQueryParam(params, "lp_init")
  addOptionalQueryParam(params, "learnpath_id")
  addOptionalQueryParam(params, "learnpath_item_id")
  addOptionalQueryParam(params, "learnpath_item_view_id")
  addOptionalQueryParam(params, "lp_id")
  addOptionalQueryParam(params, "node")
  addOptionalQueryParam(params, "type")
  addOptionalQueryParam(params, "returnToLp")
  addOptionalQueryParam(params, "isStudentView")
  addOptionalQueryParam(params, "preview")

  return params
}


function isEmbeddedInLearnpath() {
  if (typeof window === "undefined") {
    return false
  }

  try {
    if (window.parent && window.parent !== window) {
      const parentPath = window.parent.location?.pathname || ""
      const referrer = document.referrer || ""

      return parentPath.includes("/main/lp/")
        || parentPath.includes("/main/newscorm/")
        || referrer.includes("/main/lp/")
        || referrer.includes("/main/newscorm/")
    }
  } catch (error) {
    return (document.referrer || "").includes("/main/lp/")
      || (document.referrer || "").includes("/main/newscorm/")
  }

  return false
}

const isLearnpathContext = computed(() => {
  const origin = String(getQueryValue(route.query.origin) || "")

  return origin === "learnpath"
    || Boolean(getQueryValue(route.query.lp_init))
    || Boolean(getQueryValue(route.query.learnpath_id))
    || isEmbeddedInLearnpath()
})

function getExerciseId() {
  return Number(getQueryValue(route.params.exerciseId) || 0)
}

function getRuntimeStartContextParams() {
  const params = { ...getContextParams() }

  if (
    overview.canManage
    && !isLearnpathContext.value
    && !Object.prototype.hasOwnProperty.call(params, "preview")
    && !Object.prototype.hasOwnProperty.call(params, "isStudentView")
  ) {
    params.preview = 1
  }

  return params
}

function buildPlayerQuery(startResponse = null, contextParams = null) {
  const query = { ...(contextParams || getContextParams()) }
  const attemptId = Number(startResponse?.attemptId || 0)

  if (attemptId > 0) {
    query.attemptId = attemptId
  }

  return query
}

async function openPlayer(startResponse = null, contextParams = null) {
  await router.push({
    name: "ExercisePlayer",
    params: route.params,
    query: buildPlayerQuery(startResponse, contextParams),
  })
}

function openLegacyRuntime(startResponse = null) {
  const overviewUrl = startResponse?.legacyUrls?.overview || startResponse?.legacyUrls?.exercise || ""
  if (overviewUrl && typeof window !== "undefined") {
    window.location.href = overviewUrl

    return true
  }

  return false
}

async function startAndOpenPlayer() {
  const exerciseId = getExerciseId()
  if (exerciseId <= 0) {
    errorMessage.value = t("Invalid exercise")

    return
  }

  isStartingAttempt.value = true
  errorMessage.value = ""

  try {
    const runtimeContextParams = getRuntimeStartContextParams()
    const response = await exerciseService.startExerciseAttempt({ exerciseId }, runtimeContextParams, exerciseId)

    if (response?.success) {
      await openPlayer(response, runtimeContextParams)

      return
    }

    if (true === response?.usesLegacyRuntime && openLegacyRuntime(response)) {
      return
    }

    errorMessage.value = response?.message ? t(response.message) : t("Could not start the attempt")
  } catch (error) {
    console.error("Error starting exercise attempt from overview", error)
    errorMessage.value = t("Could not start the attempt")
  } finally {
    isStartingAttempt.value = false
  }
}

async function loadOverview() {
  const exerciseId = getExerciseId()
  if (exerciseId <= 0) {
    errorMessage.value = t("Invalid exercise")

    return
  }

  isLoading.value = true
  errorMessage.value = ""

  try {
    const response = await exerciseService.getExerciseOverview(getContextParams(), exerciseId)
    Object.assign(overview, response || {})
    overview.currentUserAttempts = Array.isArray(response?.currentUserAttempts) ? response.currentUserAttempts : []
  } catch (error) {
    console.error("Error loading exercise overview", error)
    errorMessage.value = t("Could not load exercise overview")
  } finally {
    isLoading.value = false
  }
}


function overviewAlertClass(type) {
  switch (type) {
    case "danger":
    case "error":
      return "rounded-xl border border-danger/30 bg-danger/10 p-4 text-sm font-medium text-danger"
    case "warning":
      return "rounded-xl border border-warning/30 bg-warning/10 p-4 text-sm font-medium text-warning"
    case "success":
      return "rounded-xl border border-success/30 bg-success/10 p-4 text-sm font-medium text-success"
    default:
      return "rounded-xl border border-info/30 bg-info/10 p-4 text-sm font-medium text-info"
  }
}

function formatDate(date) {
  if (!date) {
    return t("No date")
  }

  const parsedDate = new Date(date)
  if (Number.isNaN(parsedDate.getTime())) {
    return t("No date")
  }

  return parsedDate.toLocaleString()
}

function formatScore(score) {
  return Number(score || 0).toFixed(2)
}

function availabilityLabel(status) {
  switch (status) {
    case "not_started":
      return t("Not started")
    case "closed":
      return t("Closed")
    default:
      return t("Open")
  }
}

function availabilityBadgeClass(status) {
  switch (status) {
    case "not_started":
      return "bg-blue-100 text-blue-700"
    case "closed":
      return "bg-gray-100 text-gray-700"
    default:
      return "bg-green-100 text-green-700"
  }
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

onMounted(loadOverview)

watch(
  () => [route.params.exerciseId, route.query.cid, route.query.sid, route.query.gid],
  () => loadOverview(),
)
</script>

<style scoped>
:deep(.exercise-overview-toolbar .p-button-icon) {
  font-size: 1.25rem;
}
</style>
