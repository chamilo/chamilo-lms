<template>
  <div class="space-y-5 pb-8">
    <Message
      v-if="errorMessage"
      severity="error"
      :closable="false"
    >
      {{ errorMessage }}
    </Message>

    <GlobalReportingToolbar
      :show-print="true"
      @print="printReport"
    />

    <section class="border-b border-gray-25 pb-3">
      <h1 class="text-2xl font-semibold text-gray-90">{{ t("Certificate report") }}</h1>
      <p class="mt-1 text-sm text-gray-50">
        {{
          t("Filter and list learner certificates by session, course, date or learner, then export them when needed.")
        }}
      </p>
    </section>

    <div class="grid gap-4 lg:grid-cols-12">
      <section
        class="rounded-xl border border-gray-25 bg-white p-4 shadow-sm"
        :class="report.meta.supportsLearnerFilter ? 'lg:col-span-8' : 'lg:col-span-12'"
      >
        <h2 class="text-lg font-semibold text-gray-90">{{ t("Filter by session and course") }}</h2>
        <p class="mt-1 text-sm text-gray-50">
          {{ t("Choose a session, course and optional date to list the generated certificates.") }}
        </p>

        <div class="mt-4 grid gap-4 md:grid-cols-2 lg:grid-cols-12 lg:items-end">
          <div class="lg:col-span-3">
            <BaseSelect
              id="certificate-session"
              v-model="filters.sessionId"
              :label="t('Course sessions')"
              :options="sessionOptions"
              name="sessionId"
              @change="onSessionChange"
            />
          </div>

          <div class="lg:col-span-3">
            <BaseSelect
              id="certificate-course"
              v-model="filters.courseId"
              :label="t('Courses')"
              :options="availableCourseOptions"
              name="courseId"
            />
          </div>

          <div class="lg:col-span-2">
            <BaseSelect
              id="certificate-month"
              v-model="filters.month"
              :label="t('Month')"
              :options="monthOptions"
              name="month"
            />
          </div>

          <div class="lg:col-span-2">
            <BaseSelect
              id="certificate-year"
              v-model="filters.year"
              :label="t('Year')"
              :options="yearOptions"
              name="year"
            />
          </div>

          <div class="flex gap-2 lg:col-span-2 lg:justify-end">
            <BaseButton
              :label="t('Search')"
              icon="search"
              type="primary"
              @click="searchByCourse"
            />
            <BaseButton
              :label="t('Reset')"
              icon="refresh"
              type="plain"
              @click="resetCourseFilters"
            />
          </div>
        </div>
      </section>

      <section
        v-if="report.meta.supportsLearnerFilter"
        class="rounded-xl border border-gray-25 bg-white p-4 shadow-sm lg:col-span-4"
      >
        <h2 class="text-lg font-semibold text-gray-90">{{ t("Filter by learner") }}</h2>
        <p class="mt-1 text-sm text-gray-50">
          {{ t("Find certificates for a specific learner across sessions and courses.") }}
        </p>

        <div class="mt-4">
          <BaseSelect
            id="certificate-learner"
            v-model="filters.userId"
            :label="t('Learners')"
            :options="learnerOptions"
            name="userId"
            allow-clear
          />
        </div>

        <div class="mt-4 flex justify-end">
          <BaseButton
            :label="t('Search')"
            icon="search"
            type="primary"
            :disabled="filters.userId <= 0"
            @click="searchByLearner"
          />
        </div>
      </section>
    </div>

    <section
      v-if="report.items.length > 0"
      class="rounded-xl border border-gray-25 bg-white shadow-sm"
    >
      <header class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-25 p-4">
        <div>
          <h2 class="text-lg font-semibold text-gray-90">{{ t("List of learner certificates") }}</h2>
          <p class="mt-1 text-sm text-gray-50">
            {{ t("Certificates found") }}: <strong>{{ report.total }}</strong>
          </p>
        </div>

        <BaseButton
          v-if="report.meta.exportAllUrl"
          :label="t('Export all certificates to PDF')"
          icon="file-pdf-box"
          type="primary"
          :to-url="report.meta.exportAllUrl"
        />
      </header>

      <div class="p-4">
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
            field="fullName"
            :header="t('Learner')"
          >
            <template #body="{ data }">
              <strong>{{ data.fullName }}</strong>
            </template>
          </Column>
          <Column
            field="session"
            :header="t('Session')"
          />
          <Column
            field="course"
            :header="t('Course')"
          />
          <Column
            field="createdAt"
            :header="t('Date')"
          >
            <template #body="{ data }">
              <span class="rounded-full bg-gray-10 px-3 py-1 text-sm text-gray-50">
                {{ formatDate(data.createdAt) }}
              </span>
            </template>
          </Column>
          <Column
            field="certificateUrl"
            :header="t('Certificate')"
          >
            <template #body="{ data }">
              <a
                v-if="data.certificateUrl"
                :href="data.certificateUrl"
                class="inline-flex items-center gap-1 text-primary hover:underline"
                target="_blank"
                rel="noopener"
              >
                <BaseIcon icon="file-pdf-box" />
                <span>{{ t("Open certificate") }}</span>
              </a>
              <span
                v-else
                class="text-sm text-gray-50"
              >
                {{ t("Certificate file not available") }}
              </span>
            </template>
          </Column>
        </BaseTable>
      </div>
    </section>

    <Message
      v-else-if="!loading"
      severity="info"
      :closable="false"
    >
      {{ t("No results found") }}
    </Message>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import Column from "primevue/column"
