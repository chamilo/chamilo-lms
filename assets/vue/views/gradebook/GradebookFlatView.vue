<template>
  <section class="space-y-6">
    <div
      v-if="errorMessage"
      class="rounded-xl border border-danger/30 bg-danger/10 p-4 text-sm text-danger"
      role="alert"
    >
      {{ errorMessage }}
    </div>

    <div class="flex flex-wrap items-center gap-1 rounded-xl border border-gray-20 bg-white px-2 py-1 shadow-sm">
      <BaseButton
        :label="t('Back')"
        :route="gradebookRoute"
        icon="back"
        only-icon
        size="normal"
        type="primary-text"
      />
      <BaseButton
        v-if="report?.totalItems > 0"
        :label="t('Export CSV')"
        :to-url="gradebookService.buildExportUrl('flat', 'csv', exportContextParams)"
        icon="file-delimited-outline"
        only-icon
        size="normal"
        type="primary-text"
      />
      <BaseButton
        v-if="report?.totalItems > 0"
        :label="t('Export Excel')"
        :to-url="gradebookService.buildExportUrl('flat', 'xls', exportContextParams)"
        icon="file-excel"
        only-icon
        size="normal"
        type="primary-text"
      />
      <BaseButton
        v-if="report?.totalItems > 0"
        :label="t('Export')"
        :to-url="gradebookService.buildExportUrl('flat', 'docx', exportContextParams)"
        icon="file-text"
        only-icon
        size="normal"
        type="primary-text"
      />
      <BaseButton
        v-if="report?.totalItems > 0"
        :label="t('Print')"
        :route="printRoute"
        icon="export"
        only-icon
        size="normal"
        type="primary-text"
      />
      <BaseButton
        v-if="report?.totalItems > 0 && report?.settings?.hidePdfReportButton !== true"
        :label="t('Export to PDF')"
        :to-url="gradebookService.buildExportUrl('flat', 'pdf', exportContextParams)"
        icon="file-pdf"
        only-icon
        size="normal"
        type="primary-text"
      />
      <BaseButton
        v-if="report?.settings?.hideGraph !== true"
        :label="t('Graphical view')"
        :route="graphRoute"
        icon="graph"
        only-icon
        size="normal"
        type="primary-text"
      />
    </div>

    <div class="rounded-xl border border-gray-20 bg-white p-4 shadow-sm">
      <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <h1 class="text-xl font-semibold text-gray-90">
          {{ t("List view") }}
        </h1>

        <form
          class="flex w-full flex-col gap-2 sm:flex-row sm:items-end md:w-auto [&_.field]:mb-0"
          @submit.prevent="applySearch"
        >
          <BaseInputText
            id="gradebook-flatview-search"
            v-model="searchForm"
            :label="t('Search')"
            name="gradebook_flatview_search"
          />
          <div class="flex items-center gap-2">
            <BaseButton
              :is-loading="isLoading"
              :label="t('Search')"
              icon="search"
              :is-submit="true"
              type="primary"
            />
            <BaseButton
              v-if="search"
              :label="t('Clear search')"
              icon="close"
              type="secondary"
              @click="clearSearch"
            />
          </div>
        </form>
      </div>
    </div>

    <BaseTable
      v-model:rows="itemsPerPage"
      :is-loading="isLoading"
      :lazy="true"
      :text-for-empty="t('No results found')"
      :total-items="report?.totalItems || 0"
      :values="tableRows"
      data-key="id"
      @page="handlePage"
      @sort="handleSort"
    >
      <Column
        :header="t('First name')"
        field="firstName"
        sortable
      >
        <template #body="{ data }">
          <RouterLink
            v-if="data.id"
            class="font-semibold text-primary hover:underline"
            :to="buildLearnerRoute(data.id)"
          >
            {{ data.firstName || "-" }}
          </RouterLink>
          <span v-else>{{ data.firstName || "-" }}</span>
        </template>
      </Column>

      <Column
        :header="t('Last name')"
        field="lastName"
        sortable
      >
        <template #body="{ data }">
          <RouterLink
            v-if="data.id"
            class="font-semibold text-primary hover:underline"
            :to="buildLearnerRoute(data.id)"
          >
            {{ data.lastName || "-" }}
          </RouterLink>
          <span v-else>{{ data.lastName || "-" }}</span>
        </template>
      </Column>

      <Column
        :header="t('Username')"
        field="username"
        sortable
      />

      <Column
        v-for="field in report?.extraFieldColumns || []"
        :key="`extra-${field.variable}`"
        :header="field.label"
      >
        <template #body="{ data }">
          {{ data.extraFields?.[field.variable] || "-" }}
        </template>
      </Column>

      <Column
        v-for="column in report?.columns || []"
        :key="column.key"
      >
        <template #header>
          <div class="min-w-32 text-center font-semibold text-gray-90">
            {{ column.title }}
            <span class="font-normal">{{ formatRelativeWeight(column.relativeWeight) }}</span>
          </div>
        </template>
        <template #body="{ data }">
          <div class="min-w-28 text-center text-sm">
            {{ formatScore(data.scores?.[column.key]) }}
          </div>
        </template>
      </Column>

      <Column :header="t('Total')">
        <template #body="{ data }">
          <span class="font-semibold text-gray-90">
            {{ formatFlatTotal(data.total) }}
          </span>
        </template>
      </Column>

      <Column
        v-if="report?.settings?.customScoreStandalone"
        :header="t('Ranking')"
      >
        <template #body="{ data }">
          {{ data.customScore || "-" }}
        </template>
      </Column>
    </BaseTable>

    <div
      v-if="report?.settings?.hideGraph !== true && report?.totalItems > 0"
      class="space-y-4"
    >
      <div
        v-if="isGraphLoading"
        class="py-4 text-center text-sm text-gray-600"
      >
        {{ t("Loading...") }}
      </div>

      <div
        v-else-if="graphErrorMessage"
        class="py-4 text-center text-sm text-danger"
        role="alert"
      >
        {{ graphErrorMessage }}
      </div>

      <div
        v-else-if="graph && !graph.enabled"
        class="py-4 text-center text-sm text-gray-700"
      >
        {{ t("To view graph score rule must be enabled") }}
      </div>

      <div
        v-else-if="graph?.resources?.length"
        class="grid gap-4 lg:grid-cols-2"
      >
        <article
          v-for="resource in graph.resources"
          :key="resource.key"
          class="rounded-xl border border-gray-20 bg-white p-4 shadow-sm"
        >
          <h2 class="mb-4 text-center font-semibold text-gray-90">{{ resource.title }}</h2>
          <div class="space-y-3">
            <div
              v-for="bucket in resource.distribution"
              :key="bucket.label"
              class="grid grid-cols-[minmax(6rem,auto)_1fr_auto] items-center gap-3"
            >
              <span class="truncate text-sm text-gray-700">{{ bucket.label }}</span>
              <div class="h-4 overflow-hidden rounded bg-gray-100">
                <div
                  class="h-full rounded bg-primary"
                  :style="{ width: `${bucket.widthPercent}%` }"
                />
              </div>
              <span class="min-w-8 text-right text-sm font-semibold text-gray-90">{{ bucket.count }}</span>
            </div>
          </div>
        </article>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import gradebookService from "../../services/gradebookService"

