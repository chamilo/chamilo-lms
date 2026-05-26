<script setup>
import { reactive, ref, watch } from "vue"
import { FilterMatchMode } from "@primevue/core/api"
import debounce from "lodash/debounce"
import userService from "../../services/userService"
import { useNotification } from "../../composables/notification"
import InputText from "primevue/inputtext"
import BaseButton from "./BaseButton.vue"
import BaseTable from "./BaseTable.vue"
import { useI18n } from "vue-i18n"

const { t } = useI18n()
const { showErrorNotification } = useNotification()

const tableState = reactive({
  filters: {},
  rows: 20,
  sortField: null,
  sortOrder: null,
  currentPage: 0,
})
const totalRecords = ref(0)
const isLoadingUserList = ref(true)
const userList = ref([])
const selectedUsers = ref([])

async function listUsers() {
  let searchParams = {
    page: tableState.currentPage + 1,
    itemsPerPage: tableState.rows,
  }

  if (tableState.sortField) {
    searchParams.order = { [tableState.sortField]: tableState.sortOrder === -1 ? "desc" : "asc" }
  }

  if (tableState.filters) {
    if (tableState.filters.username && tableState.filters.username.value) {
      searchParams.username = tableState.filters.username.value
    }

    if (tableState.filters.firstname && tableState.filters.firstname.value) {
      searchParams.firstname = tableState.filters.firstname.value
    }

    if (tableState.filters.lastname && tableState.filters.lastname.value) {
      searchParams.lastname = tableState.filters.lastname.value
    }
  }

  try {
    isLoadingUserList.value = true

    const { totalItems, items } = await userService.findAll(searchParams)

    totalRecords.value = totalItems
    userList.value = items
  } catch (error) {
    showErrorNotification(error)
  } finally {
    isLoadingUserList.value = false
  }
}

const debouncedSearch = debounce(() => {
  listUsers()
}, 300)

async function onPage({ page, rows, sortField, sortOrder, filters }) {
  tableState.currentPage = page
  tableState.rows = rows
  tableState.sortField = sortField
  tableState.sortOrder = sortOrder
  tableState.filters = filters
}

async function onSort({ rows, sortField, sortOrder, filters }) {
  tableState.rows = rows
  tableState.sortField = sortField
  tableState.sortOrder = sortOrder
  tableState.filters = filters
  tableState.currentPage = 0
}

async function onFilter({ rows, sortField, sortOrder, filters }) {
  tableState.rows = rows
  tableState.sortField = sortField
  tableState.sortOrder = sortOrder
  tableState.currentPage = 0
  tableState.filters = filters
}

function clearFilters() {
  tableState.filters = {
    username: { value: null, matchMode: FilterMatchMode.CONTAINS },
    firstname: { value: null, matchMode: FilterMatchMode.CONTAINS },
    lastname: { value: null, matchMode: FilterMatchMode.CONTAINS },
  }
}

clearFilters()

listUsers()

watch(tableState, () => {
  debouncedSearch()
})

defineExpose({ selectedUsers })
</script>

<template>
  <BaseTable
    v-model:filters="tableState.filters"
    v-model:selected-items="selectedUsers"
    :is-loading="isLoadingUserList"
    :total-items="totalRecords"
    :values="userList"
    data-key="@id"
    lazy
    show-filter-row
    @filter="onFilter"
    @page="onPage"
    @sort="onSort"
  >
    <template #header>
      <div class="flex justify-between items-center gap-4">
        <span
          v-text="t('User list')"
          class="mr-auto"
        />
        <BaseButton
          :label="t('Clear filters')"
          icon="clear-all"
          size="small"
          type="plain"
          @click="clearFilters"
        />
      </div>
    </template>

    <Column selectionMode="multiple" />

    <Column
      :header="t('Username')"
      :show-filter-menu="false"
      field="username"
      filter-field="username"
      sortable
    >
      <template #filter="{ filterModel, filterCallback }">
        <InputText
          v-model="filterModel.value"
          size="small"
          type="text"
          @input="filterCallback()"
        />
      </template>
    </Column>

    <Column
      :header="t('First name')"
      :show-filter-menu="false"
      field="firstname"
      sortable
    >
      <template #filter="{ filterModel, filterCallback }">
        <InputText
          v-model="filterModel.value"
          size="small"
          type="text"
          @input="filterCallback()"
        />
      </template>
    </Column>
    <Column
      :header="t('Last name')"
      :show-filter-menu="false"
      field="lastname"
      sortable
    >
      <template #filter="{ filterModel, filterCallback }">
        <InputText
          v-model="filterModel.value"
          size="small"
          type="text"
          @input="filterCallback()"
        />
      </template>
    </Column>
  </BaseTable>
</template>
