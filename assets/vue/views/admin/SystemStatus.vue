<template>
  <section class="space-y-6">
    <div class="rounded-3xl border border-gray-20 bg-white p-6 shadow-sm">
      <div class="flex items-center gap-3">
        <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-support-1 text-primary">
          <i class="mdi mdi-heart-pulse text-2xl" />
        </span>
        <div>
          <h1 class="text-2xl font-semibold tracking-tight text-gray-90">
            {{ t("System status") }}
          </h1>
          <p class="mt-1 max-w-3xl text-body-2 text-gray-50">
            {{ activeSectionInfo }}
          </p>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
      <button
        v-for="section in sections"
        :key="section.key"
        type="button"
        class="group block rounded-2xl bg-white p-4 text-left shadow-sm transition hover:shadow-md"
        :class="
          section.key === currentSection
            ? 'ring-2 ring-primary/80 bg-primary/5'
            : 'ring-1 ring-gray-200 hover:ring-gray-300'
        "
        @click="selectSection(section.key)"
      >
        <div class="flex items-center gap-3">
          <div
            class="flex h-12 w-12 items-center justify-center rounded-xl bg-gray-10 group-hover:bg-gray-30"
          >
            <i
              class="mdi text-2xl"
              :class="[
                section.icon,
                section.key === currentSection ? 'text-primary' : 'text-gray-70',
              ]"
            />
          </div>
          <div class="min-w-0">
            <div class="flex items-center gap-2">
              <h3
                class="truncate text-sm font-semibold"
                :class="section.key === currentSection ? 'text-primary' : 'text-gray-90'"
              >
                {{ section.label }}
              </h3>
              <span
                v-if="section.key === currentSection"
                class="ml-auto rounded-full bg-primary/10 px-2 py-0.5 text-xs font-medium text-primary"
              >
                {{ t("Active") }}
              </span>
            </div>
            <p class="mt-0.5 line-clamp-2 text-xs text-gray-50">
              {{ section.info }}
            </p>
          </div>
        </div>
      </button>
    </div>

    <div
      v-if="activeSectionInfo"
      class="rounded-xl border border-blue-200 bg-blue-50 p-4 text-blue-800"
    >
      <div class="flex items-start gap-3">
        <BaseIcon
          icon="information"
          size="small"
        />
        <p class="text-sm">
          {{ activeSectionInfo }}
        </p>
      </div>
    </div>

    <div
      v-if="isLoading"
      class="rounded-2xl border border-gray-20 bg-white p-8 text-center text-gray-50 shadow-sm"
    >
      {{ t("Loading") }}...
    </div>

    <div
      v-else-if="errorMessage"
      class="rounded-2xl border border-red-200 bg-red-50 p-4 text-red-800"
    >
      {{ errorMessage }}
    </div>

    <template v-else>
      <div
        v-if="currentSection === 'php'"
        class="space-y-4"
      >
        <div class="rounded-2xl border border-gray-20 bg-white shadow-sm">
          <button
            type="button"
            name="php-cache-toggle"
            class="flex w-full items-center justify-between gap-3 rounded-2xl px-5 py-4 text-left transition hover:bg-support-2"
            :aria-expanded="phpCacheExpanded ? 'true' : 'false'"
            aria-controls="php-cache-panel"
            @click="togglePhpCache"
          >
            <div class="flex min-w-0 items-center gap-3">
              <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-support-1 text-primary">
                <i class="mdi mdi-memory text-xl" />
              </span>
              <div class="min-w-0">
                <h2 class="text-xl font-semibold text-gray-90">
                  {{ t("PHP cache") }}
                </h2>
                <p class="mt-0.5 text-caption text-gray-50">
                  {{
                    phpCacheExpanded
                      ? t("Live OPcache and APCu diagnostics")
                      : t("Click to show OPcache and APCu diagnostics")
                  }}
                </p>
              </div>
            </div>
            <i
              class="mdi text-2xl text-gray-50"
              :class="phpCacheExpanded ? 'mdi-chevron-up' : 'mdi-chevron-down'"
            />
          </button>

          <div
            v-if="phpCacheExpanded"
            id="php-cache-panel"
            class="space-y-4 border-t border-gray-20 px-5 pb-5 pt-4"
          >
            <div class="flex flex-wrap items-center justify-end gap-3">
              <BaseCheckbox
                id="php-cache-auto-refresh"
                v-model="cacheAutoRefresh"
                name="php_cache_auto_refresh"
                :label="t('Auto-refresh every 5 seconds')"
              />
              <span
                v-if="cacheFetchedAt"
                class="text-caption text-gray-50"
              >
                {{ t("Last updated") }}: {{ formatLastVisit(cacheFetchedAt) }}
              </span>
            </div>

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
          <div class="rounded-2xl border border-gray-20 bg-white p-5 shadow-sm">
            <div class="mb-4 flex items-start justify-between gap-3">
              <div class="flex items-center gap-3">
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-support-1 text-primary">
                  <i class="mdi mdi-memory text-xl" />
                </span>
                <div>
                  <h3 class="text-body-1 font-semibold text-gray-90">
                    OPcache
                  </h3>
                  <p class="text-caption text-gray-50">
                    {{ cacheStatusLabel(cacheData?.opcache) }}
                  </p>
                </div>
              </div>
              <BaseButton
                :label="t('Refresh')"
                icon="refresh"
                type="primary-text"
                only-icon
                size="small"
                :is-loading="cacheLoading"
                @click="loadCacheData"
              />
            </div>
            <div
              v-if="cacheLoading && !cacheData"
              class="text-caption text-gray-50"
            >
              {{ t("Loading") }}...
            </div>
            <template v-else-if="cacheData?.opcache?.enabled">
              <div
                v-if="opcacheMemoryBar"
                class="mb-4"
              >
                <div class="mb-1 flex items-center justify-between gap-2 text-caption">
                  <span class="font-semibold text-gray-70">
                    {{ t("Memory used") }}
                  </span>
                  <span class="font-mono text-gray-90">
                    {{ formatBytes(opcacheMemoryBar.used) }}
                    /
                    {{ formatBytes(opcacheMemoryBar.total) }}
                    ({{ formatPercent(opcacheMemoryBar.percent) }})
                  </span>
                </div>
                <div
                  class="h-3 w-full overflow-hidden rounded-full bg-gray-20"
                  role="progressbar"
                  :aria-valuenow="opcacheMemoryBar.percent"
                  aria-valuemin="0"
                  aria-valuemax="100"
                  :aria-label="t('Memory used')"
                >
                  <div
                    class="h-full rounded-full transition-all duration-300"
                    :style="memoryBarFillStyle(opcacheMemoryBar.percent)"
                  ></div>
                </div>
              </div>
              <dl class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div
                  v-for="metric in opcacheMetrics"
                  :key="metric.label"
                  class="rounded-xl border border-gray-20 bg-support-2 p-3"
                >
                  <dt class="text-caption font-semibold uppercase tracking-wide text-gray-50">
                    {{ metric.label }}
                  </dt>
                  <dd class="mt-1 font-mono text-body-2 text-gray-90">
                    {{ metric.value }}
                  </dd>
                </div>
              </dl>
            </template>
            <p
              v-else
              class="text-body-2 text-gray-50"
            >
              {{
                cacheData?.opcache?.available
                  ? t("OPcache is installed but not enabled.")
                  : t("OPcache extension is not available on this server.")
              }}
            </p>
          </div>

          <div class="rounded-2xl border border-gray-20 bg-white p-5 shadow-sm">
            <div class="mb-4 flex items-start justify-between gap-3">
              <div class="flex items-center gap-3">
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-support-1 text-primary">
                  <i class="mdi mdi-database-outline text-xl" />
                </span>
                <div>
                  <h3 class="text-body-1 font-semibold text-gray-90">
                    APCu
                  </h3>
                  <p class="text-caption text-gray-50">
                    {{ cacheStatusLabel(cacheData?.apcu) }}
                  </p>
                </div>
              </div>
              <BaseButton
                :label="t('Refresh')"
                icon="refresh"
                type="primary-text"
                only-icon
                size="small"
                :is-loading="cacheLoading"
                @click="loadCacheData"
              />
            </div>
            <div
              v-if="cacheLoading && !cacheData"
              class="text-caption text-gray-50"
            >
              {{ t("Loading") }}...
            </div>
            <template v-else-if="cacheData?.apcu?.enabled">
              <div
                v-if="apcuMemoryBar"
                class="mb-4"
              >
                <div class="mb-1 flex items-center justify-between gap-2 text-caption">
                  <span class="font-semibold text-gray-70">
                    {{ t("Memory used") }}
                  </span>
                  <span class="font-mono text-gray-90">
                    {{ formatBytes(apcuMemoryBar.used) }}
                    /
                    {{ formatBytes(apcuMemoryBar.total) }}
                    ({{ formatPercent(apcuMemoryBar.percent) }})
                  </span>
                </div>
                <div
                  class="h-3 w-full overflow-hidden rounded-full bg-gray-20"
                  role="progressbar"
                  :aria-valuenow="apcuMemoryBar.percent"
                  aria-valuemin="0"
                  aria-valuemax="100"
                  :aria-label="t('Memory used')"
                >
                  <div
                    class="h-full rounded-full transition-all duration-300"
                    :style="memoryBarFillStyle(apcuMemoryBar.percent)"
                  ></div>
                </div>
              </div>
              <dl class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div
                  v-for="metric in apcuMetrics"
                  :key="metric.label"
                  class="rounded-xl border border-gray-20 bg-support-2 p-3"
                >
                  <dt class="text-caption font-semibold uppercase tracking-wide text-gray-50">
                    {{ metric.label }}
                  </dt>
                  <dd class="mt-1 font-mono text-body-2 text-gray-90">
                    {{ metric.value }}
                  </dd>
                </div>
              </dl>
            </template>
            <p
              v-else
              class="text-body-2 text-gray-50"
            >
              {{
                cacheData?.apcu?.available
                  ? t("APCu is installed but not enabled.")
                  : t("APCu extension is not available on this server.")
              }}
            </p>
          </div>
            </div>
          </div>
        </div>
      </div>

      <BaseTable
        v-if="rowType === 'generic'"
        :values="rows"
        data-key="title"
      >
        <Column :header="t('Status')">
          <template #body="{ data }">
            <span
              class="inline-flex items-center gap-1"
              :title="data.status"
            >
              <i
                class="mdi text-xl"
                :class="statusIconClass(data.status)"
              />
            </span>
          </template>
        </Column>
        <Column
          field="section"
          :header="t('Section')"
        />
        <Column :header="t('Setting')">
          <template #body="{ data }">
            <a
              v-if="data.url"
              :href="data.url"
              target="_blank"
              rel="noopener noreferrer"
              class="text-primary hover:underline"
            >
              {{ data.title }}
            </a>
            <span v-else>{{ data.title }}</span>
          </template>
        </Column>
        <Column
          field="current"
          :header="t('Current')"
        >
          <template #body="{ data }">
            <span class="font-mono text-caption">{{ data.current }}</span>
          </template>
        </Column>
        <Column
          field="expected"
          :header="t('Expected')"
        >
          <template #body="{ data }">
            <span class="font-mono text-caption">{{ data.expected }}</span>
          </template>
        </Column>
        <Column
          field="comment"
          :header="t('Comment')"
        />
      </BaseTable>

      <BaseTable
        v-else-if="rowType === 'paths'"
        :values="rows"
        data-key="constant"
      >
        <Column
          field="path"
          :header="t('Path')"
        >
          <template #body="{ data }">
            <span class="break-all font-mono text-caption">{{ data.path }}</span>
          </template>
        </Column>
        <Column
          field="constant"
          :header="t('Constant')"
        >
          <template #body="{ data }">
            <span class="font-mono text-caption">{{ data.constant }}</span>
          </template>
        </Column>
      </BaseTable>

      <BaseTable
        v-else-if="rowType === 'coursesSpace'"
        :values="rows"
        data-key="id"
      >
        <Column header="">
          <template #body>
            <BaseIcon
              icon="home"
              size="small"
            />
          </template>
        </Column>
        <Column
          field="code"
          :header="t('Course code')"
        />
        <Column
          field="usedMb"
          :header="t('Space used on disk (MB)')"
        />
        <Column
          field="quotaMb"
          :header="t('Set max course space (MB)')"
        />
        <Column :header="t('Edit')">
          <template #body="{ data }">
            <BaseButton
              :label="t('Edit')"
              icon="pencil"
              type="secondary-text"
              only-icon
              size="small"
              :to-url="courseEditUrl(data.id)"
            />
          </template>
        </Column>
        <Column
          field="lastVisit"
          :header="t('Latest visit')"
        >
          <template #body="{ data }">
            {{ formatLastVisit(data.lastVisit) }}
          </template>
        </Column>
      </BaseTable>
    </template>
  </section>
