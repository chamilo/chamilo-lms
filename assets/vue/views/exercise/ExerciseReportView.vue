<template>
  <section class="space-y-5">
    <div class="flex w-fit flex-wrap items-center gap-1 rounded-xl border border-gray-20 bg-white px-2 py-1 shadow-sm">
      <BaseButton
        :label="t('Back to exercises')"
        :route="{ name: 'ExerciseList', params: getBaseRouteParams(), query: getContextParams() }"
        :icon="safeIcon('back')"
        only-icon
        size="small"
        type="primary-text"
      />
      <BaseButton
        :label="t('Edit questions')"
        :route="{ name: 'ExerciseQuestions', params: getExerciseRouteParams(), query: getContextParams() }"
        :icon="safeIcon('edit')"
        only-icon
        size="small"
        type="secondary-text"
      />
      <BaseButton
        v-if="legacyUrls.liveStats"
        :label="t('Live results')"
        :to-url="legacyUrls.liveStats"
        :icon="safeIcon('table', 'information')"
        only-icon
        size="small"
        type="primary-text"
      />
      <BaseButton
        v-if="legacyUrls.questionReport"
        :label="t('Report by question')"
        :to-url="legacyUrls.questionReport"
        :icon="safeIcon('table', 'information')"
        only-icon
        size="small"
        type="primary-text"
      />
      <BaseButton
        v-if="legacyUrls.export"
        :label="t('Export')"
        :to-url="legacyUrls.export"
        :icon="safeIcon('file-excel', 'information')"
        only-icon
        size="small"
        type="primary-text"
      />
      <BaseButton
        v-if="legacyUrls.recalculateAll"
        :label="t('Recalculate results')"
        :to-url="legacyUrls.recalculateAll"
        :icon="safeIcon('refresh', 'information')"
        only-icon
        size="small"
        type="secondary-text"
      />
      <BaseButton
        v-if="legacyUrls.exportAllPdf"
        :label="t('Export all attempts')"
        :to-url="legacyUrls.exportAllPdf"
        :icon="safeIcon('file-excel', 'information')"
        only-icon
        size="small"
        type="primary-text"
      />
      <BaseButton
        v-if="legacyUrls.sendAllEmails"
        :label="t('Send all results/corrections by email')"
        :to-url="legacyUrls.sendAllEmails"
        :icon="safeIcon('information')"
        only-icon
        size="small"
        type="primary-text"
      />
      <BaseButton
        v-if="legacyUrls.questionStats"
        :label="t('Question stats')"
        :to-url="legacyUrls.questionStats"
        :icon="safeIcon('table', 'information')"
        size="small"
        type="primary-text"
      />
      <BaseButton
        v-if="legacyUrls.legacyReport"
        :label="t('Open legacy report')"
        :to-url="legacyUrls.legacyReport"
        :icon="safeIcon('table', 'information')"
        only-icon
        size="small"
        type="secondary-text"
      />
      <BaseButton
        :label="isSearchVisible ? t('Hide search') : t('Search')"
        :icon="isSearchVisible ? safeIcon('close', 'information') : safeIcon('search')"
        only-icon
        size="small"
        type="primary-text"
        @click="toggleSearch"
      />
    </div>

    <div class="border-b border-gray-20" />

    <header class="overflow-hidden rounded-xl border border-gray-20 bg-white shadow-sm">
      <div class="border-l-4 border-l-primary p-5">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
          <div class="space-y-2">
            <h1 class="text-2xl font-semibold text-gray-90">
              {{ displayText(title, t('Learner score')) }}
            </h1>
            <div
              v-if="description"
              class="exercise-report-html text-sm text-gray-700"
              v-html="description"
            />
          </div>
          <div class="flex flex-wrap gap-2">
            <span class="rounded-full bg-info/10 px-3 py-1 text-sm font-semibold text-info">
              {{ t('Attempts') }}: {{ attempts.length }}
            </span>
            <span
              v-if="pendingCorrectionCount > 0"
              class="rounded-full bg-warning/10 px-3 py-1 text-sm font-semibold text-warning"
            >
              {{ t('Pending corrections') }}: {{ pendingCorrectionCount }}
            </span>
          </div>
        </div>
      </div>
    </header>

    <form
      v-if="isSearchVisible"
      class="rounded-xl border border-gray-20 bg-white p-4 shadow-sm"
      @submit.prevent="applyFilters"
    >
      <div class="grid gap-3 md:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_minmax(0,1fr)_auto] md:items-end">
        <BaseInputText
          id="exercise-report-first-name"
          v-model="filterForm.firstName"
          :label="t('First name')"
          name="firstName"
        />
        <BaseInputText
          id="exercise-report-last-name"
          v-model="filterForm.lastName"
          :label="t('Last name')"
          name="lastName"
        />
        <BaseSelect
          id="exercise-report-status"
          v-model="filterForm.status"
          :label="t('Status')"
          name="status"
          :options="statusOptions"
          option-label="label"
          option-value="value"
        />
        <div class="flex min-h-[42px] flex-wrap items-end gap-2 md:self-end">
          <BaseButton
            :is-loading="isLoading"
            :label="t('Search')"
            :icon="safeIcon('search')"
            :is-submit="true"
            type="primary"
          />
          <BaseButton
            v-if="hasFilters"
            :label="t('Clear search')"
            :icon="safeIcon('close', 'information')"
            type="secondary"
            @click="clearFilters"
          />
        </div>
      </div>
    </form>

    <div
      v-if="errorMessage"
      class="rounded-xl border border-danger/30 bg-danger/10 p-4 text-sm text-danger"
    >
      {{ errorMessage }}
    </div>

    <div
      v-if="successMessage"
      class="rounded-xl border border-success/30 bg-success/10 p-4 text-sm text-success"
    >
      {{ successMessage }}
    </div>

    <BaseTable
      :is-loading="isLoading"
      :text-for-empty="t('No attempts found')"
      :total-items="attempts.length"
      :values="attempts"
      data-key="id"
    >
      <Column :header="t('First name')" field="firstName" sortable>
        <template #body="{ data }">
          <div class="font-semibold text-gray-90">{{ displayText(data.firstName, '-') }}</div>
          <div class="text-xs text-gray-500">{{ data.username }}</div>
        </template>
      </Column>
      <Column :header="t('Last name')" field="lastName" sortable>
        <template #body="{ data }">
          {{ displayText(data.lastName, '-') }}
        </template>
      </Column>
      <Column :header="t('Group')" field="groupName">
        <template #body="{ data }">
          {{ displayText(data.groupName, '-') }}
        </template>
      </Column>
      <Column :header="t('Duration')" field="duration" sortable>
        <template #body="{ data }">
          {{ formatSeconds(data.duration) }}
        </template>
      </Column>
      <Column :header="t('Start Date')" field="startedAt" sortable>
        <template #body="{ data }">
          {{ formatDate(data.startedAt) }}
        </template>
      </Column>
      <Column :header="t('End Date')" field="completedAt" sortable>
        <template #body="{ data }">
          {{ formatDate(data.completedAt) }}
        </template>
      </Column>
      <Column :header="t('Score')" field="score" sortable>
        <template #body="{ data }">
          <span class="font-semibold text-gray-90">
            {{ formatNumber(data.percentage) }} % ({{ formatNumber(data.score) }} / {{ formatNumber(data.maxScore) }})
          </span>
        </template>
      </Column>
      <Column :header="t('IP')" field="ip">
        <template #body="{ data }">
          {{ displayText(data.ip, '-') }}
        </template>
      </Column>
      <Column :header="t('Status')" field="status" sortable>
        <template #body="{ data }">
          <span
            class="rounded-full px-3 py-1 text-xs font-semibold"
            :class="statusClass(data.status)"
          >
            {{ t(data.statusLabel || 'Completed') }}
          </span>
        </template>
      </Column>
      <Column :header="t('Learning path')" field="learningPath">
        <template #body="{ data }">
          {{ displayText(data.learningPath, '-') }}
        </template>
      </Column>
      <Column :header="t('Detail')">
        <template #body="{ data }">
          <div class="flex flex-wrap justify-end gap-1">
            <BaseButton
              v-if="data.canReview"
              :label="data.pendingCorrection ? t('Review / Correct') : t('Review attempt')"
              :route="{ name: 'ExerciseResult', params: { ...getExerciseRouteParams(), attemptId: data.attemptId }, query: getContextParams() }"
              :icon="data.pendingCorrection ? safeIcon('edit') : safeIcon('information')"
              only-icon
              size="small"
              :type="data.pendingCorrection ? 'secondary-text' : 'primary-text'"
            />
            <BaseButton
              v-if="data.legacyUrls?.result"
              :label="t('Open legacy result')"
              :to-url="data.legacyUrls.result"
              :icon="safeIcon('information')"
              only-icon
              size="small"
              type="primary-text"
            />
            <BaseButton
              v-if="data.legacyUrls?.recalculate"
              :label="t('Recalculate results')"
              :to-url="data.legacyUrls.recalculate"
              :icon="safeIcon('refresh', 'information')"
              only-icon
              size="small"
              type="secondary-text"
            />
            <BaseButton
              v-if="data.legacyUrls?.pdf"
              :label="t('Export to PDF')"
              :to-url="data.legacyUrls.pdf"
              :icon="safeIcon('file-excel', 'information')"
              only-icon
              size="small"
              type="primary-text"
            />
            <BaseButton
              v-if="data.legacyUrls?.sendEmail"
              :label="t('Send by e-mail')"
              :to-url="data.legacyUrls.sendEmail"
              :icon="safeIcon('information')"
              only-icon
              size="small"
              type="primary-text"
            />
            <BaseButton
              v-if="data.canDelete"
              :label="t('Delete attempt')"
              :icon="safeIcon('delete', 'information')"
              only-icon
              size="small"
              type="danger-text"
              @click="confirmDeleteAttempt(data)"
            />
          </div>
        </template>
      </Column>
    </BaseTable>
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import { chamiloIconToClass } from "../../components/basecomponents/ChamiloIcons"
import { useConfirmation } from "../../composables/useConfirmation"
import exerciseService from "../../services/exerciseService"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const { requireConfirmation } = useConfirmation()

