<template>
  <section class="space-y-6">
    <SectionHeader :title="t('Classes')" />

    <BaseToolbar>
      <template #start>
        <div class="flex flex-wrap items-center gap-2">
          <BaseButton
            :label="t('Learners')"
            :route="buildUsersRoute()"
            icon="account"
            type="primary-text"
          />
          <BaseButton
            :label="t('Teachers')"
            :route="buildTeachersRoute()"
            icon="human-male-board"
            type="primary-text"
          />
          <BaseButton
            :label="t('Groups')"
            :route="buildGroupsRoute()"
            icon="join-group"
            type="primary-text"
          />
          <BaseButton
            :label="t('Classes')"
            icon="sessions"
            type="primary"
          />
        </div>
      </template>

      <template #end>
        <div class="flex flex-wrap items-center gap-2">
          <BaseButton
            :label="isRegisteredView ? t('Browse available classes') : t('Back to linked classes')"
            :icon="isRegisteredView ? 'plus' : 'back'"
            only-icon
            size="normal"
            :tooltip="isRegisteredView ? t('Browse available classes') : t('Back to linked classes')"
            :type="isRegisteredView ? 'success' : 'primary'"
            @click="toggleView"
          />
        </div>
      </template>
    </BaseToolbar>

    <form
      v-if="!isRegisteredView"
      class="rounded-xl border border-gray-20 bg-white p-4 shadow-sm"
      @submit.prevent="applySearch"
    >
      <div class="flex flex-col gap-3 md:flex-row md:items-start">
        <BaseSelect
          id="course-class-group-filter"
          v-model="groupFilterDraft"
          :label="t('Groups')"
          name="group_filter"
          :options="groupFilterOptions"
        />
        <BaseInputText
          id="course-class-search"
          v-model="searchDraft"
          class="flex-1"
          :label="t('Search')"
          name="keyword"
        />
        <div class="flex flex-wrap gap-2 md:pt-1">
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
      v-if="informationMessage"
      class="rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800"
    >
      {{ t(informationMessage) }}
    </div>

    <div
      v-if="errorMessage"
      class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"
    >
      {{ errorMessage }}
    </div>

    <div
      v-if="successMessage"
      class="rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-700"
    >
      {{ successMessage }}
    </div>

    <BaseTable
      v-model:rows="itemsPerPage"
      :is-loading="loading"
      lazy
      :text-for-empty="
        isRegisteredView
          ? t('No classes are currently linked to this course.')
          : t('No available classes were found for this course.')
      "
      :total-items="totalItems"
      :values="classes"
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
        field="users"
        :header="t('Users')"
        sortable
      >
        <template #body="{ data }">
          <a
            v-if="data.usersUrl"
            class="text-primary hover:underline"
            :href="data.usersUrl"
          >
            {{ data.users }}
          </a>
          <span v-else>{{ data.users }}</span>
        </template>
      </Column>

      <Column
        field="status"
        :header="t('Status')"
        sortable
      >
        <template #body="{ data }">
          {{ data.status ? t(data.status) : "-" }}
        </template>
      </Column>

      <Column
        field="groupType"
        :header="t('Type')"
        sortable
      >
        <template #body="{ data }">
          <span
            class="rounded-full px-2 py-1 text-xs"
            :class="data.groupType === SOCIAL_GROUP ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700'"
          >
            {{ t(data.groupTypeLabel) }}
          </span>
        </template>
      </Column>

      <Column :header="t('Detail')">
        <template #body="{ data }">
          <div class="flex items-center justify-center gap-2">
            <BaseButton
              v-if="data.isRegistered"
              :label="t('Overview students subscribed to the class')"
              icon="list"
              only-icon
              size="small"
              :to-url="data.overviewUrl"
              type="primary-text"
            />
            <BaseButton
              v-if="data.statisticsUrl"
              :label="t('Statistics')"
              icon="tracking"
              only-icon
              size="small"
              :to-url="data.statisticsUrl"
              type="primary-text"
            />
            <BaseButton
              v-if="data.canRemoveOnly"
              :label="t('Remove the class without removing students')"
              icon="restore"
              only-icon
              size="small"
              type="secondary-text"
              @click="confirmRemoveOnly(data)"
            />
            <BaseButton
              v-if="data.canRemove"
              :label="t('Remove')"
              icon="delete"
              only-icon
              size="small"
              type="danger-text"
              @click="confirmRemove(data)"
            />
            <BaseButton
              v-if="data.canAdd"
              :label="t('Add')"
              icon="plus"
              only-icon
              size="small"
              type="success-text"
              @click="addClass(data)"
            />
          </div>
        </template>
      </Column>
    </BaseTable>
  </section>
</template>

