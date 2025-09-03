<script setup>
import { ref, useSlots } from "vue"
import DataTable from "primevue/datatable"

const filters = defineModel("filters", { type: Object, required: false })

const selectedItems = defineModel("selectedItems", { type: Array, required: false })

const sortOrder = defineModel("sortOrder", { type: Number, required: false, default: null })

const rows = defineModel("rows", { type: Number, required: false, default: 10 })

const sortField = defineModel("sortField", { type: String, required: false, default: null })

const multiSortMeta = defineModel("multiSortMeta", {
  type: Array,
  required: false,
  default: null,
})

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
    :rows-per-page-options="[5, 10, 20, 50]"
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