const isLoading = ref(false)
const errorMessage = ref("")
const successMessage = ref("")
const title = ref("")
const description = ref("")
const attempts = ref([])
const legacyUrls = ref({})
const filterForm = reactive({
  firstName: getQueryValue(route.query.firstName) || "",
  lastName: getQueryValue(route.query.lastName) || "",
  status: getQueryValue(route.query.status) || "",
})
const isSearchVisible = ref(Boolean(filterForm.firstName || filterForm.lastName || filterForm.status))

const statusOptions = computed(() => [
  { label: t("All"), value: "" },
  { label: t("Completed"), value: "completed" },
  { label: t("Pending correction"), value: "pending_correction" },
  { label: t("Ongoing"), value: "incomplete" },
])
const pendingCorrectionCount = computed(() => attempts.value.filter((attempt) => attempt.pendingCorrection).length)
const hasFilters = computed(() => Boolean(getQueryValue(route.query.firstName) || getQueryValue(route.query.lastName) || getQueryValue(route.query.status)))
const availableIcons = Object.keys(chamiloIconToClass)

function safeIcon(icon, fallback = "information") {
  if (icon && availableIcons.includes(icon)) {
    return icon
  }

  if (fallback && availableIcons.includes(fallback)) {
    return fallback
  }

  return availableIcons[0] || "information"
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

function getBaseRouteParams() {
  return {
    node: route.params.node,
  }
}

function getExerciseRouteParams() {
  return {
    ...getBaseRouteParams(),
    exerciseId: route.params.exerciseId,
  }
}

function getExerciseId() {
  return Number(route.params.exerciseId || 0)
}

function getReportParams() {
  return {
    ...getContextParams(),
    firstName: getQueryValue(route.query.firstName),
    lastName: getQueryValue(route.query.lastName),
    status: getQueryValue(route.query.status),
  }
}

async function loadReport() {
  const exerciseId = getExerciseId()
  if (!exerciseId) {
    errorMessage.value = t("Invalid exercise")
    return
  }

  isLoading.value = true
  errorMessage.value = ""

  try {
    const response = await exerciseService.getExerciseRuntimeReport(getReportParams(), exerciseId)
    title.value = response.title || ""
    description.value = response.description || ""
    attempts.value = Array.isArray(response.attempts) ? response.attempts : []
    legacyUrls.value = response.legacyUrls || {}
  } catch (error) {
    console.error("Error loading exercise report", error)
    errorMessage.value = t("Could not load exercise report")
  } finally {
    isLoading.value = false
  }
}

function toggleSearch() {
  isSearchVisible.value = !isSearchVisible.value
}

function applyFilters() {
  const query = { ...route.query }

  if (filterForm.firstName.trim()) {
    query.firstName = filterForm.firstName.trim()
  } else {
    delete query.firstName
  }

  if (filterForm.lastName.trim()) {
    query.lastName = filterForm.lastName.trim()
  } else {
    delete query.lastName
  }

  if (filterForm.status) {
    query.status = filterForm.status
  } else {
    delete query.status
  }

  router.push({ name: route.name, params: route.params, query })
}

function clearFilters() {
  filterForm.firstName = ""
  filterForm.lastName = ""
  filterForm.status = ""
  const query = { ...route.query }
  delete query.firstName
  delete query.lastName
  delete query.status
  router.push({ name: route.name, params: route.params, query })
}

function confirmDeleteAttempt(attempt) {
  requireConfirmation({
    message: t("Delete attempt?"),
    accept: () => deleteAttempt(attempt),
  })
}

async function deleteAttempt(attempt) {
  const exerciseId = getExerciseId()
  const attemptId = Number(attempt.attemptId || 0)
  if (!exerciseId || !attemptId) {
    return
  }

  errorMessage.value = ""
  successMessage.value = ""
  try {
    const response = await exerciseService.deleteExerciseRuntimeAttempt({}, getContextParams(), exerciseId, attemptId)
    if (!response?.success) {
      throw new Error(response?.message || "Could not delete attempt")
    }

    successMessage.value = t("Attempt deleted")
    await loadReport()
  } catch (error) {
    console.error("Error deleting exercise attempt", error)
    errorMessage.value = t("Could not delete attempt")
  }
}

function statusClass(status) {
  if (status === "pending_correction") {
    return "bg-warning/10 text-warning"
  }

  if (status === "incomplete") {
    return "bg-danger/10 text-danger"
  }

  return "bg-success/10 text-success"
}

function formatNumber(value) {
  if (value === null || value === undefined || value === "") {
    return "—"
  }

  const number = Number(value)
  if (Number.isNaN(number)) {
    return "—"
  }

  return number.toFixed(2).replace(/\.00$/, "")
}

function formatSeconds(seconds) {
  const safeSeconds = Math.max(0, Number(seconds || 0))
  const hours = Math.floor(safeSeconds / 3600)
  const minutes = Math.floor((safeSeconds % 3600) / 60)
  const remainingSeconds = safeSeconds % 60

  if (hours > 0) {
    return `${String(hours).padStart(2, "0")}:${String(minutes).padStart(2, "0")}:${String(remainingSeconds).padStart(2, "0")}`
  }

  return `${String(minutes).padStart(2, "0")}:${String(remainingSeconds).padStart(2, "0")}`
}

function formatDate(value) {
  if (!value) {
    return ""
  }

  const date = new Date(value)
  if (Number.isNaN(date.getTime())) {
    return ""
  }

  return date.toLocaleString()
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

onMounted(loadReport)

watch(
  () => [route.params.exerciseId, route.query.cid, route.query.sid, route.query.gid, route.query.firstName, route.query.lastName, route.query.status],
  () => {
    filterForm.firstName = getQueryValue(route.query.firstName) || ""
    filterForm.lastName = getQueryValue(route.query.lastName) || ""
    filterForm.status = getQueryValue(route.query.status) || ""
    if (filterForm.firstName || filterForm.lastName || filterForm.status) {
      isSearchVisible.value = true
    }
    loadReport()
  },
)
</script>

<style scoped>
.exercise-report-html :deep(img) {
  max-width: 100%;
  height: auto;
}

.exercise-report-html :deep(p) {
  margin-bottom: 0.25rem;
}

.exercise-report-html :deep(p:last-child) {
  margin-bottom: 0;
}
</style>
