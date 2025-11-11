<script setup>
import { ref } from "vue"
import { useI18n } from "vue-i18n"
import { useRouter } from "vue-router"
import InputText from "primevue/inputtext"
import { FilterMatchMode } from "@primevue/core/api"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import baseService from "../../services/baseService"
import { findAll as listAccessUrl } from "../../services/accessurlService"
import userService from "../../services/userService"
import { useNotification } from "../../composables/notification"
import debounce from "lodash/debounce"

const { t } = useI18n()
const router = useRouter()

const { showErrorNotification, showSuccessNotification } = useNotification()

const accessUrlList = ref([])
const authSourceList = ref([])
const userList = ref([])
const userListTotal = ref(0)

const accessUrl = ref(null)
const authSource = ref(null)
const selectedUsers = ref([])
const isLoadingUserList = ref(true)
const isLoadingAssign = ref(false)
const currentPage = ref(0)
const filters = ref({
  username: { value: null, matchMode: FilterMatchMode.CONTAINS },
  firstname: { value: null, matchMode: FilterMatchMode.CONTAINS },
  lastname: { value: null, matchMode: FilterMatchMode.CONTAINS },
})

async function listAuthSourcesByAccessUrl({ value: accessUrlIri }) {
  authSourceList.value = []
  authSource.value = null

  try {
    const data = await baseService.get("/access-url/auth-sources/list", { access_url: accessUrlIri })

    authSourceList.value = data.map((methodName) => ({ label: methodName, value: methodName }))
  } catch (error) {
    showErrorNotification(error)
  }
}

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

async function assignAuthSources() {
  isLoadingAssign.value = true

  try {
    await baseService.post(
      "/access-url/auth-sources/assign",
      {
        users: selectedUsers.value.map((userInfo) => userInfo["@id"]),
        auth_source: authSource.value,
        access_url: accessUrl.value,
      },
      true,
    )

    showSuccessNotification(t("Auth sources assigned successfully"))

    selectedUsers.value = []
  } catch (e) {
    showErrorNotification(e)
  } finally {
    isLoadingAssign.value = false
  }
}

listAccessUrl().then((items) => (accessUrlList.value = items))
listUsers(20).then(() => {})

const debouncedSearch = debounce((rows, sortField, sortOrder, filters) => {
  listUsers(rows, sortField, sortOrder, filters)
}, 300)
</script>

<template>
  <SectionHeader :title="t('Assign auth sources to users')" />

  <BaseToolbar>
    <template #start>
      <BaseButton
        :title="t('Back to user assignment page')"
        icon="back"
        only-icon
        type="black"
        @click="router.back()"
      />
    </template>
  </BaseToolbar>

  <div class="grid grid-flow-row-dense md:grid-cols-3 gap-4">
    <div class="md:col-span-2">
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
    </div>

    <div>
      <BaseSelect
        id="access_url"
        v-model="accessUrl"
        :disabled="0 === accessUrlList.length"
        :label="t('Access URL')"
        :options="accessUrlList"
        option-label="url"
        option-value="@id"
        @change="listAuthSourcesByAccessUrl"
      />

      <BaseSelect
        id="auth_source"
        v-model="authSource"
        :disabled="0 === authSourceList.length"
        :label="t('Auth source')"
        :options="authSourceList"
      />

      <BaseButton
        :disabled="!accessUrl || !authSource || 0 === selectedUsers.length || isLoadingAssign"
        :is-loading="isLoadingAssign"
        :label="t('Assign')"
        icon="save"
        type="primary"
        @click="assignAuthSources"
      />
    </div>
  </div>
</template>
