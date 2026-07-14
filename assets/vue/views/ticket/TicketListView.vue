<template>
  <section class="space-y-4">
    <div
      v-if="errorMessage"
      class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700"
      role="alert"
    >
      {{ errorMessage }}
    </div>

    <template v-else-if="!isLoading && projects.length === 0">
      <BaseToolbar v-if="isAdmin">
        <template #start>
          <BaseButton
            icon="settings"
            :label="t('Settings')"
            only-icon
            size="normal"
            :route="{ name: 'TicketSettings' }"
            type="secondary"
          />
        </template>
      </BaseToolbar>

      <div
        class="rounded-lg border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-800"
        role="status"
      >
        {{ t("No results found") }}
      </div>
    </template>

    <template v-else>
      <BaseToolbar>
        <template #start>
          <div class="flex flex-wrap items-center gap-2">
            <BaseButton
              v-if="canCreate && selectedProjectId"
              icon="plus"
              :label="t('Add')"
              only-icon
              size="normal"
              :route="{ name: 'TicketCreate', query: { project_id: String(selectedProjectId) } }"
              type="success"
            />

            <BaseButton
              :icon="isSearchVisible ? 'close' : 'search'"
              :label="isSearchVisible ? t('Cancel') : t('Search')"
              only-icon
              size="normal"
              type="primary"
              @click="toggleSearch"
            />

            <BaseButton
              v-if="isAdmin && selectedProjectId"
              icon="file-excel"
              :label="t('Export')"
              only-icon
              size="normal"
              :to-url="exportUrl"
              type="primary"
            />

            <BaseButton
              v-if="isAdmin"
              icon="folder-backup"
              :is-loading="isClosingOldTickets"
              :label="t('Close old tickets')"
              only-icon
              size="normal"
              type="secondary"
              @click="confirmCloseOldTickets"
            />

            <BaseButton
              v-if="isAdmin"
              icon="settings"
              :label="t('Settings')"
              only-icon
              size="normal"
              :route="{ name: 'TicketSettings', query: { project_id: String(selectedProjectId || '') } }"
              type="secondary"
            />
          </div>
        </template>
      </BaseToolbar>

      <form
        v-if="isSearchVisible"
        class="rounded-xl border border-gray-20 bg-white p-4 shadow-sm"
        @submit.prevent="applyFilters"
      >
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3 lg:items-end">
          <BaseSelect
            id="ticket-project-filter"
            v-model="pendingFilters.projectId"
            class="w-full"
            :label="t('Project')"
            name="ticket_project_id"
            option-label="title"
            option-value="id"
            :options="projects"
            @change="changeProject"
          />

          <BaseInputText
            id="ticket-keyword-filter"
            v-model="pendingFilters.keyword"
            class="w-full"
            :label="t('Keyword')"
            name="ticket_keyword"
          />

          <BaseInputText
            id="ticket-course-filter"
            v-model="pendingFilters.course"
            class="w-full"
            :label="t('Course')"
            name="ticket_course"
          />

          <BaseSelect
            id="ticket-category-filter"
            v-model="pendingFilters.categoryId"
            :allow-clear="true"
            class="w-full"
            :label="t('Category')"
            name="ticket_category_id"
            option-label="label"
            option-value="id"
            :options="categories"
          />

          <BaseSelect
            id="ticket-status-filter"
            v-model="pendingFilters.statusId"
            :allow-clear="true"
            class="w-full"
            :label="t('Status')"
            name="ticket_status_id"
            option-label="label"
            option-value="id"
            :options="statuses"
          />

          <BaseSelect
            id="ticket-priority-filter"
            v-model="pendingFilters.priorityId"
            :allow-clear="true"
            class="w-full"
            :label="t('Priority')"
            name="ticket_priority_id"
            option-label="label"
            option-value="id"
            :options="priorities"
          />

          <BaseSelect
            id="ticket-assignee-filter"
            v-model="pendingFilters.assignedUserId"
            :allow-clear="true"
            class="w-full"
            :label="t('Assigned to')"
            name="ticket_assigned_user_id"
            option-label="label"
            option-value="id"
            :options="assigneeOptions"
          />

          <div class="w-full self-start">
            <BaseInputText
              id="ticket-start-date-filter"
              v-model="pendingFilters.startDate"
              class="w-full"
              :label="t('Created')"
              name="ticket_start_date"
              type="date"
            />
          </div>

          <div class="w-full self-start">
            <BaseInputText
              id="ticket-end-date-filter"
              v-model="pendingFilters.endDate"
              class="w-full"
              :label="t('Until')"
              name="ticket_end_date"
              type="date"
            />
          </div>
        </div>

        <div class="mt-4 flex flex-wrap justify-end gap-2">
          <BaseButton
            icon="search"
            is-submit
            :label="t('Search')"
            type="primary"
          />

          <BaseButton
            v-if="hasActiveFilters"
            icon="close"
            :label="t('Clear')"
            type="secondary"
            @click="clearFilters"
          />

          <BaseButton
            v-else
            icon="close"
            :label="t('Cancel')"
            type="plain"
            @click="toggleSearch"
          />
        </div>
      </form>

      <div
        v-if="!isLoading && !canViewAll && selectedProjectId"
        class="rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800"
        role="status"
      >
        {{
          t(
            "Welcome to YOUR tickets section. Here, you'll be able to track the state of all the tickets " +
              "you created in the main tickets section.",
          )
        }}
      </div>

      <BaseTable
        v-model:rows="rows"
        v-model:sort-field="sortField"
        v-model:sort-order="sortOrder"
        data-key="id"
        :is-loading="isLoading"
        lazy
        :text-for-empty="t('No results found')"
        :total-items="totalItems"
        :values="tickets"
        @page="handlePage"
        @sort="handleSort"
      >
        <Column
          field="code"
          :header="t('Ticket number')"
          sortable
        >
          <template #body="{ data }">
            <div class="flex min-w-0 items-start gap-2">
              <BaseIcon
                icon="ticket"
                size="small"
              />
              <div class="min-w-0">
                <router-link
                  class="break-words font-semibold text-primary hover:underline"
                  :to="{ name: 'TicketDetail', params: { id: data.id } }"
                >
                  {{ data.code }}
                </router-link>
                <p class="mt-1 break-words text-sm text-gray-700">
                  {{ data.subject }}
                </p>
              </div>
            </div>
          </template>
        </Column>

        <Column
          field="status"
          :header="t('Status')"
          sortable
        >
          <template #body="{ data }">
            <span
              class="inline-flex rounded-full px-2 py-1 text-xs font-medium"
              :class="statusClass(data.status?.code)"
            >
              {{ data.status?.title || "-" }}
            </span>
          </template>
        </Column>

        <Column
          field="createdAt"
          :header="t('Date')"
          sortable
        >
          <template #body="{ data }">
            {{ formatDate(data.createdAt) }}
          </template>
        </Column>

        <Column
          field="updatedAt"
          :header="t('Last update')"
          sortable
        >
          <template #body="{ data }">
            {{ formatDate(data.updatedAt) }}
          </template>
        </Column>

        <Column
          field="category"
          :header="t('Category')"
          sortable
        >
          <template #body="{ data }">
            {{ data.category?.title || "-" }}
          </template>
        </Column>

        <Column
          v-if="isAdmin"
          field="creator"
          :header="t('Created by')"
          sortable
        >
          <template #body="{ data }">
            {{ data.creator?.fullName || data.creator?.username || "-" }}
          </template>
        </Column>

        <Column
          v-if="isAdmin"
          field="assignee"
          :header="t('Assigned to')"
          sortable
        >
          <template #body="{ data }">
            {{ data.assignee?.fullName || data.assignee?.username || t("Unassigned") }}
          </template>
        </Column>

        <Column
          v-if="isAdmin"
          field="totalMessages"
          :header="t('Message')"
          sortable
        />
      </BaseTable>
    </template>
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import Column from "primevue/column"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue"
import { useConfirmation } from "../../composables/useConfirmation"
import { useNotification } from "../../composables/notification"
import ticketService from "../../services/ticketService"

