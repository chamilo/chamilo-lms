<script setup>
import { ref } from "vue"
import { FilterMatchMode } from "@primevue/core/api"
import debounce from "lodash/debounce"
import userService from "../../services/userService"
import { useNotification } from "../../composables/notification"
import InputText from "primevue/inputtext"
import BaseTable from "./BaseTable.vue"
import { useI18n } from "vue-i18n"

const { t } = useI18n()
const { showErrorNotification } = useNotification()

const currentPage = ref(0)
const filters = ref({
  username: { value: null, matchMode: FilterMatchMode.CONTAINS },
  firstname: { value: null, matchMode: FilterMatchMode.CONTAINS },
  lastname: { value: null, matchMode: FilterMatchMode.CONTAINS },
})
const isLoadingUserList = ref(true)
const userList = ref([])
const userListTotal = ref(0)
const selectedUsers = ref([])

async function listUsers(rows, sortField, sortOrder, filters) {
  let searchParams = {
    page: currentPage.value + 1,
    itemsPerPage: rows,
  }

  if (sortField) {
    searchParams.order = { [sortField]: sortOrder === -1 ? "desc" : "asc" }
  }

  if (filters) {
    if (filters.username && filters.username.value) {
      searchParams.username = filters.username.value
    }

    if (filters.firstname && filters.firstname.value) {
      searchParams.firstname = filters.firstname.value
    }

    if (filters.lastname && filters.lastname.value) {
      searchParams.lastname = filters.lastname.value
    }
  }

  try {
    isLoadingUserList.value = true

    const { totalItems, items } = await userService.findAll(searchParams)

    userListTotal.value = totalItems
    userList.value = items
  } catch (error) {
    showErrorNotification(error)
  } finally {
    isLoadingUserList.value = false
  }
}

const debouncedSearch = debounce((rows, sortField, sortOrder, filters) => {
  listUsers(rows, sortField, sortOrder, filters)
}, 300)

async function onPage({ page, rows, sortField, sortOrder, filters }) {
  currentPage.value = page

  debouncedSearch(rows, sortField, sortOrder, filters)
}

async function onSort({ rows, sortField, sortOrder, filters }) {
  currentPage.value = 0

  debouncedSearch(rows, sortField, sortOrder, filters)
}

async function onFilter({ rows, sortField, sortOrder, filters }) {
  currentPage.value = 0

  debouncedSearch(rows, sortField, sortOrder, filters)
}

listUsers(20).then(() => {})

defineExpose({ selectedUsers })
</script>

<template>
  <BaseTable
    v-model:filters="filters"
    v-model:selected-items="selectedUsers"
    :is-loading="isLoadingUserList"
    :total-items="userListTotal"
    :values="userList"
    data-key="@id"
    lazy
    show-filter-row
    @filter="onFilter"
    @page="onPage"
    @sort="onSort"
  >
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
          type="text"
          @input="filterCallback()"
        />
      </template>
    </Column>
  </BaseTable>
</template>
