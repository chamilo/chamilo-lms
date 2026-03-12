<template>
  <div class="flex flex-col gap-8">
    <div class="flex items-center justify-between">
      <h2 class="text-2xl font-semibold text-gray-800">{{ t("User list") }}</h2>
      <a
        href="/main/admin/user_add.php"
        class="btn btn--primary"
      >
        {{ t("Add a user") }}
      </a>
    </div>

    <!-- Tabs: All users / Deleted users -->
    <div class="flex gap-2 border-b border-gray-200">
      <button
        :class="[
          'px-4 py-2 text-sm font-medium border-b-2 transition-colors',
          view === 'all'
            ? 'border-blue-600 text-blue-600'
            : 'border-transparent text-gray-500 hover:text-gray-700',
        ]"
        @click="switchView('all')"
      >
        {{ t("All users") }}
      </button>
      <button
        :class="[
          'px-4 py-2 text-sm font-medium border-b-2 transition-colors',
          view === 'deleted'
            ? 'border-blue-600 text-blue-600'
            : 'border-transparent text-gray-500 hover:text-gray-700',
        ]"
        @click="switchView('deleted')"
      >
        {{ t("Deleted users") }}
      </button>
    </div>

    <!-- Search form -->
    <form
      class="flex flex-wrap gap-4 items-end"
      @submit.prevent="onSearch"
    >
      <div class="flex flex-col gap-1">
        <label class="text-sm text-gray-600">{{ t("Keyword") }}</label>
        <input
          v-model="filters.keyword"
          class="border border-gray-300 rounded px-3 py-1.5 text-sm"
          type="text"
        />
      </div>
      <div class="flex flex-col gap-1">
        <label class="text-sm text-gray-600">{{ t("First name") }}</label>
        <input
          v-model="filters.keyword_firstname"
          class="border border-gray-300 rounded px-3 py-1.5 text-sm"
          type="text"
        />
      </div>
      <div class="flex flex-col gap-1">
        <label class="text-sm text-gray-600">{{ t("Last name") }}</label>
        <input
          v-model="filters.keyword_lastname"
          class="border border-gray-300 rounded px-3 py-1.5 text-sm"
          type="text"
        />
      </div>
      <div class="flex flex-col gap-1">
        <label class="text-sm text-gray-600">{{ t("Username") }}</label>
        <input
          v-model="filters.keyword_username"
          class="border border-gray-300 rounded px-3 py-1.5 text-sm"
          type="text"
        />
      </div>
      <div class="flex flex-col gap-1">
        <label class="text-sm text-gray-600">{{ t("E-mail") }}</label>
        <input
          v-model="filters.keyword_email"
          class="border border-gray-300 rounded px-3 py-1.5 text-sm"
          type="text"
        />
      </div>
      <div class="flex flex-col gap-1">
        <label class="text-sm text-gray-600">{{ t("Official code") }}</label>
        <input
          v-model="filters.keyword_officialcode"
          class="border border-gray-300 rounded px-3 py-1.5 text-sm"
          type="text"
        />
      </div>
      <button
        class="btn btn--primary"
        type="submit"
      >
        {{ t("Search") }}
      </button>
    </form>

    <!-- User table -->
    <BaseTable
      v-model:rows="pageSize"
      :is-loading="isLoading"
      :lazy="true"
      :text-for-empty="t('No data available')"
      :total-items="total"
      :values="items"
      data-key="id"
      @page="onPage"
      @sort="onSort"
    >
      <Column
        :header="t('Photo')"
        field="avatarUrl"
      >
        <template #body="{ data }">
          <img
            :alt="data.firstname + ' ' + data.lastname"
            :src="data.avatarUrl"
            class="w-8 h-8 rounded-full object-cover"
          />
        </template>
      </Column>
      <Column
        :header="t('Official code')"
        field="officialCode"
        sortable
      />
      <Column
        :header="t('First name')"
        field="firstname"
        sortable
      >
        <template #body="{ data }">
          <a :href="`/main/admin/user_information.php?user_id=${data.id}`">{{ data.firstname }}</a>
        </template>
      </Column>
      <Column
        :header="t('Last name')"
        field="lastname"
        sortable
      >
        <template #body="{ data }">
          <a :href="`/main/admin/user_information.php?user_id=${data.id}`">{{ data.lastname }}</a>
        </template>
      </Column>
      <Column
        :header="t('Username')"
        field="username"
        sortable
      />
      <Column
        :header="t('E-mail')"
        field="email"
        sortable
      />
      <Column
        :header="t('Roles')"
        field="roles"
      >
        <template #body="{ data }">
          <span
            v-for="role in data.roles"
            :key="role"
            class="block text-xs"
          >{{ formatRole(role) }}</span>
        </template>
      </Column>
      <Column
        :header="t('Active')"
        field="active"
        sortable
      >
        <template #body="{ data }">
          <span
            :class="[
              'inline-block w-3 h-3 rounded-full',
              data.active === 1 ? 'bg-green-500' : 'bg-red-400',
            ]"
            :title="data.active === 1 ? t('Active') : t('Inactive')"
          />
        </template>
      </Column>
      <Column
        :header="t('Registration date')"
        field="createdAt"
        sortable
      />
      <Column
        :header="t('Latest login')"
        field="lastLogin"
        sortable
      />
      <Column
        :header="t('Actions')"
        field="id"
      >
        <template #body="{ data }">
          <div class="flex gap-1">
            <a
              :href="`/main/admin/user_information.php?user_id=${data.id}`"
              :title="t('Information')"
              class="text-blue-600 hover:text-blue-800"
            >
              <span class="mdi mdi-information text-lg" />
            </a>
            <a
              :href="`/main/admin/user_edit.php?user_id=${data.id}`"
              :title="t('Edit')"
              class="text-blue-600 hover:text-blue-800"
            >
              <span class="mdi mdi-pencil text-lg" />
            </a>
          </div>
        </template>
      </Column>
    </BaseTable>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from "vue"
