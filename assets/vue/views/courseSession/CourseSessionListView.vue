<template>
  <section class="space-y-6">
    <BaseToolbar>
      <template #start>
        <BaseButton
          v-if="returnToCourseUsersRoute"
          :label="t('Users')"
          :route="returnToCourseUsersRoute"
          icon="back"
          only-icon
          size="normal"
          :tooltip="t('Users')"
          type="primary"
        />
      </template>
      <template #end>
        <div class="flex flex-wrap items-center gap-2">
          <BaseButton
            v-if="canCreate"
            :label="t('Add a training session')"
            :to-url="createSessionUrl"
            icon="plus"
            only-icon
            size="normal"
            :tooltip="t('Add a training session')"
            type="success"
          />
          <BaseButton
            v-if="canCreate"
            :label="t('Add training sessions to categories')"
            :to-url="addToCategoryUrl"
            icon="folder-plus"
            only-icon
            size="normal"
            :tooltip="t('Add training sessions to categories')"
            type="success"
          />
          <BaseButton
            v-if="canCreate"
            :label="t('Sessions categories list')"
            :to-url="categoriesUrl"
            icon="folder-generic"
            only-icon
            size="normal"
            :tooltip="t('Sessions categories list')"
            type="primary"
          />
          <BaseButton
            :label="searchVisible ? t('Hide search') : t('Search')"
            :icon="searchVisible ? 'close' : 'search'"
            only-icon
            size="normal"
            :tooltip="searchVisible ? t('Hide search') : t('Search')"
            type="primary"
            @click="searchVisible = !searchVisible"
          />
        </div>
      </template>
    </BaseToolbar>

    <form
      v-if="searchVisible"
      class="rounded-xl border border-gray-20 bg-white p-4 shadow-sm"
      @submit.prevent="applySearch"
    >
      <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <BaseInputText
          id="course-session-name-filter"
          v-model="nameDraft"
          :label="t('Name')"
          name="name"
        />
        <BaseInputText
          id="course-session-courses-filter"
          v-model="coursesDraft"
          :label="t('Courses')"
          name="courses"
        />
        <BaseInputText
          id="course-session-users-filter"
          v-model="usersDraft"
          :label="t('Number of users')"
          name="users"
        />
        <BaseInputText
          id="course-session-category-filter"
          v-model="categoryDraft"
          :label="t('Category name')"
          name="category"
        />
        <BaseInputText
          id="course-session-start-date-filter"
          v-model="startDateDraft"
          :label="t('Start Date')"
          name="startDate"
        />
        <BaseInputText
          id="course-session-end-date-filter"
          v-model="endDateDraft"
          :label="t('End Date')"
          name="endDate"
        />
        <BaseSelect
          id="course-session-active-filter"
          v-model="activeDraft"
          :label="t('Status')"
          name="active"
          :options="activeOptions"
        />
        <div class="flex flex-wrap items-end gap-2">
          <BaseButton
            :is-loading="loading"
            :label="t('Search')"
            icon="search"
            is-submit
            type="primary"
          />
          <BaseButton
            :label="t('Clear search')"
            icon="close"
            type="secondary"
            @click="clearSearch"
          />
        </div>
      </div>
    </form>

    <div
      v-if="errorMessage"
      class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"
    >
      {{ errorMessage }}
    </div>

    <BaseTable
      v-model:rows="itemsPerPage"
      :is-loading="loading"
      lazy
      :text-for-empty="t('No sessions found')"
      :total-items="totalItems"
      :values="sessions"
      data-key="id"
      @page="onPage"
      @sort="onSort"
    >
      <Column
        field="title"
        :header="t('Name')"
        sortable
      />
      <Column
        field="nbrCourses"
        :header="t('Courses')"
        sortable
      />
      <Column
        field="nbrUsers"
        :header="t('Number of users')"
        sortable
      />
      <Column
        field="categoryName"
        :header="t('Category name')"
        sortable
      >
        <template #body="{ data }">
          {{ data.categoryName || "-" }}
        </template>
      </Column>
      <Column
        field="accessStartDate"
        :header="t('Start Date')"
        sortable
      >
        <template #body="{ data }">
          {{ data.accessStartDate || "-" }}
        </template>
      </Column>
      <Column
        field="accessEndDate"
        :header="t('End Date')"
        sortable
      >
        <template #body="{ data }">
          {{ data.accessEndDate || "-" }}
        </template>
      </Column>
      <Column
        field="coachName"
        :header="t('Tutor')"
        sortable
      >
        <template #body="{ data }">
          {{ data.coachName || "-" }}
        </template>
      </Column>
      <Column
        field="active"
        :header="t('Status')"
        sortable
      >
        <template #body="{ data }">
          <span
            class="rounded-full px-2 py-1 text-xs"
            :class="data.active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'"
          >
            {{ data.active ? t("active") : t("inactive") }}
          </span>
        </template>
      </Column>
      <Column
        field="visibility"
        :header="t('Visibility')"
        sortable
      />
      <Column :header="t('Detail')">
        <template #body="{ data }">
          <div class="flex items-center justify-center gap-1">
            <BaseButton
              :label="t('Session overview')"
              :route="buildOverviewRoute(data.id)"
              icon="list"
              only-icon
              size="small"
              type="primary-text"
            />
            <BaseButton
              :label="t('Subscribe users to this session')"
              :route="buildUsersRoute(data.id)"
              icon="account-plus"
              only-icon
              size="small"
              type="success-text"
            />
          </div>
        </template>
      </Column>
    </BaseTable>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue"
