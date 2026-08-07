<template>
  <section class="space-y-6">
    <BaseToolbar>
      <template #start>
        <div class="flex flex-wrap items-center gap-2">
          <BaseButton
            :label="t('Session overview')"
            :route="buildOverviewRoute()"
            icon="back"
            type="primary-text"
          />
          <BaseButton
            :label="t('Users subscribed to this session')"
            icon="account-multiple"
            :type="isRegisteredView ? 'primary' : 'primary-text'"
            @click="changeView(REGISTERED)"
          />
          <BaseButton
            :label="t('Subscribe users to this session')"
            icon="account-plus"
            :type="isAvailableView ? 'primary' : 'primary-text'"
            @click="changeView(AVAILABLE)"
          />
        </div>
      </template>
      <template #end>
        <BaseButton
          :label="t('Enrolment by classes')"
          :to-url="enrollmentByClassesUrl"
          icon="sessions"
          only-icon
          size="normal"
          :tooltip="t('Enrolment by classes')"
          type="success"
        />
      </template>
    </BaseToolbar>

    <form
      class="rounded-xl border border-gray-20 bg-white p-4 shadow-sm"
      @submit.prevent="applySearch"
    >
      <div class="flex flex-col gap-3 md:flex-row md:flex-wrap md:items-start">
        <BaseInputText
          id="course-session-user-search"
          v-model="searchDraft"
          class="min-w-64 flex-1"
          :label="t('Search users')"
          name="search"
        />
        <BaseSelect
          v-if="isAvailableView"
          id="course-session-user-scope"
          v-model="scopeDraft"
          :label="t('Users')"
          name="scope"
          :options="scopeOptions"
        />
        <BaseSelect
          v-if="isAvailableView"
          id="course-session-user-first-letter"
          v-model="firstLetterDraft"
          :label="t('First letter (last name)')"
          name="firstLetter"
          :options="firstLetterOptions"
        />
        <template v-if="isAvailableView">
          <BaseSelect
            v-for="field in profilingFields"
            :id="`course-session-field-${field.id}`"
            :key="field.id"
            v-model="extraFilterDrafts[field.variable]"
            :label="field.label"
            :name="`field_${field.variable}`"
            :options="withEmptyOption(field.options)"
          />
        </template>
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
      v-if="selectedUserIds.length > 0"
      class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-gray-20 bg-white px-4 py-3 shadow-sm"
    >
      <span class="font-semibold text-gray-800">
        {{ t("{0} users selected", [selectedUserIds.length]) }}
      </span>
      <div class="flex flex-wrap gap-2">
        <BaseButton
          :label="isAvailableView ? t('Subscribe') : t('Unsubscribe')"
          :icon="isAvailableView ? 'account-plus' : 'user-delete'"
          size="small"
          :type="isAvailableView ? 'success' : 'danger'"
          @click="confirmBulkAction"
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
      :text-for-empty="isAvailableView ? t('No available users found') : t('No users found')"
      :total-items="totalItems"
      :values="users"
      data-key="id"
      @page="onPage"
      @sort="onSort"
    >
      <Column class="w-12">
        <template #header>
          <input
            :aria-label="t('Select all')"
            class="h-4 w-4 cursor-pointer rounded border-gray-30"
            type="checkbox"
            :checked="allSelected"
            :disabled="users.length === 0"
            :indeterminate.prop="selectionIndeterminate"
            @change="toggleAll($event.target.checked)"
          />
        </template>
        <template #body="{ data }">
          <input
            :aria-label="t('Select user')"
            class="h-4 w-4 cursor-pointer rounded border-gray-30"
            type="checkbox"
            :checked="selectedUserIds.includes(data.id)"
            @change="toggleSelection(data.id, $event.target.checked)"
          />
        </template>
      </Column>
      <Column
        field="officialCode"
        :header="t('Code')"
        sortable
      />
      <Column
        field="firstname"
        :header="t('First name')"
        sortable
      />
      <Column
        field="lastname"
        :header="t('Last name')"
        sortable
      />
      <Column
        field="username"
        :header="t('Login')"
        sortable
      />
      <Column :header="t('Action')">
        <template #body="{ data }">
          <div class="flex items-center justify-center">
            <BaseButton
              :label="isAvailableView ? t('Subscribe') : t('Unsubscribe')"
              :icon="isAvailableView ? 'account-plus' : 'user-delete'"
              only-icon
              size="small"
              :type="isAvailableView ? 'success-text' : 'danger-text'"
              @click="confirmSingleAction(data)"
            />
          </div>
        </template>
      </Column>
    </BaseTable>
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue"
import { useConfirmation } from "../../composables/useConfirmation"
import courseSessionService from "../../services/courseSessionService"

const REGISTERED = "registered"
const AVAILABLE = "available"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const { requireConfirmation } = useConfirmation()

const sessionId = Number(route.params.sessionId)
const users = ref([])
const totalItems = ref(0)
const loading = ref(false)
const errorMessage = ref("")
const successMessage = ref("")
const selectedUserIds = ref([])
const searchDraft = ref("")
const activeSearch = ref("")
const scopeDraft = ref("all")
const activeScope = ref("all")
const firstLetterDraft = ref("")
const activeFirstLetter = ref("")
const profilingFields = ref([])
const extraFilterDrafts = reactive({})
const activeExtraFilters = reactive({})
const page = ref(1)
const itemsPerPage = ref(20)
const sortField = ref("lastname")
const sortOrder = ref("asc")

