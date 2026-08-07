<template>
  <div class="space-y-5 pb-8">
    <Message
      v-if="errorMessage"
      severity="error"
      :closable="false"
    >
      {{ errorMessage }}
    </Message>

    <GlobalReportingToolbar :show-print="false" />

    <nav
      class="no-print flex flex-nowrap items-center gap-1 overflow-x-auto rounded-3xl border border-gray-25 bg-white p-1"
      :aria-label="t('Reporting')"
    >
      <router-link
        v-for="tab in trackingTabs"
        :key="tab.key"
        :to="trackingTabRoute(tab.key)"
        :title="t(tab.label)"
        :class="[
          'inline-flex min-h-10 shrink-0 items-center gap-2 rounded-2xl px-4 py-2 text-sm transition',
          activeView === tab.key ? 'bg-primary text-white shadow-sm' : 'text-gray-700 hover:bg-gray-15',
        ]"
      >
        <BaseIcon :icon="tab.icon" />
        <span>{{ t(tab.label) }}</span>
      </router-link>
    </nav>

    <template v-if="activeView === 'exams'">
      <section class="no-print rounded-xl border border-gray-25 bg-white p-4 shadow-sm">
        <div class="flex flex-col gap-3 md:flex-row md:items-end">
          <div class="w-full md:max-w-44">
            <BaseInputText
              id="global-reporting-exam-score"
              v-model="scoreInput"
              :label="t('Percentage')"
              inputmode="numeric"
              name="score"
              @keyup.enter="applyExamFilter"
            />
          </div>

          <div class="flex flex-wrap items-center gap-2">
            <BaseButton
              :label="t('Filter')"
              icon="filter"
              type="primary"
              @click="applyExamFilter"
            />
          </div>

          <div class="flex items-center gap-2 md:ml-auto">
            <BaseButton
              :label="t('Export to XLS')"
              icon="file-excel"
              only-icon
              type="primary-alternative"
              :is-loading="exporting"
              @click="downloadXlsx"
            />
            <BaseButton
              :label="t('Print')"
              icon="file-text"
              only-icon
              type="primary-alternative"
              @click="printReport"
            />
          </div>
        </div>

        <p class="mt-3 font-semibold">{{ t("Filtering with score %s", [scoreThreshold]) }}%</p>
      </section>

      <section class="rounded-xl border border-gray-25 bg-white shadow-sm">
        <header class="border-b border-gray-25 p-4">
          <h1 class="text-xl font-semibold">{{ t("Exam tracking") }}</h1>
        </header>

        <div class="overflow-x-auto p-4">
          <BaseTable
            v-model:rows="filters.itemsPerPage"
            :values="report.items"
            :total-items="report.total"
            :is-loading="loading"
            :lazy="true"
            data-key="id"
            :text-for-empty="t('No results found')"
            @page="onPage"
          >
            <Column
              v-for="column in report.columns"
              :key="column.key"
              :field="column.key"
              :body-class="column.type === 'pass-threshold' ? 'bg-yellow-100' : ''"
            >
              <template #header>
                <span v-if="column.type === 'pass-threshold'"> {{ t(column.label, [scoreThreshold]) }}% </span>
                <span v-else>{{ t(column.label) }}</span>
              </template>

              <template #body="{ data }">
                <span v-if="column.type === 'exam-title'">
                  {{ data.empty ? t(String(data.testTitle || data.test || "")) : data.testTitle }}
                  <template v-if="data.sessionTitle">
                    <BaseIcon
                      icon="session-star"
                      size="small"
                      class="ml-1 inline-flex"
                    />
                    ({{ data.sessionTitle }})
                  </template>
                </span>
                <span v-else-if="column.type === 'nullable-number' || column.type === 'pass-threshold'">
                  {{ data[column.key] === null || data[column.key] === undefined ? "" : data[column.key] }}
                </span>
                <span v-else>{{ data[column.key] }}</span>
              </template>
            </Column>
          </BaseTable>
        </div>
      </section>
    </template>

    <template v-else>
      <section class="no-print rounded-xl border border-gray-25 bg-white p-4 shadow-sm">
        <header class="mb-4 border-b border-gray-25 pb-3">
          <h1 class="text-xl font-semibold">{{ t(activeTab.label) }}</h1>
        </header>

        <div class="grid gap-4 lg:grid-cols-12 lg:items-end">
          <div class="lg:col-span-5">
            <BaseSelect
              id="global-reporting-tracking-course"
              v-model="selectedCourseId"
              :label="t('Course')"
              :options="courseOptions"
              name="courseId"
              @change="onCourseChange"
            />
          </div>

          <div class="lg:col-span-5">
            <BaseSelect
              id="global-reporting-tracking-session"
              v-model="selectedSessionId"
              :label="t('Session')"
              :options="sessionOptions"
              :allow-clear="true"
              name="sessionId"
            />
          </div>

          <div class="flex items-center gap-2 lg:col-span-2 lg:justify-end">
            <BaseButton
              :label="t('Open')"
              icon="eye"
              type="primary"
              :disabled="!selectedCourseId"
              @click="openCourseReport"
            />
          </div>
        </div>
      </section>
    </template>
  </div>
</template>

