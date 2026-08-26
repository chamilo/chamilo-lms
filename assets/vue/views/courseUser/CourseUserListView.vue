<template>
  <section class="space-y-6">
    <SectionHeader :title="t('Users')" />

    <BaseToolbar>
      <template #start>
        <div
          v-if="permissions.showSubscriptionTabs"
          class="flex flex-wrap items-center gap-2"
        >
          <BaseButton
            :label="t('Learners')"
            icon="account"
            :type="userType === STUDENT ? 'primary' : 'primary-text'"
            @click="changeType(STUDENT)"
          />
          <BaseButton
            :label="t('Teachers')"
            icon="human-male-board"
            :type="userType === TEACHER ? 'primary' : 'primary-text'"
            @click="changeType(TEACHER)"
          />
          <BaseButton
            :label="t('Groups')"
            :route="buildGroupsRoute()"
            icon="join-group"
            type="primary-text"
          />
          <BaseButton
            v-if="permissions.showClasses"
            :label="t('Classes')"
            :route="buildClassesRoute()"
            icon="sessions"
            type="primary-text"
          />
        </div>
      </template>

      <template #end>
        <div class="flex flex-wrap items-center gap-2">
          <BaseButton
            v-if="permissions.canSubscribe"
            :label="t('Add')"
            :route="buildSubscribeRoute()"
            icon="user-add"
            only-icon
            size="normal"
            :tooltip="t('Add')"
            type="success"
          />
          <BaseButton
            v-if="permissions.canInviteByEmail"
            :label="t('Invite by email')"
            :route="buildInvitationRoute()"
            icon="email-outline"
            only-icon
            size="normal"
            :tooltip="t('Invite by email')"
            type="success"
          />
          <BaseButton
            v-if="permissions.canImport"
            :label="t('Import users list')"
            :route="buildImportRoute()"
            icon="import"
            only-icon
            size="normal"
            :tooltip="t('Import users list')"
            type="success"
          />
          <BaseButton
            v-if="permissions.canManage"
            :label="t('CSV export')"
            icon="file-delimited-outline"
            only-icon
            size="normal"
            :tooltip="t('CSV export')"
            type="primary"
            @click="exportFile('csv')"
          />
          <BaseButton
            v-if="permissions.canManage"
            :label="t('Excel export')"
            icon="file-excel"
            only-icon
            size="normal"
            :tooltip="t('Excel export')"
            type="primary"
            @click="exportFile('xlsx')"
          />
          <BaseButton
            v-if="permissions.canManage"
            :label="t('Export to PDF')"
            icon="file-pdf"
            only-icon
            size="normal"
            :tooltip="t('Export to PDF')"
            type="primary"
            @click="exportFile('pdf')"
          />
          <BaseButton
            v-if="permissions.showSessionManagement"
            :label="t('Course sessions')"
            :route="buildSessionsRoute()"
            icon="sessions"
            only-icon
            size="normal"
            :tooltip="t('Course sessions')"
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
      <div class="flex flex-col gap-3 md:flex-row md:items-start">
        <BaseInputText
          id="course-user-search"
          v-model="searchDraft"
          class="flex-1"
          :label="t('Search users')"
          name="search"
        />
        <BaseSelect
          id="course-user-active-filter"
          v-model="activeDraft"
          :label="t('Status')"
          name="active"
          :options="activeOptions"
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
      v-if="warningMessage"
      class="rounded-xl border border-orange-200 bg-orange-50 p-4 text-sm text-orange-800"
    >
      <p>{{ warningMessage }}</p>
      <p
        v-if="showUpgradeCta"
        class="mt-1"
      >
        {{ t("User subscriptions are limited through your course properties. To increase your limit, get a") }}
        <a
          class="font-semibold text-primary underline"
          href="/resources/courses/new"
        >
          {{ t("pro plan") }}
        </a>
        {{
          t(
            "and import this course's backup through Course Maintenance to your new paid course, or open a ticket to get your course converted into a pro course once you've acquired this plan.",
          )
        }}
      </p>
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

    <div
      v-if="selectedUserIds.length > 0 && permissions.canUnsubscribe"
      class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-gray-20 bg-white px-4 py-3 shadow-sm"
    >
      <span class="font-semibold text-gray-800">
        {{ t("{0} users selected", [selectedUserIds.length]) }}
      </span>
      <div class="flex flex-wrap gap-2">
        <BaseButton
          :label="t('Unsubscribe')"
          icon="user-delete"
          size="small"
          type="danger"
          @click="confirmBulkUnsubscribe"
        />
        <BaseButton
          :label="t('Clear selection')"
          icon="close"
          size="small"
          type="plain"
          @click="selectedUserIds = []"
        />
      </div>
    </div>

    <BaseTable
      v-model:rows="itemsPerPage"
      :is-loading="loading"
      lazy
      :text-for-empty="t('No users found')"
      :total-items="totalItems"
      :values="users"
      data-key="id"
      @page="onPage"
      @sort="onSort"
    >
      <Column
        v-if="permissions.canUnsubscribe"
        class="w-12"
      >
        <template #header>
          <input
            :aria-label="t('Select all')"
            class="h-4 w-4 cursor-pointer rounded border-gray-30"
            type="checkbox"
            :checked="allSelectableSelected"
            :disabled="selectableUsers.length === 0"
            :indeterminate.prop="selectionIndeterminate"
            @change="toggleAll($event.target.checked)"
          />
        </template>
        <template #body="{ data }">
          <input
            v-if="data.canUnsubscribe"
            :aria-label="t('Select user')"
            class="h-4 w-4 cursor-pointer rounded border-gray-30"
            type="checkbox"
            :checked="selectedUserIds.includes(data.id)"
            @change="toggleSelection(data.id, $event.target.checked)"
          />
        </template>
      </Column>

      <Column :header="t('Photo')">
        <template #body="{ data }">
          <BaseUserAvatar
            :alt="data.fullName || data.username"
            :image-url="data.pictureUrl"
          />
        </template>
      </Column>

      <Column
        v-if="!isHidden('official_code')"
        field="officialCode"
        :header="t('Code')"
        sortable
      />

      <Column
        v-if="permissions.westernNameOrder && !isHidden('firstname')"
        field="firstname"
        :header="t('First name')"
        sortable
      />

      <Column
        v-if="!isHidden('lastname')"
        field="lastname"
        :header="t('Last name')"
        sortable
      />

      <Column
        v-if="!permissions.westernNameOrder && !isHidden('firstname')"
        field="firstname"
        :header="t('First name')"
        sortable
      />

      <Column
        v-if="!isHidden('username')"
        field="username"
        :header="t('Login')"
        sortable
      />

      <Column
        v-if="!isHidden('groups')"
        :header="t('Group')"
      >
        <template #body="{ data }">
          {{ data.groups?.join(", ") || "-" }}
        </template>
      </Column>

      <Column
        v-if="permissions.canManage"
        :header="t('Status')"
      >
        <template #body="{ data }">
          <span class="rounded-full bg-gray-100 px-2 py-1 text-xs text-gray-700">
            {{ t(data.status) }}
          </span>
        </template>
      </Column>

      <Column
        v-if="permissions.canManage"
        :header="t('active')"
      >
        <template #body="{ data }">
          <BaseIcon
            :icon="data.active ? 'check' : 'alert'"
            :class="data.active ? 'text-success' : 'text-danger'"
          />
        </template>
      </Column>

      <Column
        v-for="field in extraFields"
        :key="field.id"
        :header="field.label"
      >
        <template #body="{ data }">
          {{ data.extraValues?.[String(field.id)] || "-" }}
        </template>
      </Column>

      <Column :header="t('Action')">
        <template #body="{ data }">
          <div class="flex items-center justify-end gap-1">
            <BaseButton
              v-if="data.canReport"
              :label="t('Reporting')"
              :to-url="data.reportingUrl"
              icon="tracking"
              only-icon
              size="small"
              type="primary-text"
            />
            <BaseButton
              v-if="data.canLoginAs"
              :label="t('Login as')"
              :to-url="data.loginAsUrl"
              icon="account-key"
              only-icon
              size="small"
              type="primary-text"
            />
            <BaseButton
              v-if="data.canEdit"
              :label="t('Edit')"
              :to-url="data.editUrl"
              icon="edit"
              only-icon
              size="small"
              type="secondary-text"
            />
            <BaseButton
              v-if="data.canSetTutor"
              :label="data.isTutor ? t('Remove assistant role') : t('Convert to assistant')"
              :icon="data.isTutor ? 'account-cancel' : 'account-check'"
              only-icon
              size="small"
              type="secondary-text"
              @click="confirmTutorChange(data)"
            />
            <BaseButton
              v-if="data.canUnsubscribe"
              :label="t('Unsubscribe')"
              icon="user-delete"
              only-icon
              size="small"
              type="danger-text"
              @click="confirmUnsubscribe(data)"
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
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue"
import BaseUserAvatar from "../../components/basecomponents/BaseUserAvatar.vue"
import { useConfirmation } from "../../composables/useConfirmation"
import courseUserService from "../../services/courseUserService"
import { useRouteCourseContext } from "../../composables/useRouteCourseContext"
import { useStudentViewRefresh } from "../../composables/useStudentViewRefresh"
import SectionHeader from "../../components/layout/SectionHeader.vue"