const { t } = useI18n()
const route = useRoute()

const report = ref(null)
const graph = ref(null)
const isLoading = ref(false)
const isGraphLoading = ref(false)
const errorMessage = ref("")
const graphErrorMessage = ref("")
const page = ref(1)
const itemsPerPage = ref(20)
const search = ref("")
const searchForm = ref("")
const sortBy = ref("fullName")
const sortDirection = ref("asc")

const tableRows = computed(() =>
  (report.value?.rows || []).map((row) => ({
    ...row,
    id: row.user?.id,
    fullName: row.user?.fullName || "",
    firstName: row.user?.firstName || "",
    lastName: row.user?.lastName || "",
    username: row.user?.username || "",
  })),
)

const reportQuery = computed(() => {
  const query = { ...route.query }
  delete query.search
  delete query.page

  return query
})

const gradebookRoute = computed(() => ({
  name: "GradebookList",
  params: { node: route.params.node },
  query: reportQuery.value,
}))

const exportContextParams = computed(() => {
  const params = {
    cid: getQueryValue(route.query.cid),
    sid: getQueryValue(route.query.sid),
    gid: getQueryValue(route.query.gid),
    node: route.params.node,
    search: search.value,
    sortBy: sortBy.value,
    sortDirection: sortDirection.value,
  }
  const categoryId = Number(getQueryValue(route.query.categoryId) || 0)
  if (categoryId > 0) {
    params.categoryId = categoryId
  }

  return params
})

const printRoute = computed(() => ({
  name: "GradebookPrint",
  params: { node: route.params.node, scope: "flat" },
  query: exportContextParams.value,
}))

const graphRoute = computed(() => ({
  name: "GradebookGraph",
  params: { node: route.params.node },
  query: reportQuery.value,
}))

function getQueryValue(value) {
  return Array.isArray(value) ? value[0] : value
}