<script setup>
import Column from "primevue/column"
import Message from "primevue/message"
import { computed, onMounted, reactive, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import globalReportingService from "../../services/globalReportingService"
import GlobalReportingToolbar from "./GlobalReportingToolbar.vue"

const route = useRoute()
const router = useRouter()
const { t } = useI18n()
const loading = ref(false)
const exporting = ref(false)
const errorMessage = ref("")
const scoreInput = ref("70")
const selectedCourseId = ref(0)
const selectedSessionId = ref(0)
const loadedExamRequestKey = ref("")
const report = reactive({
  total: 0,
  page: 1,
  itemsPerPage: 20,
  columns: [],
  items: [],
  meta: {},
})
const filters = reactive({
  page: 1,
  itemsPerPage: 20,
  score: 70,
})

const trackingTabs = [
  { key: "groups", label: "Group report", icon: "join-group" },
  { key: "resources", label: "Report on resources", icon: "folder-open" },
  { key: "course", label: "Course report", icon: "courses" },
  { key: "exams", label: "Exam tracking", icon: "gradebook" },
  { key: "audit", label: "Audit report", icon: "shield-check" },
]
const validViews = trackingTabs.map((tab) => tab.key)
const activeView = computed(() => {
  const view = String(route.query.view || "exams")

  return validViews.includes(view) ? view : "exams"
})
const activeTab = computed(() => trackingTabs.find((tab) => tab.key === activeView.value) || trackingTabs[3])
const scoreThreshold = computed(() => Number(report.meta.scoreThreshold ?? filters.score))
const courseOptions = computed(() =>
  (report.meta.courseOptions || []).map((option) => ({ label: option.label, value: Number(option.id) })),
)
const sessionOptions = computed(() => {
  const courseId = Number(selectedCourseId.value || 0)

  return (report.meta.sessionOptions || [])
    .filter((option) => courseId <= 0 || (option.courseIds || []).map(Number).includes(courseId))
    .map((option) => ({ label: option.label, value: Number(option.id) }))
})

function examRequestKey() {
  return `${filters.page}|${filters.itemsPerPage}|${filters.score}`
}

function hydrateFromRoute() {
  filters.page = Math.max(1, Number(route.query.page || 1))
  filters.itemsPerPage = Math.min(100, Math.max(10, Number(route.query.itemsPerPage || 20)))
  filters.score = Math.min(100, Math.max(0, Number(route.query.score ?? 70)))
  scoreInput.value = String(filters.score)
  selectedCourseId.value = Number(route.query.courseId || 0)
  selectedSessionId.value = Number(route.query.sessionId || 0)
}

function trackingTabRoute(view) {
  return {
    name: "GlobalReportingExams",
    query: {
      view,
      courseId: selectedCourseId.value || undefined,
      sessionId: selectedSessionId.value || undefined,
      score: view === "exams" ? filters.score : undefined,
    },
  }
}

async function loadReport() {
  loading.value = true
  errorMessage.value = ""

  try {
    const response = await globalReportingService.getSection("exams", {
      page: filters.page,
      itemsPerPage: filters.itemsPerPage,
      score: filters.score,
    })
    Object.assign(report, {
      total: Number(response.total || 0),
      page: Number(response.page || 1),
      itemsPerPage: Number(response.itemsPerPage || filters.itemsPerPage),
      columns: response.columns || [],
      items: response.items || [],
      meta: response.meta || {},
    })
    loadedExamRequestKey.value = examRequestKey()
  } catch (error) {
    errorMessage.value = error?.response?.data?.detail || error?.message || t("An error occurred")
  } finally {
    loading.value = false
  }
}

async function syncExamRouteAndLoad() {
  await router.replace({
    name: "GlobalReportingExams",
    query: {
      view: "exams",
      page: filters.page,
      itemsPerPage: filters.itemsPerPage,
      score: filters.score,
      courseId: selectedCourseId.value || undefined,
      sessionId: selectedSessionId.value || undefined,
    },
  })
  await loadReport()
}

async function applyExamFilter() {
  filters.page = 1
  filters.score = Math.min(100, Math.max(0, Number(scoreInput.value || 0)))
  scoreInput.value = String(filters.score)
  await syncExamRouteAndLoad()
}

async function onPage(event) {
  filters.page = Number(event.page || 0) + 1
  filters.itemsPerPage = Number(event.rows || filters.itemsPerPage)
  await syncExamRouteAndLoad()
}

function onCourseChange() {
  selectedSessionId.value = 0
}

function openCourseReport() {
  const routeNames = {
    groups: "CourseReportingGroups",
    resources: "CourseReportingResources",
    course: "CourseReportingTools",
    audit: "CourseReportingAudit",
  }
  const routeName = routeNames[activeView.value]
  if (!routeName || !selectedCourseId.value) {
    return
  }

  router.push({
    name: routeName,
    query: {
      cid: selectedCourseId.value,
      sid: selectedSessionId.value || 0,
      gid: 0,
      returnTo: "global-reporting-tracking",
      returnView: activeView.value,
      returnCourseId: selectedCourseId.value,
      returnSessionId: selectedSessionId.value || 0,
      returnScore: filters.score,
    },
  })
}

async function downloadXlsx() {
  exporting.value = true
  errorMessage.value = ""

  try {
    const response = await globalReportingService.downloadSection("exams", "xlsx", {
      score: filters.score,
    })
    const blob = response.data instanceof Blob ? response.data : new Blob([response.data])
    const url = URL.createObjectURL(blob)
    const link = document.createElement("a")
    link.href = url
    link.download = "global-reporting-exams.xlsx"
    document.body.appendChild(link)
    link.click()
    link.remove()
    URL.revokeObjectURL(url)
  } catch (error) {
    errorMessage.value = error?.response?.data?.detail || error?.message || t("An error occurred")
  } finally {
    exporting.value = false
  }
}

function printReport() {
  window.print()
}

watch(
  () => route.query,
  async () => {
    hydrateFromRoute()
    if (
      !report.meta.courseOptions ||
      (activeView.value === "exams" && loadedExamRequestKey.value !== examRequestKey())
    ) {
      await loadReport()
    }
  },
)

onMounted(async () => {
  hydrateFromRoute()
  await loadReport()
})
</script>