const { t, locale } = useI18n()
const route = useRoute()
const router = useRouter()
const { requireConfirmation } = useConfirmation()
const { showSuccessNotification, showErrorNotification } = useNotification()

const tickets = ref([])
const projects = ref([])
const categories = ref([])
const statuses = ref([])
const priorities = ref([])
const assignees = ref([])
const totalItems = ref(0)
const rows = ref(20)
const currentPage = ref(1)
const sortField = ref("id")
const sortOrder = ref(-1)
const isLoading = ref(false)
const isClosingOldTickets = ref(false)
const errorMessage = ref("")
const isSearchVisible = ref(false)
const isAdmin = ref(false)
const canViewAll = ref(false)
const canCreate = ref(false)
const selectedProjectId = ref(0)
const csrfToken = ref("")

const emptyFilters = () => ({
  projectId: Number(route.query.project_id || 0) || null,
  keyword: String(route.query.keyword || ""),
  categoryId: Number(route.query.category_id || 0) || null,
  statusId: Number(route.query.status_id || 0) || null,
  priorityId: Number(route.query.priority_id || 0) || null,
  assignedUserId: route.query.assigned_user_id === undefined ? null : Number(route.query.assigned_user_id),
  course: String(route.query.course || ""),
  startDate: String(route.query.start_date || ""),
  endDate: String(route.query.end_date || ""),
})

