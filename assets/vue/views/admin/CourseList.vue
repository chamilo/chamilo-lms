<template>
  <div class="flex flex-col gap-8">
    <div class="flex items-center justify-between">
      <h2 class="text-2xl font-semibold text-gray-800">{{ t("Course list") }}</h2>
      <a
        class="btn btn--success"
        href="/main/admin/course_add.php"
      >
        {{ t("Add course") }}
      </a>
    </div>

    <!-- Tabs -->
    <div class="flex gap-2 border-b border-gray-200">
      <button
        :class="[
          'px-4 py-2 text-sm font-medium border-b-2 transition-colors',
          view === 'simple' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700',
        ]"
        @click="switchView('simple')"
      >
        {{ t("Standard list") }}
      </button>
      <button
        :class="[
          'px-4 py-2 text-sm font-medium border-b-2 transition-colors',
          view === 'admin' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700',
        ]"
        @click="switchView('admin')"
      >
        {{ t("Management list") }}
      </button>
    </div>

    <!-- Simple search + Advanced toggle -->
    <div class="flex flex-col gap-4">
      <form
        class="flex gap-4 items-end"
        @submit.prevent="onSearch"
      >
        <div class="flex flex-col gap-1 flex-1 max-w-md">
          <input
            v-model="simpleKeyword"
            :placeholder="t('Search courses')"
            class="border border-gray-300 rounded px-3 py-1.5 text-sm w-full"
            type="text"
          />
        </div>
        <button
          class="btn btn--primary"
          type="submit"
        >
          {{ t("Search") }}
        </button>
        <button
          class="btn btn--plain text-sm flex items-center gap-1"
          type="button"
          @click="showAdvanced = !showAdvanced"
        >
          <span :class="showAdvanced ? 'mdi mdi-arrow-down-bold' : 'mdi mdi-arrow-right-bold'" />
          {{ t("Advanced search") }}
        </button>
      </form>

      <!-- Advanced search form -->
      <div
        v-if="showAdvanced"
        class="border border-gray-200 rounded p-4 bg-gray-50"
      >
        <h3 class="text-lg font-medium mb-4">{{ t("Advanced search") }}</h3>
        <form
          class="grid grid-cols-1 md:grid-cols-3 gap-4"
          @submit.prevent="onAdvancedSearch"
        >
          <div class="flex flex-col gap-1">
            <label class="text-sm text-gray-600">{{ t("Course code") }}</label>
            <input
              v-model="advancedFilters.keyword_code"
              class="border border-gray-300 rounded px-3 py-1.5 text-sm"
              type="text"
            />
          </div>
          <div class="flex flex-col gap-1">
            <label class="text-sm text-gray-600">{{ t("Title") }}</label>
            <input
              v-model="advancedFilters.keyword_title"
              class="border border-gray-300 rounded px-3 py-1.5 text-sm"
              type="text"
            />
          </div>
          <div
            v-if="view === 'simple'"
            class="flex flex-col gap-1"
          >
            <label class="text-sm text-gray-600">{{ t("Language") }}</label>
            <input
              v-model="advancedFilters.keyword_language"
              class="border border-gray-300 rounded px-3 py-1.5 text-sm"
              type="text"
            />
          </div>
          <div
            v-if="view === 'simple'"
            class="flex flex-col gap-1"
          >
            <label class="text-sm text-gray-600">{{ t("Categories") }}</label>
            <input
              v-model="advancedFilters.keyword_category"
              class="border border-gray-300 rounded px-3 py-1.5 text-sm"
              type="text"
            />
          </div>
          <div
            v-if="view === 'simple'"
            class="flex flex-col gap-1"
          >
            <label class="text-sm text-gray-600">{{ t("Visibility") }}</label>
            <select
              v-model="advancedFilters.keyword_visibility"
              class="border border-gray-300 rounded px-3 py-1.5 text-sm"
            >
              <option value="">{{ t("All") }}</option>
              <option value="0">{{ t("Closed") }}</option>
              <option value="1">{{ t("Private") }}</option>
              <option value="2">{{ t("Open platform") }}</option>
              <option value="3">{{ t("Public") }}</option>
              <option value="4">{{ t("Hidden") }}</option>
            </select>
          </div>
          <div
            v-if="view === 'simple'"
            class="flex flex-col gap-1"
          >
            <label class="text-sm text-gray-600">{{ t("Registr. allowed") }}</label>
            <select
              v-model="advancedFilters.keyword_subscribe"
              class="border border-gray-300 rounded px-3 py-1.5 text-sm"
            >
              <option value="">{{ t("All") }}</option>
              <option value="1">{{ t("Yes") }}</option>
              <option value="0">{{ t("No") }}</option>
            </select>
          </div>
          <div
            v-if="view === 'simple'"
            class="flex flex-col gap-1"
          >
            <label class="text-sm text-gray-600">{{ t("Unreg. allowed") }}</label>
            <select
              v-model="advancedFilters.keyword_unsubscribe"
              class="border border-gray-300 rounded px-3 py-1.5 text-sm"
            >
              <option value="">{{ t("All") }}</option>
              <option value="1">{{ t("Yes") }}</option>
              <option value="0">{{ t("No") }}</option>
            </select>
          </div>
          <div
            v-if="view === 'admin'"
            class="flex flex-col gap-1"
          >
            <label class="text-sm text-gray-600">{{ t("Teacher") }}</label>
            <input
              v-model="advancedFilters.course_teacher_input"
              :placeholder="t('Teacher user ID')"
              class="border border-gray-300 rounded px-3 py-1.5 text-sm"
              type="text"
            />
          </div>
          <div class="flex items-end md:col-span-3">
            <button
              class="btn btn--primary"
              type="submit"
            >
              {{ t("Search") }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Bulk actions -->
    <div
      v-if="selectedItems.length > 0"
      class="flex items-center gap-4"
    >
      <span class="text-sm text-gray-600">{{ selectedItems.length }} {{ t("selected") }}</span>
      <button
        class="btn btn--danger text-sm"
        @click="confirmBulkDelete"
      >
        {{ t("Delete selected") }}
      </button>
    </div>

    <!-- Course table -->
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

      <!-- Simple view columns -->
      <Column
        :header="t('Title')"
        field="title"
        sortable
      >
        <template #body="{ data }">
          <a
            :href="`/main/admin/course_information.php?code=${encodeURIComponent(data.code)}`"
            class="text-blue-600 hover:underline"
          >
            {{ data.title }}
          </a>
        </template>
      </Column>
      <Column
        v-if="view === 'simple'"
        :header="t('Course code')"
        field="code"
        sortable
      />
      <Column
        v-if="view === 'simple'"
        :header="t('Language')"
        field="courseLanguage"
        sortable
      />
      <Column
        v-if="view === 'simple'"
        :header="t('Categories')"
        field="categories"
      >
        <template #body="{ data }">
          <span
            v-for="cat in data.categories"
            :key="cat.id"
            class="block text-xs"
          >
            {{ cat.name }}
          </span>
        </template>
      </Column>
      <Column
        v-if="view === 'simple'"
        :header="t('Registr. allowed')"
        field="subscribe"
        sortable
      >
        <template #body="{ data }">
          <span :class="data.subscribe ? 'mdi mdi-check text-green-600' : 'mdi mdi-close text-red-500'" />
        </template>
      </Column>
      <Column
        v-if="view === 'simple'"
        :header="t('Unreg. allowed')"
        field="unsubscribe"
        sortable
      >
        <template #body="{ data }">
          <span :class="data.unsubscribe ? 'mdi mdi-check text-green-600' : 'mdi mdi-close text-red-500'" />
        </template>
      </Column>

      <!-- Admin view columns -->
      <Column
        v-if="view === 'admin'"
        :header="t('Creation date')"
        field="creationDate"
        sortable
      />
      <Column
        v-if="view === 'admin'"
        :header="t('Latest access in course')"
        field="lastAccess"
      />
      <Column
        v-if="view === 'admin'"
        :header="t('Teachers')"
        field="teachers"
      >
        <template #body="{ data }">
          <span
            v-for="teacher in data.teachers"
            :key="teacher.id"
            class="block text-xs"
          >
            {{ teacher.name }}
          </span>
        </template>
      </Column>

      <!-- Visibility (both views) -->
      <Column
        :header="t('Visibility')"
        field="visibility"
        sortable
      >
        <template #body="{ data }">
          <span
            :class="visibilityIcon(data.visibility)"
            :title="data.visibilityLabel"
          />
        </template>
      </Column>

      <!-- Actions column -->
      <Column
        :header="t('Actions')"
        field="id"
      >
        <template #body="{ data }">
          <div class="flex gap-1 flex-nowrap">
            <!-- Information -->
            <a
              :href="`/main/admin/course_information.php?code=${encodeURIComponent(data.code)}`"
              :title="t('Information')"
            >
              <span class="mdi mdi-information ch-tool-icon" />
            </a>

            <!-- Course home -->
            <a
              v-if="view === 'simple'"
              :href="`/course/${data.id}/home`"
              :title="t('Course home')"
            >
              <span class="mdi mdi-home ch-tool-icon" />
            </a>

            <!-- Reporting -->
            <a
              :href="`/main/tracking/courseLog.php?cid=${data.id}`"
              :title="t('Reporting')"
            >
              <span class="mdi mdi-chart-box ch-tool-icon" />
            </a>

            <!-- Edit -->
            <a
              :href="`/main/admin/course_edit.php?id=${data.id}`"
              :title="t('Edit')"
            >
              <span class="mdi mdi-pencil ch-tool-icon" />
            </a>

            <!-- Backup -->
            <a
              :href="`/main/course_copy/create_backup.php?cid=${data.id}`"
              :title="t('Create a backup')"
            >
              <span class="mdi mdi-archive ch-tool-icon" />
            </a>

            <!-- Catalogue toggle (simple view only) -->
            <a
              v-if="view === 'simple'"
              :title="data.inCatalogue ? t('Remove from catalogue') : t('Add to catalogue')"
              class="cursor-pointer"
              @click.prevent="toggleCatalogue(data)"
            >
              <span
                :class="data.inCatalogue ? 'mdi mdi-book-minus-outline ch-tool-icon' : 'mdi mdi-book-plus ch-tool-icon'"
              />
            </a>

            <!-- Delete -->
            <a
              :title="t('Delete')"
              class="cursor-pointer"
              @click.prevent="confirmDelete(data)"
            >
              <span class="mdi mdi-delete ch-tool-icon text-red-600" />
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
const sortField = ref("title")
const sortOrder = ref(1)
const view = ref("simple")
const selectedItems = ref([])

const simpleKeyword = ref("")
const showAdvanced = ref(false)

const advancedFilters = reactive({
  keyword_code: "",
  keyword_title: "",
  keyword_category: "",
  keyword_language: "",
  keyword_visibility: "",
  keyword_subscribe: "",
  keyword_unsubscribe: "",
  course_teacher_input: "",
})

const csrfToken = ref("")

function visibilityIcon(visibility) {
  const map = {
    0: "mdi mdi-eye-off-outline ch-tool-icon",
    1: "mdi mdi-eye-off ch-tool-icon",
    2: "mdi mdi-eye-outline ch-tool-icon",
    3: "mdi mdi-eye ch-tool-icon",
    4: "mdi mdi-eye-closed ch-tool-icon",
  }
  return map[visibility] || "mdi mdi-help-circle ch-tool-icon"
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

    if (showAdvanced.value) {
      for (const [key, val] of Object.entries(advancedFilters)) {
        if (key === "course_teacher_input" && val) {
          params.append("course_teachers[]", val)
        } else if (typeof val === "string" && val) {
          params.set(key, val)
        }
      }
    } else if (simpleKeyword.value) {
      params.set("keyword", simpleKeyword.value)
    }

    const data = await baseService.get(`/admin/course-list-data?${params.toString()}`)
    items.value = data.items
    total.value = data.total
    if (data.csrfToken) {
      csrfToken.value = data.csrfToken
    }
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
  sortField.value = event.sortField ?? "title"
  sortOrder.value = event.sortOrder ?? 1
  page.value = 1
  load()
}

function onSearch() {
  page.value = 1
  load()
}

function onAdvancedSearch() {
  page.value = 1
  load()
}

function switchView(newView) {
  view.value = newView
  page.value = 1
  selectedItems.value = []
  load()
}

function confirmDelete(data) {
  if (!confirm(t("Please confirm your choice"))) return
  if (!confirm(t("Are you sure you want to delete this course?"))) return

  const form = document.createElement("form")
  form.method = "POST"
  form.action = "/admin/course-list-action"

  const fields = { action: "delete_course", course_id: data.id, _token: csrfToken.value }
  for (const [k, v] of Object.entries(fields)) {
    const input = document.createElement("input")
    input.type = "hidden"
    input.name = k
    input.value = v
    form.appendChild(input)
  }
  document.body.appendChild(form)
  form.submit()
}

function confirmBulkDelete() {
  if (!confirm(t("Please confirm your choice"))) return
  if (!confirm(t("Are you sure you want to delete the selected courses?"))) return

  const form = document.createElement("form")
  form.method = "POST"
  form.action = "/admin/course-list-action"

  const tokenInput = document.createElement("input")
  tokenInput.type = "hidden"
  tokenInput.name = "_token"
  tokenInput.value = csrfToken.value
  form.appendChild(tokenInput)

  const actionInput = document.createElement("input")
  actionInput.type = "hidden"
  actionInput.name = "action"
  actionInput.value = "delete_courses"
  form.appendChild(actionInput)

  for (const item of selectedItems.value) {
    const input = document.createElement("input")
    input.type = "hidden"
    input.name = "course_ids[]"
    input.value = item.id
    form.appendChild(input)
  }

  document.body.appendChild(form)
  form.submit()
}

async function toggleCatalogue(data) {
  try {
    const formData = new URLSearchParams()
    formData.append("action", "toggle_catalogue")
    formData.append("course_id", String(data.id))
    formData.append("_token", csrfToken.value)

    const res = await fetch("/admin/course-list-action", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: formData.toString(),
    })

    if (res.ok) {
      const result = await res.json()
      data.inCatalogue = result.inCatalogue
    }
  } catch (e) {
    console.error(e)
  }
}

onMounted(load)
</script>
