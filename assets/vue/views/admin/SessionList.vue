<template>
  <div class="flex flex-col gap-8">
    <SectionHeader :title="t('Session list')">
      <BaseButton
        :label="t('Add a training session')"
        :to-url="'/main/session/session_add.php'"
        icon="plus"
        type="success"
      />
    </SectionHeader>

    <!-- Tabs -->
    <div class="flex gap-2 border-b border-gray-200">
      <button
        v-for="tab in tabs"
        :key="tab.value"
        :class="[
          'px-4 py-2 text-sm font-medium border-b-2 transition-colors',
          listType === tab.value
            ? 'border-blue-600 text-blue-600'
            : 'border-transparent text-gray-500 hover:text-gray-700',
        ]"
        @click="switchTab(tab.value)"
      >
        {{ t(tab.label) }}
      </button>
    </div>

    <!-- Search & Filters -->
    <div class="flex flex-col gap-4">
      <form
        class="flex gap-4 items-end"
        @submit.prevent="onSearch"
      >
        <div class="flex flex-col gap-1 flex-1 max-w-md">
          <input
            v-model="keyword"
            :placeholder="t('Search sessions')"
            class="form-control w-full"
            type="text"
          />
        </div>
        <div class="flex flex-col gap-1 max-w-xs">
          <select
            v-model="categoryFilter"
            class="form-control"
            @change="onSearch"
          >
            <option value="">{{ t("All categories") }}</option>
            <option
              v-for="cat in categories"
              :key="cat.id"
              :value="cat.id"
            >
              {{ cat.title }}
            </option>
          </select>
        </div>
        <BaseButton
          :label="t('Search')"
          icon="search"
          is-submit
        />
      </form>
    </div>

    <!-- Session table -->
    <BaseTable
      v-model:rows="pageSize"
      v-model:selectedItems="selectedItems"
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
        header-style="width: 3rem"
        selection-mode="multiple"
      />
      <Column
        :header="t('Title')"
        field="title"
        sortable
      >
        <template #body="{ data }">
          <span
            v-if="data.isChild"
            class="text-gray-500"
          >
            {{ data.title }}
          </span>
          <BaseAppLink
            v-else
            :url="`/main/session/resume_session.php?id_session=${data.id}`"
            class="text-blue-600 hover:underline"
          >
            {{ data.title }}
          </BaseAppLink>
        </template>
      </Column>
      <Column
        :header="t('Category')"
        field="categoryName"
        sortable
      />
      <Column
        :header="t('Start date to display')"
        field="displayStartDate"
        sortable
      />
      <Column
        :header="t('End date to display')"
        field="displayEndDate"
        sortable
      />
      <Column
        :header="t('Visibility')"
        field="visibilityLabel"
      />
      <Column
        :header="t('Users')"
        field="nbrUsers"
        sortable
      >
        <template #body="{ data }">
          <span
            v-if="data.usersLang && Object.keys(data.usersLang).length"
            :title="
              Object.entries(data.usersLang)
                .map(([lang, count]) => `${lang} ${count}`)
                .join(' | ')
            "
            class="cursor-help underline decoration-dotted"
          >
            {{ data.nbrUsers }}
          </span>
          <span v-else>{{ data.nbrUsers }}</span>
        </template>
      </Column>
      <Column
        :header="t('Courses')"
        field="nbrCourses"
        sortable
      />
      <Column
        :header="t('Session status')"
        field="statusLabel"
      >
        <template #body="{ data }">
          <span
            :class="statusClass(data.status)"
            class="inline-block px-2 py-0.5 rounded text-xs font-medium"
            >{{ t(data.statusLabel) }}</span
          >
        </template>
      </Column>
      <Column
        :header="t('Actions')"
        field="id"
      >
        <template #body="{ data }">
          <div class="flex gap-1 flex-nowrap">
            <!-- Edit -->
            <a
              :href="`/main/session/session_edit.php?page=resume_session.php&id=${data.id}`"
              :title="t('Edit')"
            >
              <span class="mdi mdi-pencil ch-tool-icon" />
            </a>
            <!-- Subscribe users -->
            <a
              :href="`/main/session/add_users_to_session.php?page=/admin/session-list&id_session=${data.id}`"
              :title="t('Subscribe users to this session')"
            >
              <span class="mdi mdi-account-multiple-plus ch-tool-icon" />
            </a>
            <!-- Add courses -->
            <a
              :href="`/main/session/add_courses_to_session.php?page=/admin/session-list&id_session=${data.id}`"
              :title="t('Add courses to this session')"
            >
              <span class="mdi mdi-book-open-page-variant ch-tool-icon" />
            </a>
            <!-- Copy -->
            <a
              :title="t('Copy')"
              class="cursor-pointer"
              @click.prevent="copySession(data.id)"
            >
              <span class="mdi mdi-content-duplicate ch-tool-icon" />
            </a>
            <!-- Delete -->
            <a
              v-if="viewer.isPlatformAdmin"
              :title="t('Delete')"
              class="cursor-pointer"
              @click.prevent="confirmDelete([data.id])"
            >
              <span class="mdi mdi-delete ch-tool-icon text-red-600" />
            </a>
          </div>
        </template>
      </Column>
    </BaseTable>

    <!-- Toolbar below table -->
    <div class="flex items-center gap-4">
      <BaseButton
        :label="t('Refresh')"
        icon="refresh"
        type="black"
        size="small"
        @click="load"
      />
      <template v-if="selectedItems.length > 0">
        <span class="text-sm text-gray-600">{{ selectedItems.length }} {{ t("selected") }}</span>
        <BaseButton
          v-if="viewer.isPlatformAdmin"
          :label="t('Delete selected')"
          icon="delete"
          type="danger"
          size="small"
          @click="confirmDelete(selectedItems.map((s) => s.id))"
        />
        <BaseButton
          :label="t('Copy selected')"
          icon="copy"
          type="success"
          size="small"
          @click="copyMultiple(selectedItems.map((s) => s.id))"
        />
        <BaseButton
          :label="t('Courses reports')"
          icon="zip-pack"
          type="primary"
          size="small"
          @click="exportCoursesReports(selectedItems.map((s) => s.id))"
        />
        <BaseButton
          :label="t('Export courses reports complete')"
          icon="file-export"
          type="primary"
          size="small"
          @click="exportCoursesReportsComplete(selectedItems.map((s) => s.id))"
        />
      </template>
    </div>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from "vue"