import courseSessionService from "../../services/courseSessionService"

const { t } = useI18n()
const route = useRoute()

const sessions = ref([])
const totalItems = ref(0)
const loading = ref(false)
const errorMessage = ref("")
const searchVisible = ref(false)
const nameDraft = ref("")
const activeName = ref("")
const coursesDraft = ref("")
const activeCourses = ref("")
const usersDraft = ref("")
const activeUsers = ref("")
const categoryDraft = ref("")
const activeCategory = ref("")
const startDateDraft = ref("")
const activeStartDate = ref("")
const endDateDraft = ref("")
const activeEndDate = ref("")
const activeDraft = ref(1)
const activeFilter = ref(1)
const page = ref(1)
const itemsPerPage = ref(20)
const sortField = ref("")
const sortOrder = ref("asc")
const canCreate = ref(false)
const createSessionUrl = ref("")
const addToCategoryUrl = ref("")
const categoriesUrl = ref("")

const activeOptions = computed(() => [
  { label: t("All"), value: -1 },
  { label: t("active"), value: 1 },
  { label: t("inactive"), value: 0 },
])
const returnToCourseUsersRoute = computed(() => {
  const node = String(route.query.courseUserNode || "")

  if (!node) {
    return null
  }

  return {
    name: "CourseUserList",
    params: { node },
    query: {
      cid: route.query.cid || undefined,
      sid: route.query.sid || undefined,
      gid: route.query.gid || undefined,
      type: route.query.courseUserType || undefined,
    },
  }
})

function requestParams() {
  return {
    name: activeName.value,
    courses: activeCourses.value,
    users: activeUsers.value,
    category: activeCategory.value,
    startDate: activeStartDate.value,
    endDate: activeEndDate.value,
    active: activeFilter.value,
    page: page.value,
    itemsPerPage: itemsPerPage.value,
    sort: sortField.value || undefined,
    order: sortField.value ? sortOrder.value : undefined,
  }
}

async function loadSessions() {
  loading.value = true
  errorMessage.value = ""

  try {
    const response = await courseSessionService.getList(requestParams())
    sessions.value = response.items || []
    totalItems.value = Number(response.totalItems || 0)
    canCreate.value = Boolean(response.canCreate)
    createSessionUrl.value = response.createSessionUrl || ""
    addToCategoryUrl.value = response.addToCategoryUrl || ""
    categoriesUrl.value = response.categoriesUrl || ""
  } catch (error) {
    console.error("[CourseSession] Failed to load sessions", error)
    errorMessage.value = error?.response?.data?.detail || error?.message || t("An error occurred")
  } finally {
    loading.value = false
  }
}

function applySearch() {
  activeName.value = nameDraft.value.trim()
  activeCourses.value = coursesDraft.value.trim()
  activeUsers.value = usersDraft.value.trim()
  activeCategory.value = categoryDraft.value.trim()
  activeStartDate.value = startDateDraft.value.trim()
  activeEndDate.value = endDateDraft.value.trim()
  activeFilter.value = Number(activeDraft.value)
  page.value = 1
  loadSessions()
}

function clearSearch() {
  nameDraft.value = ""
  activeName.value = ""
  coursesDraft.value = ""
  activeCourses.value = ""
  usersDraft.value = ""
  activeUsers.value = ""
  categoryDraft.value = ""
  activeCategory.value = ""
  startDateDraft.value = ""
  activeStartDate.value = ""
  endDateDraft.value = ""
  activeEndDate.value = ""
  activeDraft.value = 1
  activeFilter.value = 1
  page.value = 1
  loadSessions()
}

function onPage(event) {
  page.value = Number(event.page || 0) + 1
  itemsPerPage.value = Number(event.rows || itemsPerPage.value)
  loadSessions()
}

function onSort(event) {
  sortField.value = event.sortField || "title"
  sortOrder.value = Number(event.sortOrder) < 0 ? "desc" : "asc"
  loadSessions()
}

function buildOverviewRoute(sessionId) {
  return { name: "CourseSessionOverview", params: { sessionId }, query: { ...route.query } }
}

function buildUsersRoute(sessionId) {
  return { name: "CourseSessionUsers", params: { sessionId }, query: { ...route.query, view: "available" } }
}

onMounted(() => {
  nameDraft.value = String(route.query.name || route.query.search || "")
  activeName.value = nameDraft.value
  coursesDraft.value = String(route.query.courses || "")
  activeCourses.value = coursesDraft.value
  usersDraft.value = String(route.query.users || "")
  activeUsers.value = usersDraft.value
  categoryDraft.value = String(route.query.category || "")
  activeCategory.value = categoryDraft.value
  startDateDraft.value = String(route.query.startDate || "")
  activeStartDate.value = startDateDraft.value
  endDateDraft.value = String(route.query.endDate || "")
  activeEndDate.value = endDateDraft.value
  activeDraft.value = Number(route.query.active ?? 1)
  activeFilter.value = activeDraft.value
  sortField.value = String(route.query.sort || "")
  sortOrder.value = String(route.query.order || "asc") === "desc" ? "desc" : "asc"
  loadSessions()
})
</script>