const currentView = computed(() => (route.query.view === AVAILABLE ? AVAILABLE : REGISTERED))
const isRegisteredView = computed(() => currentView.value === REGISTERED)
const isAvailableView = computed(() => currentView.value === AVAILABLE)
const allSelected = computed(
  () => users.value.length > 0 && users.value.every((user) => selectedUserIds.value.includes(user.id)),
)
const selectionIndeterminate = computed(() => selectedUserIds.value.length > 0 && !allSelected.value)
const scopeOptions = computed(() => [
  { label: t("All users"), value: "all" },
  { label: t("Users not registered in any session"), value: "no_session" },
])
const firstLetterOptions = computed(() => [
  { label: "--", value: "" },
  ...Array.from({ length: 26 }, (_, index) => {
    const letter = String.fromCharCode(65 + index)

    return { label: letter, value: letter }
  }),
])
const enrollmentByClassesUrl = computed(() => {
  const returnTo = `/course-sessions/${sessionId}/users`

  return `/main/admin/usergroups.php?from_session=${sessionId}&return_to=${encodeURIComponent(returnTo)}`
})

function withEmptyOption(options) {
  return [{ label: t("Select"), value: "" }, ...(options || [])]
}

function requestParams() {
  const params = {
    view: currentView.value,
    scope: activeScope.value,
    firstLetter: activeFirstLetter.value,
    search: activeSearch.value,
    page: page.value,
    itemsPerPage: itemsPerPage.value,
    sort: sortField.value,
    order: sortOrder.value,
  }

  for (const [variable, value] of Object.entries(activeExtraFilters)) {
    if (value) {
      params[`field_${variable}`] = value
    }
  }

  return params
}

async function loadUsers() {
  loading.value = true
  errorMessage.value = ""

  try {
    const response = await courseSessionService.getUsers(sessionId, requestParams())
    users.value = response.items || []
    totalItems.value = Number(response.totalItems || 0)
    profilingFields.value = response.profilingFields || []
    selectedUserIds.value = selectedUserIds.value.filter((id) => users.value.some((user) => user.id === id))

    for (const field of profilingFields.value) {
      if (!(field.variable in extraFilterDrafts)) {
        extraFilterDrafts[field.variable] = ""
      }
    }
  } catch (error) {
    console.error("[CourseSession] Failed to load session users", error)
    errorMessage.value = error?.response?.data?.detail || error?.message || t("An error occurred")
  } finally {
    loading.value = false
  }
}

function buildOverviewRoute() {
  return { name: "CourseSessionOverview", params: { sessionId }, query: { ...route.query, view: undefined } }
}

function changeView(view) {
  selectedUserIds.value = []
  page.value = 1
  router.push({
    name: "CourseSessionUsers",
    params: { sessionId },
    query: { ...route.query, view },
  })
}

function applySearch() {
  activeSearch.value = searchDraft.value.trim()
  activeScope.value = scopeDraft.value
  activeFirstLetter.value = firstLetterDraft.value
  for (const key of Object.keys(activeExtraFilters)) {
    delete activeExtraFilters[key]
  }
  for (const [variable, value] of Object.entries(extraFilterDrafts)) {
    if (value) {
      activeExtraFilters[variable] = value
    }
  }
  page.value = 1
  loadUsers()
}

function clearSearch() {
  searchDraft.value = ""
  activeSearch.value = ""
  scopeDraft.value = "all"
  activeScope.value = "all"
  firstLetterDraft.value = ""
  activeFirstLetter.value = ""
  for (const key of Object.keys(extraFilterDrafts)) {
    extraFilterDrafts[key] = ""
  }
  for (const key of Object.keys(activeExtraFilters)) {
    delete activeExtraFilters[key]
  }
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
  selectedUserIds.value = checked ? users.value.map((user) => user.id) : []
}

function confirmSingleAction(user) {
  requireConfirmation({
    message: isAvailableView.value
      ? t("Subscribe this user to the session?")
      : t("Are you sure you want to unsubscribe this user?"),
    accept: () => runAction([user.id]),
  })
}

function confirmBulkAction() {
  requireConfirmation({
    message: isAvailableView.value
      ? t("Subscribe the selected users to the session?")
      : t("Are you sure you want to unsubscribe the selected users?"),
    accept: () => runAction(selectedUserIds.value),
  })
}

async function runAction(userIds) {
  loading.value = true
  errorMessage.value = ""
  successMessage.value = ""

  try {
    const response = isAvailableView.value
      ? await courseSessionService.subscribeUsers(sessionId, userIds)
      : await courseSessionService.unsubscribeUsers(sessionId, userIds)

    successMessage.value = response.message ? t(response.message) : t("Update successful")
    selectedUserIds.value = []
    await loadUsers()
  } catch (error) {
    console.error("[CourseSession] Failed to update session users", error)
    errorMessage.value = error?.response?.data?.detail || error?.message || t("An error occurred")
  } finally {
    loading.value = false
  }
}

watch(currentView, () => {
  selectedUserIds.value = []
  page.value = 1
  loadUsers()
})

onMounted(() => {
  searchDraft.value = String(route.query.search || "")
  activeSearch.value = searchDraft.value
  scopeDraft.value = String(route.query.scope || "all")
  activeScope.value = scopeDraft.value
  firstLetterDraft.value = String(route.query.firstLetter || "")
  activeFirstLetter.value = firstLetterDraft.value
  loadUsers()
})
</script>