import Message from "primevue/message"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import globalReportingService from "../../services/globalReportingService"
import GlobalReportingToolbar from "./GlobalReportingToolbar.vue"

const route = useRoute()
const router = useRouter()
const { locale, t } = useI18n()
const loading = ref(false)
const errorMessage = ref("")
const routeSyncInProgress = ref(false)

const filters = reactive({
  page: 1,
  itemsPerPage: 20,
  sessionId: 0,
  courseId: 0,
  month: 0,
  year: "",
  userId: 0,
})

const report = reactive({
  total: 0,
  items: [],
  meta: {},
})

const allOption = computed(() => ({ label: t("All"), value: 0 }))
const sessionOptions = computed(() => [
  allOption.value,
  ...(report.meta.sessionOptions || []).map((option) => ({ label: option.label, value: Number(option.id) })),
])
const courseOptions = computed(() => [
  allOption.value,
  ...(report.meta.courseOptions || []).map((option) => ({ label: option.label, value: Number(option.id) })),
])
const learnerOptions = computed(() =>
  (report.meta.learnerOptions || []).map((option) => ({ label: option.label, value: Number(option.id) })),
)
const monthOptions = computed(() => [
  allOption.value,
  ...(report.meta.monthOptions || []).map((option) => ({ label: option.label, value: Number(option.value) })),
])
const yearOptions = computed(() => [
  { label: t("All"), value: "" },
  ...(report.meta.yearOptions || []).map((option) => ({ label: option.label, value: String(option.value) })),
])
const availableCourseOptions = computed(() => {
  if (filters.sessionId <= 0) {
    return courseOptions.value
  }

  const selectedSession = (report.meta.sessionOptions || []).find(
    (option) => Number(option.id) === Number(filters.sessionId),
  )
  const allowedIds = new Set((selectedSession?.courseIds || []).map(Number))

  return [allOption.value, ...courseOptions.value.filter((option) => option.value > 0 && allowedIds.has(option.value))]
})

function hydrateFromRoute() {
  filters.page = Math.max(1, Number(route.query.page || 1))
  filters.itemsPerPage = Math.min(100, Math.max(10, Number(route.query.itemsPerPage || 20)))
  filters.sessionId = Math.max(0, Number(route.query.sessionId || 0))
  filters.courseId = Math.max(0, Number(route.query.courseId || 0))
  filters.month = Math.min(12, Math.max(0, Number(route.query.month || 0)))
  filters.year = String(route.query.year || "")
  filters.userId = Math.max(0, Number(route.query.userId || 0))
}

function requestParams() {
  return {
    page: filters.page,
    itemsPerPage: filters.itemsPerPage,
    sessionId: filters.sessionId || undefined,
    courseId: filters.courseId || undefined,
    month: filters.month || undefined,
    year: filters.year.trim() || undefined,
    userId: filters.userId || undefined,
  }
}

async function loadReport() {
  loading.value = true
  errorMessage.value = ""

  try {
    const response = await globalReportingService.getSection("certificates", requestParams())
    report.total = Number(response.total || 0)
    report.items = response.items || []
    report.meta = response.meta || {}
  } catch (error) {
    errorMessage.value = error?.response?.data?.detail || error?.message || t("An error occurred")
    report.total = 0
    report.items = []
  } finally {
    loading.value = false
  }
}

async function syncRouteAndLoad() {
  const query = Object.fromEntries(
    Object.entries(requestParams()).filter(
      ([, value]) => value !== undefined && value !== null && String(value) !== "",
    ),
  )

  routeSyncInProgress.value = true
  await router.replace({ query })
  routeSyncInProgress.value = false
  await loadReport()
}

async function searchByCourse() {
  filters.page = 1
  filters.userId = 0
  await syncRouteAndLoad()
}

async function resetCourseFilters() {
  filters.page = 1
  filters.sessionId = 0
  filters.courseId = 0
  filters.month = 0
  filters.year = ""
  filters.userId = 0
  await syncRouteAndLoad()
}

async function searchByLearner() {
  if (filters.userId <= 0) {
    return
  }

  filters.page = 1
  filters.sessionId = 0
  filters.courseId = 0
  filters.month = 0
  filters.year = ""
  await syncRouteAndLoad()
}

function onSessionChange() {
  if (!availableCourseOptions.value.some((option) => Number(option.value) === Number(filters.courseId))) {
    filters.courseId = 0
  }
}

async function onPage(event) {
  filters.page = Number(event.page || 0) + 1
  filters.itemsPerPage = Number(event.rows || filters.itemsPerPage)
  await syncRouteAndLoad()
}

function formatDate(value) {
  if (!value) {
    return "-"
  }

  const normalized = String(value).replace(" ", "T")
  const date = new Date(normalized)
  if (Number.isNaN(date.getTime())) {
    return String(value)
  }

  return new Intl.DateTimeFormat(locale.value, {
    dateStyle: "medium",
    timeStyle: "short",
  }).format(date)
}

function printReport() {
  window.print()
}

watch(
  () => route.fullPath,
  async () => {
    if (routeSyncInProgress.value) {
      return
    }

    hydrateFromRoute()
    await loadReport()
  },
)

onMounted(async () => {
  hydrateFromRoute()
  await loadReport()
})
</script>