const filters = reactive(emptyFilters())
const pendingFilters = reactive(emptyFilters())

const assigneeOptions = computed(() => [
  {
    id: 0,
    label: t("Unassigned"),
    username: "",
  },
  ...assignees.value,
])

const hasActiveFilters = computed(() =>
  Boolean(
    filters.keyword ||
    filters.categoryId ||
    filters.statusId ||
    filters.priorityId ||
    filters.assignedUserId !== null ||
    filters.course ||
    filters.startDate ||
    filters.endDate,
  ),
)

const exportUrl = computed(() => {
  const params = new URLSearchParams({ projectId: String(selectedProjectId.value) })
  if (filters.keyword) params.set("keyword", filters.keyword)
  if (filters.categoryId) params.set("categoryId", String(filters.categoryId))
  if (filters.statusId) params.set("statusId", String(filters.statusId))
  if (filters.priorityId) params.set("priorityId", String(filters.priorityId))
  if (filters.assignedUserId !== null) params.set("assignedUserId", String(filters.assignedUserId))
  if (filters.course) params.set("course", filters.course)
  if (filters.startDate) params.set("startDate", filters.startDate)
  if (filters.endDate) params.set("endDate", filters.endDate)

  return `/api/ticket/admin/export?${params.toString()}`
})

onMounted(loadTickets)

async function loadTickets() {
  isLoading.value = true
  errorMessage.value = ""

  try {
    const response = await ticketService.getList({
      projectId: filters.projectId || undefined,
      page: currentPage.value,
      itemsPerPage: rows.value,
      keyword: filters.keyword || undefined,
      categoryId: filters.categoryId || undefined,
      statusId: filters.statusId || undefined,
      priorityId: filters.priorityId || undefined,
      assignedUserId: filters.assignedUserId ?? undefined,
      course: filters.course || undefined,
      startDate: filters.startDate || undefined,
      endDate: filters.endDate || undefined,
      sortField: sortField.value || "id",
      sortDirection: sortOrder.value === 1 ? "asc" : "desc",
    })

    tickets.value = Array.isArray(response.items) ? response.items : []
    projects.value = Array.isArray(response.projects) ? response.projects : []
    categories.value = Array.isArray(response.categories) ? response.categories : []
    statuses.value = Array.isArray(response.statuses) ? response.statuses : []
    priorities.value = Array.isArray(response.priorities) ? response.priorities : []
    assignees.value = Array.isArray(response.assignees) ? response.assignees : []
    totalItems.value = Number(response.totalItems || 0)
    isAdmin.value = Boolean(response.isAdmin)
    canViewAll.value = Boolean(response.canViewAll)
    canCreate.value = Boolean(response.canCreate)
    selectedProjectId.value = Number(response.projectId || 0)
    csrfToken.value = response.csrfToken || ""

    if (!filters.projectId && selectedProjectId.value) {
      filters.projectId = selectedProjectId.value
      pendingFilters.projectId = selectedProjectId.value
    }

    syncRouteQuery()
  } catch (error) {
    console.error("[TicketList] Failed to load tickets", error)
    errorMessage.value = t("An error occurred")
    tickets.value = []
    totalItems.value = 0
  } finally {
    isLoading.value = false
  }
}

