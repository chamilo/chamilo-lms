<script setup>
import { computed, onMounted, ref } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseCheckbox from "../../components/basecomponents/BaseCheckbox.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import { useNotification } from "../../composables/notification"
import { useConfirmation } from "../../composables/useConfirmation"
import lpService from "../../services/lpService"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const { requireConfirmation } = useConfirmation()
const { showErrorNotification, showSuccessNotification } = useNotification()

const report = ref(null)
const isLoading = ref(false)
const isResetting = ref(false)
const recalculatingUserId = ref(0)
const errorMessage = ref("")
const groupFilter = ref(String(route.query.groupFilter || ""))
const showTeachers = ref(["1", "true", "yes", "on"].includes(String(route.query.showTeachers || "").toLowerCase()))
const deleteExerciseAttempts = ref(false)
const selectedStudentId = ref(Number(route.query.studentId || 0))
const showAllAttempts = ref(false)

const lpId = computed(() => Number(route.params.lpId || 0))
const contextParams = computed(() => ({
  cid: Number(route.query.cid || 0),
  sid: Number(route.query.sid || 0),
  gid: Number(route.query.gid || 0),
}))
const filterOptions = computed(() => [
  { label: t("All"), value: "" },
  ...(report.value?.groupOptions || []),
])
const selectedLearner = computed(() => {
  const detailUser = report.value?.detail?.user
  if (detailUser?.id) {
    return detailUser
  }

  return (report.value?.learners || []).find((learner) => learner.id === selectedStudentId.value) || null
})
const hasLearners = computed(() => (report.value?.learners || []).length > 0)
const detailItems = computed(() => report.value?.detail?.items || [])
const reportingPdfUrl = computed(() => lpService.buildReportingPdfUrl(lpId.value, reportingParams(0)))

function reportingParams(studentId = selectedStudentId.value) {
  const params = {
    ...contextParams.value,
    groupFilter: groupFilter.value || undefined,
    showTeachers: showTeachers.value ? 1 : 0,
  }

  if (studentId > 0) {
    params.studentId = studentId
  }

  return params
}

function syncRouteQuery(studentId = selectedStudentId.value) {
  const query = {
    ...route.query,
    cid: contextParams.value.cid || undefined,
    sid: contextParams.value.sid || undefined,
    gid: contextParams.value.gid || undefined,
    groupFilter: groupFilter.value || undefined,
    showTeachers: showTeachers.value ? 1 : undefined,
    studentId: studentId > 0 ? studentId : undefined,
  }

  router.replace({ query })
}

async function loadReporting(studentId = selectedStudentId.value) {
  if (!lpId.value || !contextParams.value.cid) {
    errorMessage.value = t("An error occurred")
    return
  }

  isLoading.value = true
  errorMessage.value = ""

  try {
    report.value = await lpService.getReporting(lpId.value, reportingParams(studentId))
    selectedStudentId.value = Number(report.value?.detail?.user?.id || 0)
    syncRouteQuery(selectedStudentId.value)
  } catch (error) {
    errorMessage.value = error?.response?.data?.detail || t("An error occurred")
    showErrorNotification(error)
  } finally {
    isLoading.value = false
  }
}

async function applyFilters() {
  selectedStudentId.value = 0
  showAllAttempts.value = false
  await loadReporting(0)
}

async function toggleTeachers() {
  showTeachers.value = !showTeachers.value
  await applyFilters()
}

async function openLearnerDetail(learner) {
  selectedStudentId.value = Number(learner.id || 0)
  showAllAttempts.value = false
  await loadReporting(selectedStudentId.value)
}

function closeLearnerDetail() {
  selectedStudentId.value = 0
  showAllAttempts.value = false
  report.value = {
    ...report.value,
    detail: {},
  }
  syncRouteQuery(0)
}

function confirmReset(userIds) {
  if (!userIds.length || !report.value?.csrfToken) {
    return
  }

  requireConfirmation({
    message: t("Are you sure to delete results"),
    accept: () => resetUsers(userIds),
  })
}

async function resetUsers(userIds) {
  isResetting.value = true

  try {
    await lpService.resetReporting(lpId.value, reportingParams(0), {
      userIds,
      deleteExerciseAttempts: deleteExerciseAttempts.value,
      csrfToken: report.value.csrfToken,
    })

    if (userIds.includes(selectedStudentId.value)) {
      selectedStudentId.value = 0
      showAllAttempts.value = false
    }

    showSuccessNotification(t("Learning path was reset for the learner"))
    await loadReporting(selectedStudentId.value)
  } catch (error) {
    showErrorNotification(error)
  } finally {
    isResetting.value = false
  }
}