function getContextParams() {
  const params = {
    cid: getQueryValue(route.query.cid),
    sid: getQueryValue(route.query.sid),
    gid: getQueryValue(route.query.gid),
    node: route.params.node,
    page: page.value,
    itemsPerPage: itemsPerPage.value,
    search: search.value,
    sortBy: sortBy.value,
    sortDirection: sortDirection.value,
  }
  const categoryId = Number(getQueryValue(route.query.categoryId) || 0)
  if (categoryId > 0) {
    params.categoryId = categoryId
  }

  return params
}

function getGraphParams() {
  const params = {
    cid: getQueryValue(route.query.cid),
    sid: getQueryValue(route.query.sid),
    gid: getQueryValue(route.query.gid),
    node: route.params.node,
    search: search.value,
  }
  const categoryId = Number(getQueryValue(route.query.categoryId) || 0)
  if (categoryId > 0) {
    params.categoryId = categoryId
  }

  return params
}

function buildLearnerRoute(userId) {
  return {
    name: "GradebookLearnerReport",
    params: { node: route.params.node, userId },
    query: reportQuery.value,
  }
}

async function loadReport() {
  isLoading.value = true
  errorMessage.value = ""

  try {
    report.value = await gradebookService.getReport(getContextParams())
    if (report.value?.settings?.hideGraph === true || report.value?.totalItems <= 0) {
      graph.value = null
      graphErrorMessage.value = ""
    } else {
      void loadGraph()
    }
  } catch (error) {
    console.error("Failed to load Gradebook list view:", error)
    errorMessage.value = error?.response?.data?.detail || error?.message || t("An error occurred")
  } finally {
    isLoading.value = false
  }
}

async function loadGraph() {
  isGraphLoading.value = true
  graph.value = null
  graphErrorMessage.value = ""

  try {
    graph.value = await gradebookService.getGraph(getGraphParams())
  } catch (error) {
    console.error("Failed to load Gradebook flat-view graph:", error)
    graph.value = null
    graphErrorMessage.value = error?.response?.data?.detail || error?.message || t("An error occurred")
  } finally {
    isGraphLoading.value = false
  }
}

function applySearch() {
  search.value = searchForm.value.trim()
  page.value = 1
  loadReport()
}

function clearSearch() {
  searchForm.value = ""
  search.value = ""
  page.value = 1
  loadReport()
}

function handlePage(event) {
  itemsPerPage.value = Number(event.rows || itemsPerPage.value)
  page.value = Math.floor(Number(event.first || 0) / itemsPerPage.value) + 1
  loadReport()
}

function handleSort(event) {
  const allowed = ["firstName", "lastName", "username"]
  sortBy.value = allowed.includes(event.sortField) ? event.sortField : "fullName"
  sortDirection.value = Number(event.sortOrder) < 0 ? "desc" : "asc"
  page.value = 1
  loadReport()
}

function formatNumber(value) {
  if (value === null || value === undefined || value === "") {
    return "-"
  }

  const decimals = Number(report.value?.settings?.numberDecimals ?? 2)
  const number = Number(value)

  return Number.isFinite(number) ? number.toFixed(Math.max(0, decimals)) : String(value)
}

function formatScore(result) {
  if (!result || result.hasResult !== true) {
    return "-"
  }

  if (typeof result.display === "string" && result.display !== "") {
    return result.display
  }

  if (result.score === null || result.score === undefined) {
    return result.percentage === null || result.percentage === undefined
      ? "-"
      : `${formatNumber(result.percentage)}%`
  }

  return formatNumber(result.score)
}

function formatRelativeWeight(value) {
  const number = Number(value)
  if (!Number.isFinite(number)) {
    return ""
  }

  const rounded = Math.round(number * 10) / 10
  const text = Number.isInteger(rounded) ? String(rounded) : rounded.toFixed(1)

  return `${text} %`
}

function formatFlatTotal(result) {
  if (!result || result.hasResult !== true) {
    return "-"
  }
  if (result.percentage === null || result.percentage === undefined) {
    return "-"
  }

  const percentage = `${formatNumber(result.percentage)} %`
  const score = Number(result.score)
  const maximum = Number(result.maxScore)
  if (!Number.isFinite(score) || !Number.isFinite(maximum)) {
    return percentage
  }

  return `${percentage} (${formatNumber(score)} / ${formatNumber(maximum)})`
}

onMounted(loadReport)
watch(
  () => [route.query.cid, route.query.sid, route.query.gid, route.query.categoryId, route.params.node],
  () => {
    page.value = 1
    loadReport()
  },
)
</script>