function toggleSearch() {
  isSearchVisible.value = !isSearchVisible.value
}

function applyFilters() {
  Object.assign(filters, pendingFilters)
  currentPage.value = 1
  loadTickets()
}

function clearFilters() {
  const projectId = pendingFilters.projectId || selectedProjectId.value || null
  Object.assign(pendingFilters, {
    projectId,
    keyword: "",
    categoryId: null,
    statusId: null,
    priorityId: null,
    assignedUserId: null,
    course: "",
    startDate: "",
    endDate: "",
  })
  Object.assign(filters, pendingFilters)
  currentPage.value = 1
  loadTickets()
}

function changeProject() {
  Object.assign(filters, pendingFilters)
  currentPage.value = 1
  loadTickets()
}

function handlePage(event) {
  rows.value = Number(event.rows || rows.value)
  currentPage.value = Number(event.page || 0) + 1
  loadTickets()
}

function handleSort(event) {
  sortField.value = event.sortField || "id"
  sortOrder.value = Number(event.sortOrder || -1)
  currentPage.value = 1
  loadTickets()
}

function syncRouteQuery() {
  const query = {}

  if (filters.projectId) query.project_id = String(filters.projectId)
  if (filters.keyword) query.keyword = filters.keyword
  if (filters.categoryId) query.category_id = String(filters.categoryId)
  if (filters.statusId) query.status_id = String(filters.statusId)
  if (filters.priorityId) query.priority_id = String(filters.priorityId)
  if (filters.assignedUserId !== null) query.assigned_user_id = String(filters.assignedUserId)
  if (filters.course) query.course = filters.course
  if (filters.startDate) query.start_date = filters.startDate
  if (filters.endDate) query.end_date = filters.endDate

  router.replace({ name: "TicketList", query })
}

function confirmCloseOldTickets() {
  requireConfirmation({
    message: t("Close tickets older than seven days?"),
    accept: closeOldTickets,
  })
}

async function closeOldTickets() {
  if (isClosingOldTickets.value) return
  isClosingOldTickets.value = true
  try {
    const response = await ticketService.closeOldTickets(csrfToken.value)
    showSuccessNotification(`${response.message || t("Update successful")} (${Number(response.count || 0)})`)
    await loadTickets()
  } catch (error) {
    console.error("[TicketList] Failed to close old tickets", error)
    showErrorNotification(getErrorMessage(error))
  } finally {
    isClosingOldTickets.value = false
  }
}

function getErrorMessage(error) {
  return (
    error?.response?.data?.detail ||
    error?.response?.data?.error ||
    error?.response?.data?.["hydra:description"] ||
    t("An error occurred")
  )
}

function formatDate(value) {
  if (!value) {
    return "-"
  }

  const date = new Date(value)
  if (Number.isNaN(date.getTime())) {
    return "-"
  }

  const intlLocale = String(locale.value || "en-US").replace(/_/g, "-")

  return new Intl.DateTimeFormat(intlLocale, {
    dateStyle: "medium",
    timeStyle: "short",
  }).format(date)
}

function statusClass(code) {
  switch (String(code || "")) {
    case "1":
      return "bg-blue-100 text-blue-700"
    case "2":
    case "3":
      return "bg-yellow-100 text-yellow-800"
    case "4":
      return "bg-gray-100 text-gray-700"
    case "5":
      return "bg-green-100 text-green-700"
    default:
      return "bg-gray-100 text-gray-700"
  }
}
</script>
