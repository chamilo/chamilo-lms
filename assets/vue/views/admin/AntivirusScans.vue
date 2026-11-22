<template>
  <section class="flex flex-col gap-4">
    <div>
      <h2>{{ t("Antivirus scan report") }}</h2>
      <p class="mt-1 text-sm text-gray-50">
        {{ t("Each row represents a scan, not necessarily a separate upload.") }}
      </p>
    </div>

    <div class="grid grid-cols-2 gap-4 xl:grid-cols-4">
      <div
        v-for="card in summaryCards"
        :key="card.label"
        class="flex items-center justify-between gap-2 rounded-lg border border-gray-25 bg-white p-4"
      >
        <div class="min-w-0">
          <p class="text-sm text-gray-70">{{ card.label }}</p>
          <p
            class="mt-1 text-2xl font-semibold tabular-nums"
            :class="card.color"
          >
            {{ summary === null ? "—" : card.count }}
          </p>
        </div>
        <BaseIcon
          :icon="card.icon"
          :class="card.color"
          size="big"
        />
      </div>
    </div>

    <div class="flex flex-col gap-4 rounded-lg border border-gray-25 bg-white p-4">
      <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-3">
          <div class="w-full sm:w-56 [&>.field]:mb-0 [&_.p-select]:w-full">
            <BaseSelect
              id="antivirus-status"
              v-model="status"
              name="status"
              :label="t('Status')"
              :options="statuses"
              @change="refresh"
            />
          </div>
          <BaseButton
            :label="t('Refresh')"
            icon="refresh"
            type="primary-alternative"
            :is-loading="loading"
            @click="load"
          />
        </div>
        <BaseButton
          :label="t('Export CSV')"
          icon="file-delimited-outline"
          type="primary"
          :to-url="exportUrl"
        />
      </div>
      <p class="text-sm text-gray-50">{{ t("Export includes up to 10000 matching scans. Dates are in UTC.") }}</p>
      <p
        v-if="error"
        class="text-sm text-red-700"
        role="alert"
      >
        {{ t("An error occurred") }}
      </p>
      <BaseTable
        :key="tableKey"
        v-model:rows="size"
        :values="items"
        :total-items="total"
        :is-loading="loading"
        :text-for-empty="t('No results found')"
        data-key="id"
        lazy
        scrollable
        @page="onPage"
      >
        <Column
          field="scannedAt"
          :header="t('Date')"
        >
          <template #body="{ data }"
            ><span class="whitespace-nowrap tabular-nums">{{ data.scannedAt }}</span></template
          >
        </Column>
        <Column
          field="filename"
          :header="t('Filename')"
        >
          <template #body="{ data }">
            <span
              class="break-all font-medium"
              :title="data.filename"
              >{{ data.filename }}</span
            >
          </template>
        </Column>
        <Column
          field="status"
          :header="t('Status')"
        >
          <template #body="{ data }">
            <span
              class="inline-flex whitespace-nowrap rounded-full px-2 py-1 text-xs font-medium"
              :class="statusClass(data.status)"
              >{{ statusLabel(data.status) }}</span
            >
          </template>
        </Column>
        <Column
          field="threatName"
          :header="t('Threat')"
        >
          <template #body="{ data }">{{ data.threatName || "—" }}</template>
        </Column>
        <Column
          field="userId"
          :header="t('User ID')"
        >
          <template #body="{ data }">{{ data.userId ?? "—" }}</template>
        </Column>
        <Column
          field="courseId"
          :header="t('Course ID')"
        >
          <template #body="{ data }">{{ data.courseId ?? "—" }}</template>
        </Column>
        <Column
          field="sessionId"
          :header="t('Session ID')"
        >
          <template #body="{ data }">{{ data.sessionId ?? "—" }}</template>
        </Column>
        <Column
          field="source"
          :header="t('Source')"
        >
          <template #body="{ data }">
            <span
              class="break-all text-sm"
              :title="data.source"
              >{{ sourceLabel(data.source) }}</span
            >
          </template>
        </Column>
        <Column
          field="fileSize"
          :header="t('Size')"
        >
          <template #body="{ data }"
            ><span class="whitespace-nowrap tabular-nums">{{ formatSize(data.fileSize) }}</span></template
          >
        </Column>
      </BaseTable>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from "vue"
import { useI18n } from "vue-i18n"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import baseService from "../../services/baseService"

const { t } = useI18n()
const statuses = computed(() => [
  { label: t("All"), value: "all" },
  { label: t("No threat detected"), value: "clean" },
  { label: t("Infected"), value: "infected" },
  { label: t("Scan error"), value: "error" },
])
const status = ref("all")
const items = ref([])
const total = ref(0)
const summary = ref(null)
const page = ref(1)
const size = ref(25)
const loading = ref(false)
const error = ref(false)
const tableKey = ref(0)
const filterStatus = computed(() => (status.value === "all" ? "" : status.value))
const exportUrl = computed(() => `/admin/antivirus-scans-export?status=${encodeURIComponent(filterStatus.value)}`)
const summaryCards = computed(() => [
  {
    label: t("Total scans"),
    count: summary.value ? Object.values(summary.value).reduce((sum, count) => sum + count, 0) : 0,
    icon: "shield-check",
    color: "text-primary",
  },
  { label: t("No threat detected"), count: summary.value?.clean, icon: "check", color: "text-green-700" },
  { label: t("Infected"), count: summary.value?.infected, icon: "alert-circle", color: "text-red-700" },
  { label: t("Scan error"), count: summary.value?.error, icon: "alert", color: "text-amber-700" },
])
let sequence = 0

function statusLabel(value) {
  return t(value === "clean" ? "No threat detected" : value === "infected" ? "Infected" : "Scan error")
}

function statusClass(value) {
  return value === "clean"
    ? "bg-green-100 text-green-700"
    : value === "infected"
      ? "bg-red-100 text-red-700"
      : "bg-yellow-100 text-yellow-800"
}

function sourceLabel(value) {
  const source = value.split(":", 1)[0]
  const labels = {
    http: "HTTP upload",
    resource: "Resource storage",
    asset: "Asset storage",
    "document-zip": "ZIP entry",
    scorm: "SCORM package",
    "scorm-entry": "SCORM entry",
  }
  return Object.hasOwn(labels, source) ? t(labels[source]) : source
}

function formatSize(value) {
  if (value === null || value === undefined) return "—"
  if (value < 1024) return `${value} B`
  if (value < 1024 ** 2) return `${(value / 1024).toFixed(1)} KiB`
  return `${(value / 1024 ** 2).toFixed(1)} MiB`
}

async function load() {
  const current = ++sequence
  loading.value = true
  error.value = false
  try {
    const data = await baseService.get("/admin/antivirus-scans-data", {
      status: filterStatus.value,
      page: page.value,
      size: size.value,
    })
    if (current === sequence) {
      items.value = data.items
      total.value = data.total
      summary.value = data.summary
    }
  } catch {
    if (current === sequence) {
      items.value = []
      total.value = 0
      summary.value = null
      error.value = true
    }
  } finally {
    if (current === sequence) loading.value = false
  }
}

function refresh() {
  page.value = 1
  tableKey.value++
  load()
}

function onPage(event) {
  page.value = event.page + 1
  size.value = event.rows
  load()
}

onMounted(load)
</script>