const STUDENT = 5
const TEACHER = 1

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const { requireConfirmation } = useConfirmation()
const { cid, sid, gid, contextQuery } = useRouteCourseContext()

const users = ref([])
const totalItems = ref(0)
const loading = ref(false)
const errorMessage = ref("")
const successMessage = ref("")
const warningMessage = ref("")
const showUpgradeCta = ref(false)
const selectedUserIds = ref([])
const searchVisible = ref(false)
const searchDraft = ref("")
const activeDraft = ref("")
const activeSearch = ref("")
const activeFilter = ref("")
const page = ref(1)
const itemsPerPage = ref(20)
const sortField = ref("lastname")
const sortOrder = ref("asc")
const extraFields = ref([])
const hiddenFields = ref([])
const permissions = ref({
  canManage: false,
  canSubscribe: false,
  canUnsubscribe: false,
  canImport: false,
  canSetTutor: false,
  canInviteByEmail: false,
  showEmail: false,
  westernNameOrder: true,
  showSessionManagement: false,
  sessionManagementUrl: "",
  showClasses: false,
  showSubscriptionTabs: false,
  groupsUrl: "",
})

const userType = computed(() => (Number(route.query.type) === TEACHER ? TEACHER : STUDENT))
const activeOptions = computed(() => [
  { label: t("All"), value: "" },
  { label: t("Active"), value: "1" },
  { label: t("Inactive"), value: "0" },
])
const selectableUsers = computed(() => users.value.filter((user) => user.canUnsubscribe))
const allSelectableSelected = computed(
  () =>
    selectableUsers.value.length > 0 && selectableUsers.value.every((user) => selectedUserIds.value.includes(user.id)),
)
const selectionIndeterminate = computed(() => selectedUserIds.value.length > 0 && !allSelectableSelected.value)