function confirmResetAll() {
  confirmReset((report.value?.learners || []).map((learner) => Number(learner.id)).filter((id) => id > 0))
}

function confirmRecalculate(userId) {
  if (userId <= 0 || !report.value?.csrfToken) {
    return
  }

  requireConfirmation({
    message: t("Recalculate results"),
    accept: () => recalculateUser(userId),
  })
}

async function recalculateUser(userId) {
  recalculatingUserId.value = userId

  try {
    await lpService.recalculateReporting(lpId.value, reportingParams(0), {
      userId,
      csrfToken: report.value.csrfToken,
    })

    showSuccessNotification(t("Results recalculated"))
    await loadReporting(selectedStudentId.value)
  } catch (error) {
    showErrorNotification(error)
  } finally {
    recalculatingUserId.value = 0
  }
}

function goBack() {
  router.push({
    name: "LpList",
    params: { node: route.params.node },
    query: contextParams.value,
  })
}

function formatDuration(totalSeconds) {
  const seconds = Math.max(0, Number(totalSeconds || 0))
  const hours = Math.floor(seconds / 3600)
  const minutes = Math.floor((seconds % 3600) / 60)
  const remainingSeconds = Math.floor(seconds % 60)

  return [hours, minutes, remainingSeconds].map((value) => String(value).padStart(2, "0")).join(":")
}

function formatDate(timestamp) {
  const value = Number(timestamp || 0)
  if (!value) {
    return "-"
  }

  return new Intl.DateTimeFormat(undefined, {
    dateStyle: "short",
    timeStyle: "short",
  }).format(new Date(value * 1000))
}

function formatPercentage(value) {
  if (value === null || value === undefined || Number.isNaN(Number(value))) {
    return "-"
  }

  return `${Number(value).toFixed(Number(value) % 1 === 0 ? 0 : 1)}%`
}

function formatAttemptScore(attempt) {
  const score = Number(attempt?.score || 0)
  const maxScore = Number(attempt?.maxScore || 0)

  if (maxScore > 0) {
    return `${score}/${maxScore} (${formatPercentage((score * 100) / maxScore)})`
  }

  return score ? String(score) : "-"
}

function statusLabel(status) {
  const normalized = String(status || "not attempted").trim().toLowerCase()
  const labels = {
    browsed: "Browsed",
    completed: "Completed",
    failed: "Failed",
    passed: "Passed",
    "not attempted": "Not attempted",
  }

  return t(labels[normalized] || status || "Not attempted")
}

function statusClass(status) {
  const normalized = String(status || "").toLowerCase()
  if (["completed", "passed"].includes(normalized)) {
    return "bg-green-100 text-green-700"
  }
  if ("failed" === normalized) {
    return "bg-red-100 text-red-700"
  }
  if ("browsed" === normalized) {
    return "bg-blue-100 text-blue-700"
  }

  return "bg-gray-100 text-gray-700"
}

function levelClass(level) {
  const classes = ["pl-0", "pl-4", "pl-8", "pl-12", "pl-16", "pl-20"]

  return classes[Math.min(Math.max(Number(level || 0), 0), classes.length - 1)]
}

function latestAttempt(item) {
  const attempts = item?.attempts || []

  return attempts.length ? attempts[attempts.length - 1] : null
}

function visibleAttempts(item) {
  const attempts = item?.attempts || []
  if (showAllAttempts.value || attempts.length <= 1) {
    return attempts
  }

  return attempts.slice(-1)
}

function csvCell(value) {
  const text = String(value ?? "")

  return `"${text.replaceAll('"', '""')}"`
}

function downloadCsv(filename, rows) {
  const content = `\ufeff${rows.map((row) => row.map(csvCell).join(",")).join("\n")}`
  const blob = new Blob([content], { type: "text/csv;charset=utf-8" })
  const url = URL.createObjectURL(blob)
  const link = document.createElement("a")
  link.href = url
  link.download = filename
  document.body.appendChild(link)
  link.click()
  link.remove()
  URL.revokeObjectURL(url)
}