import { useRoute } from "vue-router"
import { useI18n } from "vue-i18n"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import baseService from "../../services/baseService"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"

const { t } = useI18n()
const route = useRoute()

const items = ref([])
const total = ref(0)
const isLoading = ref(false)
const page = ref(1)
const pageSize = ref(20)
const sortField = ref("title")
const sortOrder = ref(1)
const listType = ref("all")
const keyword = ref("")
const categoryFilter = ref("")
const selectedItems = ref([])
const categories = ref([])
const csrfToken = ref("")
const viewer = reactive({ isPlatformAdmin: false })

const tabs = [
  { label: "All sessions", value: "all" },
  { label: "Active sessions", value: "active" },
  { label: "Closed sessions", value: "close" },
  { label: "Custom list", value: "custom" },
  { label: "Replication", value: "replication" },
]

function statusClass(status) {
  switch (status) {
    case 1:
      return "bg-blue-100 text-blue-700"
    case 2:
      return "bg-green-100 text-green-700"
    case 3:
      return "bg-gray-100 text-gray-700"
    case 4:
      return "bg-red-100 text-red-700"
    default:
      return "bg-gray-100 text-gray-500"
  }
}

async function load() {
  isLoading.value = true
  try {
    const params = new URLSearchParams({
      page: String(page.value),
      limit: String(pageSize.value),
      sortField: sortField.value,
      sortOrder: sortOrder.value === 1 ? "ASC" : "DESC",
      listType: listType.value,
    })

    if (keyword.value) {
      params.set("keyword", keyword.value)
    }
    if (categoryFilter.value) {
      params.set("category", categoryFilter.value)
    }

    const data = await baseService.get(`/admin/session-list-data?${params.toString()}`)
    items.value = data.items
    total.value = data.total
    csrfToken.value = data.csrfToken || ""
    if (data.viewer) {
      viewer.isPlatformAdmin = data.viewer.isPlatformAdmin
    }
  } catch (e) {
    console.error("Error loading sessions:", e)
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
  sortField.value = event.sortField ?? "title"
  sortOrder.value = event.sortOrder ?? 1
  page.value = 1
  load()
}

function onSearch() {
  page.value = 1
  selectedItems.value = []
  load()
}

function switchTab(tab) {
  listType.value = tab
  page.value = 1
  keyword.value = ""
  categoryFilter.value = ""
  selectedItems.value = []
  load()
}

async function confirmDelete(ids) {
  if (!confirm(t("Please confirm your choice"))) {
    return
  }

  try {
    const formData = new URLSearchParams()
    formData.set("action", "delete")
    formData.set("_token", csrfToken.value)
    ids.forEach((id) => formData.append("sessionIds[]", String(id)))

    await fetch("/admin/session-list-data-action", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: formData.toString(),
    })

    selectedItems.value = []
    load()
  } catch (e) {
    console.error("Error deleting sessions:", e)
  }
}