import { useI18n } from "vue-i18n"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import baseService from "../../services/baseService"

const { t } = useI18n()

const items = ref([])
const total = ref(0)
const isLoading = ref(false)
const page = ref(1)
const pageSize = ref(20)
const sortField = ref("lastname")
const sortOrder = ref(1)
const view = ref("all")

const filters = reactive({
  keyword: "",
  keyword_firstname: "",
  keyword_lastname: "",
  keyword_username: "",
  keyword_email: "",
  keyword_officialcode: "",
})

const ROLE_LABELS = {
  ROLE_TEACHER: "Teacher",
  ROLE_STUDENT: "Student",
  ROLE_HR: "Human Resources Manager",
  ROLE_SESSION_MANAGER: "Session Manager",
  ROLE_STUDENT_BOSS: "Student Boss",
  ROLE_INVITEE: "Invitee",
  ROLE_PLATFORM_ADMIN: "Administrator",
  ROLE_ADMIN: "Administrator",
  ROLE_GLOBAL_ADMIN: "Global Administrator",
}

function formatRole(role) {
  const upper = role.toUpperCase()
  return ROLE_LABELS[upper] ?? role.replace(/^ROLE_/, "").replace(/_/g, " ").toLowerCase().replace(/\b\w/g, (c) => c.toUpperCase())
}

async function load() {
  isLoading.value = true
  try {
    const params = new URLSearchParams({
      page: String(page.value),
      limit: String(pageSize.value),
      sortField: sortField.value,
      sortOrder: sortOrder.value === 1 ? "ASC" : "DESC",
      view: view.value,
    })
    for (const [key, val] of Object.entries(filters)) {
      if (val) params.set(key, val)
    }
    const data = await baseService.get(`/admin/user-list-data?${params.toString()}`)
    items.value = data.items
    total.value = data.total
  } catch (e) {
    console.error(e)
  } finally {
    isLoading.value = false
  }
}

function onPage(event) {
  page.value = event.page + 1
  pageSize.value = event.rows
  load()
}

function onSort(event) {
  sortField.value = event.sortField ?? "lastname"
  sortOrder.value = event.sortOrder ?? 1
  page.value = 1
  load()
}

function onSearch() {
  page.value = 1
  load()
}

function switchView(newView) {
  view.value = newView
  page.value = 1
  load()
}

onMounted(load)
</script>