function requestParams() {
  return {
    cid: cid.value,
    sid: sid.value,
    gid: gid.value,
    type: userType.value,
    search: activeSearch.value,
    active: activeFilter.value,
    page: page.value,
    itemsPerPage: itemsPerPage.value,
    sort: sortField.value,
    order: sortOrder.value,
  }
}

async function loadUsers() {
  loading.value = true
  errorMessage.value = ""

  try {
    const response = await courseUserService.getList(requestParams())
    users.value = response.items || []
    totalItems.value = Number(response.totalItems || 0)
    warningMessage.value = response.warning || ""
    showUpgradeCta.value = Boolean(response.showUpgradeCta)
    extraFields.value = response.extraFields || []
    hiddenFields.value = response.hiddenFields || []
    permissions.value = {
      canManage: Boolean(response.canManage),
      canSubscribe: Boolean(response.canSubscribe),
      canUnsubscribe: Boolean(response.canUnsubscribe),
      canImport: Boolean(response.canImport),
      canSetTutor: Boolean(response.canSetTutor),
      canInviteByEmail: Boolean(response.canInviteByEmail),
      showEmail: Boolean(response.showEmail),
      westernNameOrder: response.westernNameOrder !== false,
      showSessionManagement: Boolean(response.showSessionManagement),
      sessionManagementUrl: response.sessionManagementUrl || "",
      showClasses: Boolean(response.showClasses),
      showSubscriptionTabs: Boolean(response.showSubscriptionTabs),
      groupsUrl: response.groupsUrl || "",
    }
    selectedUserIds.value = selectedUserIds.value.filter((id) => users.value.some((user) => user.id === id))
  } catch (error) {
    console.error("[CourseUser] Failed to load course users", error)
    errorMessage.value = error?.response?.data?.detail || error?.message || t("An error occurred")
  } finally {
    loading.value = false
  }
}

function changeType(type) {
  selectedUserIds.value = []
  page.value = 1
  router.push({ name: "CourseUserList", params: route.params, query: { ...contextQuery.value, type } })
}

function buildSubscribeRoute() {
  return { name: "CourseUserSubscribe", params: route.params, query: { ...contextQuery.value, type: userType.value } }
}