async function copySession(id) {
  if (!confirm(t("Please confirm your choice"))) {
    return
  }
  await performCopy([id])
}

async function copyMultiple(ids) {
  if (!confirm(t("Please confirm your choice"))) {
    return
  }
  await performCopy(ids)
}

async function performCopy(ids) {
  try {
    const formData = new URLSearchParams()
    formData.set("action", "copy")
    formData.set("_token", csrfToken.value)
    ids.forEach((id) => formData.append("sessionIds[]", String(id)))

    await fetch("/admin/session-list-data-action", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: formData.toString(),
    })

    selectedItems.value = []
    load()
  } catch (e) {
    console.error("Error copying sessions:", e)
  }
}

async function exportCoursesReports(ids) {
  await submitExportForm(ids, "export_zip")
}

async function exportCoursesReportsComplete(ids) {
  await submitExportForm(ids, "export_csv")
}

async function submitExportForm(ids, action) {
  try {
    const formData = new URLSearchParams()
    formData.set("action", action)
    formData.set("_token", csrfToken.value)
    ids.forEach((id) => formData.append("sessionIds[]", String(id)))

    const response = await fetch("/admin/session-list-data-action", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: formData.toString(),
    })

    if (!response.ok) {
      const data = await response.json().catch(() => null)
      alert(data?.error || t("No data to export"))
      return
    }

    // File download — extract filename from Content-Disposition header
    const disposition = response.headers.get("Content-Disposition") || ""
    const match = disposition.match(/filename="?([^";\n]+)"?/)
    const filename = match ? match[1] : "export"

    const blob = await response.blob()
    const url = URL.createObjectURL(blob)
    const a = document.createElement("a")
    a.href = url
    a.download = filename
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
    URL.revokeObjectURL(url)
  } catch (e) {
    console.error("Export error:", e)
    alert(t("No data to export"))
  }
}

async function handleLegacyAction() {
  const query = route.query
  const action = query.action
  const idChecked = query.idChecked || query.id

  if (!action || !idChecked) {
    return false
  }

  // Wait for first load so we have a CSRF token
  await load()

  const ids = String(idChecked)
    .split(",")
    .map((s) => s.trim())
    .filter(Boolean)

  if (ids.length === 0) {
    return false
  }

  if ((action === "copy" || action === "copy_multiple") && confirm(t("Please confirm your choice"))) {
    await performCopy(ids)
  } else if ((action === "delete" || action === "delete_multiple") && confirm(t("Please confirm your choice"))) {
    await confirmDeleteDirect(ids)
  }

  return true
}

async function confirmDeleteDirect(ids) {
  try {
    const formData = new URLSearchParams()
    formData.set("action", "delete")
    formData.set("_token", csrfToken.value)
    ids.forEach((id) => formData.append("sessionIds[]", String(id)))

    await fetch("/admin/session-list-data-action", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: formData.toString(),
    })

    selectedItems.value = []
    await load()
  } catch (e) {
    console.error("Error deleting sessions:", e)
  }
}

onMounted(async () => {
  // Read URL query params for backward compatibility with legacy links
  const query = route.query
  if (query.list_type && tabs.some((tab) => tab.value === query.list_type)) {
    listType.value = query.list_type
  }
  if (query.id_category) {
    categoryFilter.value = String(query.id_category)
  }
  if (query.keyword) {
    keyword.value = String(query.keyword)
  }

  // Load session categories for the filter dropdown
  try {
    const catData = await baseService.get("/api/session_categories")
    categories.value = (catData["hydra:member"] || []).map((c) => ({ id: c.id, title: c.title }))
  } catch (e) {
    console.error("Error loading categories:", e)
  }

  // Handle legacy action params (?action=copy&idChecked=X or ?action=delete&idChecked=X)
  const handled = await handleLegacyAction()
  if (!handled) {
    load()
  }
})
</script>
