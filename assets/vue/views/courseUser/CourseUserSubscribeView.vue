<template>
  <section class="space-y-6">
    <BaseToolbar>
      <template #start>
        <div class="flex flex-wrap items-center gap-2">
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
            v-if="showSubscriptionTabs"
            :label="t('Groups')"
            :route="buildGroupsRoute()"
            icon="join-group"
            type="primary-text"
          />
          <BaseButton
            v-if="showSubscriptionTabs && showClasses"
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
            v-if="canInviteByEmail"
            :label="t('Invite by email')"
            :route="buildInvitationRoute()"
            icon="email-outline"
            only-icon
            size="normal"
            :tooltip="t('Invite by email')"
            type="success"
          />
          <BaseButton
            :label="t('Back')"
            :route="buildListRoute()"
            icon="back"
            only-icon
            size="normal"
            :tooltip="t('Back')"
            type="plain"
          />
        </div>
      </template>
    </BaseToolbar>

    <form
      class="rounded-xl border border-gray-20 bg-white p-4 shadow-sm"
      @submit.prevent="applySearch"
    >
      <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
        <BaseInputText
          id="available-user-search"
          v-model="searchDraft"
          :label="t('Search users')"
          name="search"
        />
        <BaseSelect
          v-if="profilingFields.length > 0"
          id="available-user-extra-field"
          v-model="extraFieldIdDraft"
          :label="t('Profile field')"
          name="extraFieldId"
          :options="profilingFieldOptions"
        />
        <BaseSelect
          v-if="selectedProfilingField"
          id="available-user-extra-value"
          v-model="extraFieldValueDraft"
          :label="t('Value')"
          name="extraFieldValue"
          :options="selectedProfilingField.options || []"
          allow-clear
        />
        <div class="flex flex-wrap items-start gap-2 md:pt-1">
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
      v-if="selectedUserIds.length > 0"
      class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-gray-20 bg-white px-4 py-3 shadow-sm"
    >
      <span class="font-semibold text-gray-800">
        {{ t("{0} users selected", [selectedUserIds.length]) }}
      </span>
      <div class="flex gap-2">
        <BaseButton
          :disabled="!canSubscribe"
          :is-loading="saving"
          :label="t('Register')"
          icon="user-add"
          type="success"
          @click="subscribeSelected"
        />
        <BaseButton
          :label="t('Clear selection')"
          icon="close"
          type="plain"
          @click="selectedUserIds = []"
        />
      </div>
    </div>

    <BaseTable
      v-model:rows="itemsPerPage"
      :is-loading="loading"
      lazy
      :text-for-empty="t('No available users')"
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
            :disabled="users.length === 0 || !canSubscribe"
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
            :disabled="!canSubscribe"
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
        v-if="westernNameOrder"
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
        v-if="!westernNameOrder"
        field="firstname"
        :header="t('First name')"
        sortable
      />
      <Column
        v-if="showEmail"
        field="email"
        :header="t('E-mail')"
        sortable
      />

      <Column :header="t('active')">
        <template #body="{ data }">
          <BaseIcon
            :icon="data.active ? 'check' : 'alert'"
            :class="data.active ? 'text-success' : 'text-danger'"
          />
        </template>
      </Column>

      <Column :header="t('Action')">
        <template #body="{ data }">
          <div class="flex justify-end">
            <BaseButton
              :disabled="!canSubscribe"
              :is-loading="savingUserId === data.id"
              :label="t('Register')"
              icon="user-add"
              only-icon
              size="small"
              type="success-text"
              @click="subscribeUsers([data.id])"
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
import courseUserService from "../../services/courseUserService"
import { useRouteCourseContext } from "../../composables/useRouteCourseContext"

const STUDENT = 5
const TEACHER = 1

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const { cid, sid, gid, contextQuery } = useRouteCourseContext()

const users = ref([])
const totalItems = ref(0)
const loading = ref(false)
const saving = ref(false)
const savingUserId = ref(null)
const errorMessage = ref("")
const successMessage = ref("")
const warningMessage = ref("")
const showUpgradeCta = ref(false)
const selectedUserIds = ref([])
const searchDraft = ref("")
const activeSearch = ref("")
const extraFieldIdDraft = ref("")
const extraFieldValueDraft = ref("")
const activeExtraFieldId = ref("")
const activeExtraFieldValue = ref("")
const profilingFields = ref([])
const page = ref(1)
const itemsPerPage = ref(20)
const sortField = ref("lastname")
const sortOrder = ref("asc")
const canSubscribe = ref(false)
const canInviteByEmail = ref(false)
const showSubscriptionTabs = ref(false)
const showClasses = ref(false)
const groupsUrl = ref("")
const showEmail = ref(false)
const westernNameOrder = ref(true)