<script setup>
import { computed, onMounted, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue"
import { useConfirmation } from "../../composables/useConfirmation"
import courseClassService from "../../services/courseClassService"
import { useRouteCourseContext } from "../../composables/useRouteCourseContext"
import { useStudentViewRefresh } from "../../composables/useStudentViewRefresh"
import SectionHeader from "../../components/layout/SectionHeader.vue"

const REGISTERED = "registered"
const AVAILABLE = "not_registered"
const CLASS_GROUP = 0
const SOCIAL_GROUP = 1
const STUDENT = 5
const TEACHER = 1

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const { requireConfirmation } = useConfirmation()
const { cid, sid, gid, contextQuery } = useRouteCourseContext()

const classes = ref([])
const totalItems = ref(0)
const loading = ref(false)
const saving = ref(false)
const errorMessage = ref("")
const successMessage = ref("")
const informationMessage = ref("")
const groupsUrl = ref("")
const page = ref(1)
const itemsPerPage = ref(20)
const sortField = ref("title")
const sortOrder = ref("asc")
const searchDraft = ref("")
const activeSearch = ref("")
const groupFilterDraft = ref(CLASS_GROUP)
const activeGroupFilter = ref(CLASS_GROUP)

const currentView = computed(() => (route.query.view === AVAILABLE ? AVAILABLE : REGISTERED))
const isRegisteredView = computed(() => currentView.value === REGISTERED)
const groupFilterOptions = computed(() => [
  { label: t("All"), value: -1 },
  { label: t("Social groups"), value: SOCIAL_GROUP },
  { label: t("Classes"), value: CLASS_GROUP },
])

function requestParams() {
  return {
    cid: cid.value,
    sid: sid.value,
    gid: gid.value,
    view: currentView.value,
    groupFilter: activeGroupFilter.value,
    search: activeSearch.value,
    page: page.value,
    itemsPerPage: itemsPerPage.value,
    sort: sortField.value,
    order: sortOrder.value,
  }
}

async function loadClasses() {
  loading.value = true
  errorMessage.value = ""

  try {
    const response = await courseClassService.getList(requestParams())
    classes.value = response.items || []
    totalItems.value = Number(response.totalItems || 0)
    groupsUrl.value = response.groupsUrl || ""
    informationMessage.value = response.information || ""
  } catch (error) {
    console.error("[CourseClass] Failed to load classes", error)
    errorMessage.value = error?.response?.data?.detail || error?.message || t("An error occurred")
  } finally {
    loading.value = false
  }
}

function buildGroupsRoute() {
  return { name: "CourseUserGroups", params: route.params, query: contextQuery.value }
}

function buildUsersRoute() {
  return {
    name: "CourseUserList",
    params: route.params,
    query: { ...contextQuery.value, view: undefined, type: STUDENT },
  }
}

function buildTeachersRoute() {
  return {
    name: "CourseUserList",
    params: route.params,
    query: { ...contextQuery.value, view: undefined, type: TEACHER },
  }
}

function toggleView() {
  page.value = 1
  activeSearch.value = ""
  searchDraft.value = ""
  router.push({
    name: "CourseUserClasses",
    params: route.params,
    query: { ...contextQuery.value, view: isRegisteredView.value ? AVAILABLE : REGISTERED },
  })
}

function applySearch() {
  activeSearch.value = searchDraft.value.trim()
  activeGroupFilter.value = Number(groupFilterDraft.value)
  page.value = 1
  loadClasses()
}

function clearSearch() {
  searchDraft.value = ""
  activeSearch.value = ""
  groupFilterDraft.value = CLASS_GROUP
  activeGroupFilter.value = CLASS_GROUP
  page.value = 1
  loadClasses()
}

function onPage(event) {
  page.value = Number(event.page || 0) + 1
  itemsPerPage.value = Number(event.rows || itemsPerPage.value)
  loadClasses()
}

function onSort(event) {
  sortField.value = event.sortField || "title"
  sortOrder.value = Number(event.sortOrder) < 0 ? "desc" : "asc"
  loadClasses()
}

async function runAction(action, usergroup) {
  if (saving.value) {
    return
  }

  saving.value = true
  errorMessage.value = ""
  successMessage.value = ""

  try {
    const response = await action(usergroup.id, requestParams())
    successMessage.value = response.message ? t(response.message) : t("Updated")
    await loadClasses()
  } catch (error) {
    console.error("[CourseClass] Failed to update class relation", error)
    errorMessage.value = error?.response?.data?.detail || error?.message || t("An error occurred")
  } finally {
    saving.value = false
  }
}

function addClass(usergroup) {
  runAction(courseClassService.add, usergroup)
}

function confirmRemove(usergroup) {
  requireConfirmation({
    message: t("Are you sure you want to remove the class?"),
    accept: () => runAction(courseClassService.remove, usergroup),
  })
}

function confirmRemoveOnly(usergroup) {
  requireConfirmation({
    message: t("Are you sure you want to remove the class without removing users?"),
    accept: () => runAction(courseClassService.removeOnly, usergroup),
  })
}

watch(currentView, () => {
  page.value = 1
  loadClasses()
})

onMounted(loadClasses)

useStudentViewRefresh(loadClasses)
</script>