function buildInvitationRoute() {
  return {
    name: "CourseInvitationList",
    query: {
      ...contextQuery.value,
      fromNode: route.params.node || undefined,
      type: userType.value,
    },
  }
}

function buildImportRoute() {
  return { name: "CourseUserImport", params: route.params, query: { ...contextQuery.value, type: userType.value } }
}

function buildGroupsRoute() {
  return { name: "CourseUserGroups", params: route.params, query: contextQuery.value }
}

function buildClassesRoute() {
  return { name: "CourseUserClasses", params: route.params, query: { ...contextQuery.value, type: undefined } }
}

function buildSessionsRoute() {
  return {
    name: "CourseSessionList",
    query: {
      cid: cid.value || undefined,
      sid: sid.value,
      gid: gid.value,
      courseUserNode: route.params.node || undefined,
      courseUserType: route.query.type || undefined,
    },
  }
}

function applySearch() {
  activeSearch.value = searchDraft.value.trim()
  activeFilter.value = activeDraft.value
  page.value = 1
  loadUsers()
}

function clearSearch() {
  searchDraft.value = ""
  activeDraft.value = ""
  activeSearch.value = ""
  activeFilter.value = ""
  page.value = 1
  loadUsers()
}

function onPage(event) {
  page.value = Number(event.page || 0) + 1
  itemsPerPage.value = Number(event.rows || itemsPerPage.value)
  loadUsers()
}

function onSort(event) {
  sortField.value = event.sortField || "lastname"
  sortOrder.value = Number(event.sortOrder) < 0 ? "desc" : "asc"
  loadUsers()
}

function toggleSelection(userId, checked) {
  if (checked) {
    selectedUserIds.value = [...new Set([...selectedUserIds.value, userId])]
    return
  }

  selectedUserIds.value = selectedUserIds.value.filter((id) => id !== userId)
}

function toggleAll(checked) {
  selectedUserIds.value = checked ? selectableUsers.value.map((user) => user.id) : []
}

function confirmUnsubscribe(user) {
  requireConfirmation({
    message: t("Are you sure you want to unsubscribe this user?"),
    accept: () => unsubscribeUsers([user.id]),
  })
}

function confirmBulkUnsubscribe() {
  requireConfirmation({
    message: t("Are you sure you want to unsubscribe the selected users?"),
    accept: () => unsubscribeUsers(selectedUserIds.value),
  })
}

async function unsubscribeUsers(userIds) {
  loading.value = true
  errorMessage.value = ""
  successMessage.value = ""

  try {
    const response = await courseUserService.unsubscribe(userIds, requestParams())
    successMessage.value = response.message || t("The selected users have been unsubscribed from the course")
    selectedUserIds.value = []
    await loadUsers()
  } catch (error) {
    console.error("[CourseUser] Failed to unsubscribe users", error)
    errorMessage.value = error?.response?.data?.detail || error?.message || t("An error occurred")
  } finally {
    loading.value = false
  }
}

function confirmTutorChange(user) {
  requireConfirmation({
    message: user.isTutor ? t("Remove assistant role") : t("Convert to assistant"),
    accept: () => setTutor(user),
  })
}

async function setTutor(user) {
  loading.value = true
  errorMessage.value = ""
  successMessage.value = ""

  try {
    const response = await courseUserService.setTutor(user.id, !user.isTutor, requestParams())
    successMessage.value = response.message || t("Update successful")
    await loadUsers()
  } catch (error) {
    console.error("[CourseUser] Failed to update tutor role", error)
    errorMessage.value = error?.response?.data?.detail || error?.message || t("An error occurred")
  } finally {
    loading.value = false
  }
}

async function exportFile(format) {
  errorMessage.value = ""

  try {
    const response = await courseUserService.exportFile(format, requestParams())
    const blob = response?.data instanceof Blob ? response.data : response
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement("a")
    const extension = format === "xlsx" ? "xlsx" : format
    link.href = url
    link.download = `course-users-${userType.value === TEACHER ? "teachers" : "students"}.${extension}`
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch (error) {
    console.error("[CourseUser] Failed to export course users", error)
    errorMessage.value = error?.response?.data?.detail || error?.message || t("An error occurred")
  }
}

function isHidden(field) {
  return hiddenFields.value.includes(field)
}

watch(userType, () => loadUsers())

onMounted(() => {
  searchDraft.value = String(route.query.search || "")
  activeSearch.value = searchDraft.value
  activeDraft.value = String(route.query.active ?? "")
  activeFilter.value = activeDraft.value
  loadUsers()
})

useStudentViewRefresh(loadUsers)
</script>