</template>

<script setup>
import { computed, onBeforeUnmount, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseCheckbox from "../../components/basecomponents/BaseCheckbox.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import baseService from "../../services/baseService"
import { useNotification } from "../../composables/notification"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const { showErrorNotification } = useNotification()

const sections = ref([])
const currentSection = ref("chamilo")
const rowType = ref("generic")
const rows = ref([])
const isLoading = ref(true)
const errorMessage = ref("")

const cacheData = ref(null)
const cacheFetchedAt = ref(null)
const cacheLoading = ref(false)
const cacheAutoRefresh = ref(false)
/** Folded by default — live cache stats load only when expanded. */
const phpCacheExpanded = ref(false)
let cacheRefreshTimer = null

const activeSectionInfo = computed(() => {
  const match = sections.value.find((s) => s.key === currentSection.value)

  return match?.info || ""
})

const opcacheMemoryBar = computed(() => {
  const o = cacheData.value?.opcache
  if (!o?.enabled) {
    return null
  }

  const used = Number(o.memoryUsedBytes)
  const free = Number(o.memoryFreeBytes)
  const wasted = Number(o.memoryWastedBytes ?? 0)
  if (Number.isNaN(used) || Number.isNaN(free)) {
    return null
  }

  // Total allocated OPcache pool = used + free + wasted.
  const total = used + free + (Number.isNaN(wasted) ? 0 : wasted)
  if (total <= 0) {
    return null
  }

  // Occupancy includes wasted memory (still reserved in the pool).
  const occupied = used + (Number.isNaN(wasted) ? 0 : wasted)
  const percent =
    o.memoryUsedPercent !== null && o.memoryUsedPercent !== undefined && !Number.isNaN(Number(o.memoryUsedPercent))
      ? Number(o.memoryUsedPercent)
      : Math.round((10000 * occupied) / total) / 100

  return { used, total, percent }
})

const apcuMemoryBar = computed(() => {
  const a = cacheData.value?.apcu
  if (!a?.enabled) {
    return null
  }

  const used = Number(a.memoryUsedBytes)
  const total = Number(a.memorySizeBytes)
  if (Number.isNaN(used) || Number.isNaN(total) || total <= 0) {
    return null
  }

  const percent =
    a.memoryUsedPercent !== null && a.memoryUsedPercent !== undefined && !Number.isNaN(Number(a.memoryUsedPercent))
      ? Number(a.memoryUsedPercent)
      : Math.round((10000 * used) / total) / 100

  return { used, total, percent }
})

const opcacheMetrics = computed(() => {
  const o = cacheData.value?.opcache
  if (!o?.enabled) {
    return []
  }

  return [
    { label: t("Memory used"), value: formatBytes(o.memoryUsedBytes) },
    { label: t("Memory free"), value: formatBytes(o.memoryFreeBytes) },
    { label: t("Memory wasted"), value: formatBytes(o.memoryWastedBytes) },
    { label: t("Memory used %"), value: formatPercent(o.memoryUsedPercent) },
    { label: t("Cached scripts"), value: formatNumber(o.cachedScripts) },
    { label: t("Cached keys"), value: formatKeysRatio(o.cachedKeys, o.maxCachedKeys) },
    { label: t("Hits"), value: formatNumber(o.hits) },
    { label: t("Misses"), value: formatNumber(o.misses) },
    { label: t("Hit rate"), value: formatPercent(o.hitRatePercent) },
    { label: t("Cache full"), value: formatYesNo(o.full) },
    { label: t("OOM restarts"), value: formatNumber(o.oomRestarts) },
    { label: t("Hash restarts"), value: formatNumber(o.hashRestarts) },
    { label: t("Manual restarts"), value: formatNumber(o.manualRestarts) },
    { label: t("Interned strings used"), value: formatBytes(o.internedStringsUsedBytes) },
    { label: t("Interned strings free"), value: formatBytes(o.internedStringsFreeBytes) },
    { label: t("Interned strings count"), value: formatNumber(o.internedStringsNumber) },
  ]
})

const apcuMetrics = computed(() => {
  const a = cacheData.value?.apcu
  if (!a?.enabled) {
    return []
  }

  return [
    { label: t("Memory used"), value: formatBytes(a.memoryUsedBytes) },
    { label: t("Memory available"), value: formatBytes(a.memoryAvailableBytes) },
    { label: t("Memory size"), value: formatBytes(a.memorySizeBytes) },
    { label: t("Memory used %"), value: formatPercent(a.memoryUsedPercent) },
    { label: t("Entries"), value: formatNumber(a.numEntries) },
    { label: t("Slots"), value: formatNumber(a.numSlots) },
    { label: t("Hits"), value: formatNumber(a.numHits) },
    { label: t("Misses"), value: formatNumber(a.numMisses) },
    { label: t("Hit rate"), value: formatPercent(a.hitRatePercent) },
    { label: t("Inserts"), value: formatNumber(a.numInserts) },
    { label: t("Expunges"), value: formatNumber(a.numExpunges) },
    { label: t("Cache start time"), value: formatLastVisit(a.startTime) },
  ]
})

function statusIconClass(status) {
  switch (status) {
    case "ok":
      return "mdi-check-circle text-success"
    case "warning":
      return "mdi-alert text-warning"
    case "error":
      return "mdi-alert-circle text-danger"
    case "info":
    default:
      return "mdi-information text-info"
  }
}

function cacheStatusLabel(block) {
  if (!block) {
    return t("Loading") + "..."
  }
  if (!block.available) {
    return t("Not available")
  }

  return block.enabled ? t("Enabled") : t("Disabled")
}

/**
 * Inline styles for the bar fill.
 *
 * Chamilo's Tailwind config replaces the default color palette, so classes like
 * bg-green-500 do not exist. Dynamic :class utilities can also miss the
 * stylesheet when only generated for statically scanned tokens. RGB CSS
 * variables from the active theme always resolve.
 */
function memoryBarFillStyle(percent) {
  const p = Number(percent)
  const clamped = Number.isNaN(p) ? 0 : Math.min(100, Math.max(0, p))

  let colorVar = "--color-success-base"
  if (clamped >= 90) {
    colorVar = "--color-danger-base"
  } else if (clamped >= 70) {
    colorVar = "--color-warning-base"
  }

  return {
    width: `${clamped}%`,
    backgroundColor: `rgb(var(${colorVar}))`,
    minWidth: clamped > 0 ? "2px" : "0",
  }
}

function courseEditUrl(id) {
  return `/main/admin/course_edit.php?id=${Number(id)}`
}

function formatLastVisit(value) {
  if (!value) {
    return "—"
  }

  try {
    return new Date(value).toLocaleString()
  } catch {
    return value
  }
}

function formatBytes(bytes) {
  if (bytes === null || bytes === undefined || Number.isNaN(Number(bytes))) {
    return "—"
  }
  const n = Number(bytes)
  if (n < 1024) {
    return `${n} B`
  }
  if (n < 1024 * 1024) {
    return `${(n / 1024).toFixed(1)} KB`
  }
  if (n < 1024 * 1024 * 1024) {
    return `${(n / (1024 * 1024)).toFixed(2)} MB`
  }

  return `${(n / (1024 * 1024 * 1024)).toFixed(2)} GB`
}

function formatNumber(value) {
  if (value === null || value === undefined || Number.isNaN(Number(value))) {
    return "—"
  }

  return Number(value).toLocaleString()
}

function formatPercent(value) {
  if (value === null || value === undefined || Number.isNaN(Number(value))) {
    return "—"
  }

  return `${Number(value).toFixed(2)} %`
}

function formatKeysRatio(used, max) {
  if (used === null || used === undefined) {
    return "—"
  }
  if (max === null || max === undefined) {
    return formatNumber(used)
  }

  return `${formatNumber(used)} / ${formatNumber(max)}`
}

function formatYesNo(value) {
  if (value === null || value === undefined) {
    return "—"
  }

  return value ? t("Yes") : t("No")
}

function selectSection(key) {
  if (key === currentSection.value) {
    return
  }

  router.push({ name: "AdminSystemStatus", query: { section: key } })
}

function stopCacheAutoRefresh() {
  if (cacheRefreshTimer !== null) {
    clearInterval(cacheRefreshTimer)
    cacheRefreshTimer = null
  }
}

function startCacheAutoRefresh() {
  stopCacheAutoRefresh()
  if (!cacheAutoRefresh.value || currentSection.value !== "php" || !phpCacheExpanded.value) {
    return
  }
  cacheRefreshTimer = setInterval(() => {
    loadCacheData({ silent: true })
  }, 5000)
}

async function loadCacheData({ silent = false } = {}) {
  if (!silent) {
    cacheLoading.value = true
  }

  try {
    const data = await baseService.get("/admin/system-status-cache-data")
    cacheData.value = data
    cacheFetchedAt.value = data.fetchedAt || new Date().toISOString()
  } catch (e) {
    if (!silent) {
      showErrorNotification(e)
    }
  } finally {
    if (!silent) {
      cacheLoading.value = false
    }
  }
}

async function togglePhpCache() {
  phpCacheExpanded.value = !phpCacheExpanded.value

  if (phpCacheExpanded.value) {
    await loadCacheData()
    startCacheAutoRefresh()
  } else {
    stopCacheAutoRefresh()
  }
}

async function loadSection(sectionKey) {
  isLoading.value = true
  errorMessage.value = ""
  stopCacheAutoRefresh()
  // Reset fold state when navigating sections so PHP cache starts collapsed again.
  phpCacheExpanded.value = false
  cacheAutoRefresh.value = false

  try {
    const data = await baseService.get("/admin/system-status-data", {
      section: sectionKey || "chamilo",
    })

    sections.value = data.sections || []
    currentSection.value = data.currentSection || "chamilo"
    rowType.value = data.rowType || "generic"
    rows.value = data.rows || []

    if (currentSection.value !== "php") {
      cacheData.value = null
      cacheFetchedAt.value = null
    }
  } catch (e) {
    errorMessage.value = t("An unexpected error occurred.")
    showErrorNotification(e)
  } finally {
    isLoading.value = false
  }
}

watch(
  () => route.query.section,
  (section) => {
    loadSection(typeof section === "string" ? section : "chamilo")
  },
  { immediate: true },
)

watch(cacheAutoRefresh, () => {
  startCacheAutoRefresh()
})

onBeforeUnmount(() => {
  stopCacheAutoRefresh()
})
</script>