function exportLearnerCsv() {
  if (!report.value?.detail?.user) {
    return
  }

  const rows = [
    [
      t("Learning object name"),
      t("Type"),
      t("Attempt"),
      t("Status"),
      t("Score"),
      t("Time"),
      t("Start date"),
    ],
  ]

  for (const item of detailItems.value) {
    if (!(item.attempts || []).length) {
      rows.push([item.title, item.type, "-", statusLabel("not attempted"), "-", "00:00:00", "-"])
      continue
    }

    for (const attempt of item.attempts) {
      rows.push([
        item.title,
        item.type,
        `${attempt.lpAttempt}.${attempt.itemAttempt}`,
        statusLabel(attempt.status),
        formatAttemptScore(attempt),
        formatDuration(attempt.timeSeconds),
        formatDate(attempt.startTime),
      ])
    }
  }

  downloadCsv(`learning-path-${lpId.value}-learner-${report.value.detail.user.id}.csv`, rows)
}

onMounted(() => loadReporting(selectedStudentId.value))
</script>

<template>
  <section class="flex flex-col gap-6">
    <SectionHeader :title="t('Learner score')">
      <div class="lp-report-no-print flex flex-wrap items-center justify-end gap-1">
        <BaseButton
          :label="t('Back')"
          icon="back"
          only-icon
          size="small"
          type="primary-text"
          @click="goBack"
        />
        <BaseButton
          v-if="hasLearners"
          :label="t('Export to PDF')"
          :to-url="reportingPdfUrl"
          icon="file-pdf"
          only-icon
          size="small"
          type="primary-text"
        />
        <BaseButton
          v-if="hasLearners"
          :disabled="isResetting"
          :is-loading="isResetting"
          :label="t('Clean')"
          icon="clear-all"
          only-icon
          size="small"
          type="danger-text"
          @click="confirmResetAll"
        />
      </div>
    </SectionHeader>

    <p
      v-if="report?.lpTitle"
      class="text-sm text-gray-60"
    >
      {{ report.lpTitle }}
    </p>

    <div
      v-if="errorMessage"
      class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"
    >
      {{ errorMessage }}
    </div>

    <div class="lp-report-no-print flex flex-col gap-4 rounded-xl border border-gray-25 bg-white p-4 shadow-sm">
      <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div class="flex min-w-0 flex-1 flex-col gap-4 md:flex-row md:items-end">
          <BaseSelect
            v-if="filterOptions.length > 1"
            id="lp-report-group-filter"
            v-model="groupFilter"
            class="w-full md:max-w-md"
            name="group_filter"
            :label="report?.allowUserGroups ? `${t('Groups')} / ${t('Classes')}` : t('Groups')"
            :options="filterOptions"
            option-label="label"
            option-value="value"
            @update:model-value="applyFilters"
          />

          <BaseCheckbox
            id="lp-report-delete-exercise-attempts"
            v-model="deleteExerciseAttempts"
            name="delete_exercise_attempts"
            :label="t('Delete exercise attempts')"
          />
        </div>

        <BaseButton
          :label="showTeachers ? t('Hide teachers') : t('Show teachers')"
          :icon="showTeachers ? 'eye-off' : 'eye-on'"
          only-icon
          size="small"
          type="info"
          @click="toggleTeachers"
        />
      </div>
    </div>

    <div>
      <BaseTable
        :is-loading="isLoading"
        :text-for-empty="t('No user added')"
        :total-items="report?.learners?.length || 0"
        :values="report?.learners || []"
        data-key="id"
      >
        <Column
          :header="t('First name')"
          field="firstname"
          sortable
        />
        <Column
          :header="t('Last name')"
          field="lastname"
          sortable
        />
        <Column
          v-if="report?.showEmail"
          :header="t('Email')"
          field="email"
        />
        <Column :header="report?.allowUserGroups ? `${t('Groups')} / ${t('Classes')}` : t('Groups')">
          <template #body="{ data }">
            <div class="flex flex-wrap gap-1">
              <span
                v-for="name in [...(data.groups || []), ...(data.classes || [])]"
                :key="name"
                class="rounded-full bg-gray-10 px-2 py-1 text-xs text-gray-70"
              >
                {{ name }}
              </span>
              <span v-if="!(data.groups?.length || data.classes?.length)">-</span>
            </div>
          </template>
        </Column>
        <Column
          v-if="!report?.hideTime"
          :header="t('Time')"
        >
          <template #body="{ data }">
            {{ formatDuration(data.timeSeconds) }}
          </template>
        </Column>
        <Column :header="t('Progress')">
          <template #body="{ data }">
            <div class="flex min-w-32 items-center gap-2">
              <div class="h-2 flex-1 overflow-hidden rounded-full bg-gray-20">
                <div
                  class="h-full rounded-full bg-support-4"
                  :style="{ width: `${Math.max(0, Math.min(100, Number(data.progress || 0)))}%` }"
                />
              </div>
              <span class="w-12 text-right text-sm">{{ formatPercentage(data.progress) }}</span>
            </div>
          </template>
        </Column>
        <Column :header="t('Score')">
          <template #body="{ data }">
            {{ formatPercentage(data.score) }}
          </template>
        </Column>
        <Column :header="t('Last connection')">
          <template #body="{ data }">
            {{ formatDate(data.lastConnection) }}
          </template>
        </Column>
        <Column
          :header="t('Actions')"
          class="lp-report-no-print"
        >
          <template #body="{ data }">
            <div class="flex items-center justify-end gap-1">
              <BaseButton
                :label="t('Reporting')"
                :to-url="data.generalReportingUrl"
                icon="tracking"
                only-icon
                size="small"
                type="primary-text"
              />
              <BaseButton
                :label="t('Details')"
                icon="information"
                only-icon
                size="small"
                type="primary-text"
                @click="openLearnerDetail(data)"
              />
              <BaseButton
                :disabled="isResetting"
                :label="t('Clean')"
                icon="clear-all"
                only-icon
                size="small"
                type="danger-text"
                @click="confirmReset([Number(data.id)])"
              />
              <BaseButton
                :disabled="recalculatingUserId > 0"
                :is-loading="recalculatingUserId === Number(data.id)"
                :label="t('Recalculate results')"
                icon="refresh"
                only-icon
                size="small"
                type="secondary-text"
                @click="confirmRecalculate(Number(data.id))"
              />
            </div>
          </template>
        </Column>
      </BaseTable>
    </div>

    <section
      v-if="report?.detail?.user"
      class="lp-report-no-print flex flex-col gap-4 rounded-xl border border-gray-25 bg-white p-4 shadow-sm"
    >
      <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
        <div>
          <h2 class="text-xl font-semibold text-gray-90">
            {{ selectedLearner?.firstname }} {{ selectedLearner?.lastname }}
          </h2>
          <p class="text-sm text-gray-60">
            {{ selectedLearner?.username }}<template v-if="selectedLearner?.email"> · {{ selectedLearner.email }}</template>
          </p>
        </div>

        <div class="flex flex-wrap items-center justify-end gap-1">
          <BaseButton
            v-if="!report.reducedReport"
            :label="showAllAttempts ? t('Hide all attempts') : t('Show all attempts')"
            :icon="showAllAttempts ? 'fold' : 'unfold'"
            only-icon
            size="small"
            type="primary-text"
            @click="showAllAttempts = !showAllAttempts"
          />
          <BaseButton
            :label="t('Export CSV')"
            icon="file-delimited-outline"
            only-icon
            size="small"
            type="primary-text"
            @click="exportLearnerCsv"
          />
          <BaseButton
            :label="t('Close')"
            icon="close"
            only-icon
            size="small"
            type="plain"
            @click="closeLearnerDetail"
          />
        </div>
      </div>

      <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-gray-20 p-4">
          <p class="text-sm text-gray-60">{{ t("Progress") }}</p>
          <p class="mt-1 text-2xl font-semibold">{{ formatPercentage(report.detail.progress) }}</p>
        </div>
        <div class="rounded-xl border border-gray-20 p-4">
          <p class="text-sm text-gray-60">{{ t("Score") }}</p>
          <p class="mt-1 text-2xl font-semibold">{{ formatPercentage(report.detail.score) }}</p>
        </div>
        <div
          v-if="!report.hideTime"
          class="rounded-xl border border-gray-20 p-4"
        >
          <p class="text-sm text-gray-60">{{ t("Time") }}</p>
          <p class="mt-1 text-2xl font-semibold">{{ formatDuration(report.detail.timeSeconds) }}</p>
        </div>
        <div class="rounded-xl border border-gray-20 p-4">
          <p class="text-sm text-gray-60">{{ t("Attempt") }}</p>
          <p class="mt-1 text-2xl font-semibold">{{ report.detail.lpAttempts?.length || 0 }}</p>
        </div>
      </div>

      <BaseTable
        :text-for-empty="t('No data available')"
        :total-items="detailItems.length"
        :values="detailItems"
        data-key="id"
      >
        <Column :header="t('Learning object name')">
          <template #body="{ data }">
            <div :class="levelClass(data.level)">
              <div class="font-medium text-gray-90">{{ data.title }}</div>
              <div class="text-xs text-gray-50">{{ data.type }}</div>
            </div>
          </template>
        </Column>
        <Column :header="t('Status')">
          <template #body="{ data }">
            <span
              class="inline-flex rounded-full px-2 py-1 text-xs font-semibold"
              :class="statusClass(latestAttempt(data)?.status)"
            >
              {{ statusLabel(latestAttempt(data)?.status) }}
            </span>
          </template>
        </Column>
        <Column :header="t('Score')">
          <template #body="{ data }">
            {{ formatAttemptScore(latestAttempt(data)) }}
          </template>
        </Column>
        <Column
          v-if="!report.hideTime"
          :header="t('Time')"
        >
          <template #body="{ data }">
            {{ formatDuration(latestAttempt(data)?.timeSeconds) }}
          </template>
        </Column>
        <Column
          v-if="!report.reducedReport"
          :header="t('Details')"
        >
          <template #body="{ data }">
            <div
              v-if="visibleAttempts(data).length"
              class="flex flex-col gap-2"
            >
              <article
                v-for="attempt in visibleAttempts(data)"
                :key="attempt.itemViewId"
                class="rounded-lg border border-gray-20 p-3 text-sm"
              >
                <div class="flex flex-wrap items-center gap-x-4 gap-y-1">
                  <strong>{{ t("Attempt") }} {{ attempt.lpAttempt }}.{{ attempt.itemAttempt }}</strong>
                  <span>{{ statusLabel(attempt.status) }}</span>
                  <span>{{ formatAttemptScore(attempt) }}</span>
                  <span v-if="!report.hideTime">{{ formatDuration(attempt.timeSeconds) }}</span>
                  <span>{{ formatDate(attempt.startTime) }}</span>
                  <BaseButton
                    v-if="attempt.exerciseResultUrl"
                    :label="t('Result')"
                    :to-url="attempt.exerciseResultUrl"
                    icon="information"
                    only-icon
                    size="small"
                    type="primary-text"
                  />
                </div>

                <details
                  v-if="attempt.interactions?.length"
                  class="mt-3"
                >
                  <summary class="cursor-pointer font-semibold text-gray-90">{{ t("Interaction") }}</summary>
                  <div class="mt-2 overflow-x-auto">
                    <table class="w-full text-left text-xs">
                      <thead>
                        <tr class="border-b border-gray-20">
                          <th class="p-2">{{ t("ID") }}</th>
                          <th class="p-2">{{ t("Type") }}</th>
                          <th class="p-2">{{ t("Result") }}</th>
                          <th class="p-2">{{ t("Duration") }}</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr
                          v-for="interaction in attempt.interactions"
                          :key="`${attempt.itemViewId}-${interaction.order}`"
                          class="border-b border-gray-10"
                        >
                          <td class="p-2">{{ interaction.id }}</td>
                          <td class="p-2">{{ interaction.type }}</td>
                          <td class="p-2">{{ interaction.result }}</td>
                          <td class="p-2">{{ interaction.latency || interaction.time || '-' }}</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </details>

                <details
                  v-if="attempt.objectives?.length"
                  class="mt-3"
                >
                  <summary class="cursor-pointer font-semibold text-gray-90">{{ t("Objectives") }}</summary>
                  <div class="mt-2 overflow-x-auto">
                    <table class="w-full text-left text-xs">
                      <thead>
                        <tr class="border-b border-gray-20">
                          <th class="p-2">{{ t("ID") }}</th>
                          <th class="p-2">{{ t("Status") }}</th>
                          <th class="p-2">{{ t("Score") }}</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr
                          v-for="objective in attempt.objectives"
                          :key="`${attempt.itemViewId}-${objective.order}`"
                          class="border-b border-gray-10"
                        >
                          <td class="p-2">{{ objective.id }}</td>
                          <td class="p-2">{{ objective.status }}</td>
                          <td class="p-2">{{ objective.score }}/{{ objective.maxScore }}</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </details>
              </article>
            </div>
            <span v-else>-</span>
          </template>
        </Column>
      </BaseTable>
    </section>
  </section>
</template>
