<script setup>
import { ref, useSlots, computed } from "vue"
import DataTable from "primevue/datatable"
import { usePlatformConfig } from "../../store/platformConfig"

const platformConfigStore = usePlatformConfig()

/* v-models */
const filters = defineModel("filters", { type: Object, required: false })
const selectedItems = defineModel("selectedItems", { type: Array, required: false })
const sortOrder = defineModel("sortOrder", { type: Number, required: false, default: null })
const sortField = defineModel("sortField", { type: String, required: false, default: null })
const multiSortMeta = defineModel("multiSortMeta", { type: Array, required: false, default: null })
const rows = defineModel("rows", { type: Number, required: false })

/* props */
const props = defineProps({
  globalFilterFields: {
    type: Array,
    required: false,
    default: null,
  },
  lazy: {
    type: Boolean,
    required: false,
    default: false,
  },
  isLoading: {
    type: Boolean,
    required: false,
  },
  totalItems: {
    type: Number,
    required: false,
    default: 0,
  },
  values: {
    type: Array,
    required: false,
    default: () => [],
  },
  dataKey: {
    type: String,
    required: false,
    default: null,
  },
  filterAsMenu: {
    type: Boolean,
    required: false,
    default: false,
  },
  removableSort: {
    type: Boolean,
    required: false,
    default: false,
  },
  sortMode: {
    type: String,
    required: false,
    default: "single", // single, multiple
  },
  rowClass: {
    type: Function,
    required: false,
    default: null,
  },
  textForEmpty: {
    type: String,
    required: false,
    default: null,
  },
})

/* ---------- helpers settings ---------- */
const DEFAULT_FALLBACK_ROWS = 20

function getSetting(key, fallback) {
  const v = platformConfigStore.getSetting(key)
  return (v === undefined || v === null || v === "") ? fallback : v
}

function parseRowList(val) {
  if (!val) return [5, 10, 20, 50]
  if (Array.isArray(val)) return val.map(Number).filter(Number.isFinite)
  if (typeof val === "string") {
    try {
      const parsed = JSON.parse(val)
      const arr = Array.isArray(parsed) ? parsed : parsed?.options
      if (Array.isArray(arr)) return arr.map(Number).filter(Number.isFinite)
    } catch {
      const arr = val.split(",").map(s => Number(s.trim()))
      if (arr.some(n => !Number.isNaN(n))) return arr
    }
  } else if (typeof val === "object" && val?.options) {
    const arr = val.options
    if (Array.isArray(arr)) return arr.map(Number).filter(Number.isFinite)
  }
  return [5, 10, 20, 50]
}

const rowListRaw = computed(() => getSetting("platform.table_row_list", [5, 10, 20, 50]))

const defaultRowSetting = computed(() => {
  const raw = getSetting("platform.table_default_row", DEFAULT_FALLBACK_ROWS)
  const n = Number(raw)
  if (!Number.isFinite(n) || n <= 0) return DEFAULT_FALLBACK_ROWS
  return n
})

const rowsPerPageOptions = computed(() => {
  const opts = parseRowList(rowListRaw.value)
  const list = []
  for (const raw of opts) {
    const n = Number(raw)
    if (!Number.isFinite(n) || n < 0) continue
    if (n === 0) {
      list.push(props.totalItems || Number.MAX_SAFE_INTEGER)
    } else {
      list.push(n)
    }
  }
  return [...new Set(list)].sort((a, b) => a - b).slice(0, 10)
})

function initialRows() {
  return defaultRowSetting.value
}
if (rows.value == null) {
  rows.value = initialRows()
}

defineEmits(["page", "sort"])

const slots = useSlots()

const elRef = ref(null)

defineExpose({
  resetPage: elRef.value ? elRef.value.resetPage : () => {},
})
</script>

<template>
  <DataTable
    ref="elRef"
    v-model:filters="filters"
    v-model:multi-sort-meta="multiSortMeta"
    v-model:rows="rows"
    v-model:selection="selectedItems"
    v-model:sort-field="sortField"
    v-model:sort-order="sortOrder"
    :data-key="dataKey"
    :filter-display="filterAsMenu ? 'menu' : null"
    :global-filter-fields="globalFilterFields"
    :lazy="lazy"
    :loading="isLoading"
    :removable-sort="removableSort"
    :rows-per-page-options="rowsPerPageOptions"
    :sort-mode="sortMode"
    :total-records="totalItems"
    :value="values"
    current-page-report-template="{first} - {last} / {totalRecords}"
    paginator
    paginator-template="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
    responsive-layout="scroll"
    size="small"
    striped-rows
    @page="$emit('page', $event)"
    @sort="$emit('sort', $event)"
  >
    <slot />

    <template
      #header
      v-if="slots.header"
    >
      <slot name="header" />
    </template>

    <template
      #footer
      v-if="slots.footer"
    >
      <slot name="footer" />
    </template>

    <template
      #empty
      v-if="textForEmpty"
    >
      {{ textForEmpty }}
    </template>
  </DataTable>
</template>