const userType = computed(() => (Number(route.query.type) === TEACHER ? TEACHER : STUDENT))
const profilingFieldOptions = computed(() => [
  { label: t("All"), value: "" },
  ...profilingFields.value.map((field) => ({ label: field.label, value: String(field.id) })),
])
const selectedProfilingField = computed(() =>
  profilingFields.value.find((field) => String(field.id) === String(extraFieldIdDraft.value)),
)
const allSelected = computed(
  () => users.value.length > 0 && users.value.every((user) => selectedUserIds.value.includes(user.id)),
)
const selectionIndeterminate = computed(() => selectedUserIds.value.length > 0 && !allSelected.value)

function requestParams() {
  return {
    cid: cid.value,
    sid: sid.value,
    gid: gid.value,
    type: userType.value,
    search: activeSearch.value,
    extraFieldId: activeExtraFieldId.value,
    extraFieldValue: activeExtraFieldValue.value,
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
    const response = await courseUserService.getAvailable(requestParams())
    users.value = response.items || []
    totalItems.value = Number(response.totalItems || 0)
    profilingFields.value = response.extraFields || []
    canSubscribe.value = Boolean(response.canSubscribe)
    canInviteByEmail.value = Boolean(response.canInviteByEmail)
    showSubscriptionTabs.value = Boolean(response.showSubscriptionTabs)
    showClasses.value = Boolean(response.showClasses)
    groupsUrl.value = response.groupsUrl || ""
    showEmail.value = Boolean(response.showEmail)
    westernNameOrder.value = response.westernNameOrder !== false
    warningMessage.value = response.warning || ""
    showUpgradeCta.value = Boolean(response.showUpgradeCta)
    selectedUserIds.value = selectedUserIds.value.filter((id) => users.value.some((user) => user.id === id))
  } catch (error) {
    console.error("[CourseUser] Failed to load available users", error)
    errorMessage.value = error?.response?.data?.detail || error?.message || t("An error occurred")
  } finally {
    loading.value = false
  }
}

function changeType(type) {
  selectedUserIds.value = []
  page.value = 1
  router.push({ name: "CourseUserSubscribe", params: route.params, query: { ...contextQuery.value, type } })
}

function buildListRoute() {
  return { name: "CourseUserList", params: route.params, query: { ...contextQuery.value, type: userType.value } }
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

function buildGroupsRoute() {
  return { name: "CourseUserGroups", params: route.params, query: contextQuery.value }
}

function buildClassesRoute() {
  return { name: "CourseUserClasses", params: route.params, query: { ...contextQuery.value, type: undefined } }
}

function applySearch() {
  activeSearch.value = searchDraft.value.trim()
  activeExtraFieldId.value = extraFieldIdDraft.value
  activeExtraFieldValue.value = extraFieldValueDraft.value
  page.value = 1
  loadUsers()
}

function clearSearch() {
  searchDraft.value = ""
  activeSearch.value = ""
  extraFieldIdDraft.value = ""
  extraFieldValueDraft.value = ""
  activeExtraFieldId.value = ""
  activeExtraFieldValue.value = ""
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

function subscribeSelected() {
  subscribeUsers(selectedUserIds.value)
}

async function subscribeUsers(userIds) {
  if (!canSubscribe.value || userIds.length === 0) {
    return
  }

  saving.value = true
  savingUserId.value = userIds.length === 1 ? userIds[0] : null
  errorMessage.value = ""
  successMessage.value = ""

  try {
    const response = await courseUserService.subscribe(userIds, requestParams())
    successMessage.value = response.message || t("The selected users have been subscribed to the course")
    selectedUserIds.value = []
    await loadUsers()
  } catch (error) {
    console.error("[CourseUser] Failed to subscribe users", error)
    errorMessage.value = error?.response?.data?.detail || error?.message || t("An error occurred")
  } finally {
    saving.value = false
    savingUserId.value = null
  }
}

watch(extraFieldIdDraft, () => {
  extraFieldValueDraft.value = ""
})
watch(userType, () => loadUsers())

onMounted(loadUsers)
</script>
