<script setup>
import { ref, computed, onMounted, watch } from "vue"
import { useRoute, useRouter } from "vue-router"
import { useI18n } from "vue-i18n"
import usergroupAdminService from "../../services/usergroupAdminService"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()

const groupId = computed(() => Number(route.params.id))

const groupTitle = ref("")
const isSocialGroup = ref(false)
const relationType = ref(2)
const orderByOfficialCode = ref(false)

// Full platform user list — loaded once on mount and on relation change only
const allUsers = ref([])
// Set of IDs currently selected for the group — managed client-side
const selectedIds = ref(new Set())

const keyword = ref("")
const firstLetter = ref("%")
const searchableExtraFields = ref([])
const selectedExtraFieldId = ref(null)
const selectedExtraFieldValue = ref(null)
const extraFieldMatchedIds = ref(null)
const isExtraFieldLoading = ref(false)
const extraFieldError = ref("")
let extraFieldRequestId = 0

const selectedExtraField = computed(() =>
  searchableExtraFields.value.find((field) => field.id === selectedExtraFieldId.value),
)
const extraFieldIsChoice = computed(() => [3, 4, 5, 10, 13].includes(selectedExtraField.value?.type))
const extraFieldFilterActive = computed(
  () => selectedExtraFieldId.value && String(selectedExtraFieldValue.value ?? "").trim() !== "",
)
const extraFieldOptions = computed(() => selectedExtraField.value?.options || [])

watch(selectedExtraFieldId, () => {
  selectedExtraFieldValue.value = null
})

watch([selectedExtraFieldId, selectedExtraFieldValue], ([fieldId, value], _, onCleanup) => {
  const requestId = ++extraFieldRequestId
  extraFieldMatchedIds.value = null
  extraFieldError.value = ""
  isExtraFieldLoading.value = false

  if (!fieldId || value === null || value === undefined || String(value).trim() === "") {
    return
  }

  isExtraFieldLoading.value = true
  // Text searches are debounced; choice fields apply immediately.
  const delay = extraFieldIsChoice.value ? 0 : 300
  const timer = setTimeout(async () => {
    try {
      const response = await usergroupAdminService.matchUsersByExtraField(groupId.value, fieldId, String(value))
      if (requestId === extraFieldRequestId) {
        extraFieldMatchedIds.value = new Set(response.userIds.map(Number))
      }
    } catch {
      if (requestId === extraFieldRequestId) {
        extraFieldMatchedIds.value = new Set()
        extraFieldError.value = t("An error occurred. Please try again.")
      }
    } finally {
      if (requestId === extraFieldRequestId) {
        isExtraFieldLoading.value = false
      }
    }
  }, delay)
  onCleanup(() => clearTimeout(timer))
})
const isLoading = ref(false)
const isSaving = ref(false)
const errorMessage = ref("")
const successMessage = ref("")

const relationOptions = [
  { value: 1, label: "Admin" },
  { value: 2, label: "Reader" },
  { value: 3, label: "Pending invitation" },
  { value: 5, label: "Moderator" },
  { value: 7, label: "Human Resources Manager" },
]

const letterOptions = [
  { label: "--", value: "%" },
  ..."ABCDEFGHIJKLMNOPQRSTUVWXYZ".split("").map((letter) => ({ label: letter, value: letter })),
]
const userCollator = new Intl.Collator(undefined, {
  numeric: true,
  sensitivity: "base",
})

// Right panel: all selected users (unfiltered)
const usersInGroup = computed(() => allUsers.value.filter((u) => selectedIds.value.has(u.id)))

// Left panel: non-selected users matching current filters
const usersNotInGroup = computed(() => {
  const kw = keyword.value.trim().toLowerCase()
  const fl = firstLetter.value

  return allUsers.value.filter((user) => {
    if (selectedIds.value.has(user.id)) {
      return false
    }

    if (extraFieldFilterActive.value) {
      if (isExtraFieldLoading.value || !extraFieldMatchedIds.value?.has(user.id)) {
        return false
      }
    }

    const lastName = String(user.lastName || "").toLowerCase()
    if (fl && fl !== "%" && !lastName.startsWith(fl.toLowerCase())) {
      return false
    }

    if (kw && !user.label.toLowerCase().includes(kw)) {
      return false
    }

    return true
  })
})

function clearFilters() {
  keyword.value = ""
  firstLetter.value = "%"
  selectedExtraFieldId.value = null
  selectedExtraFieldValue.value = null
}

function compareUsers(a, b) {
  if (orderByOfficialCode.value) {
    const firstOfficialCode = String(a.officialCode || "").trim()
    const secondOfficialCode = String(b.officialCode || "").trim()

    if (!firstOfficialCode && secondOfficialCode) {
      return 1
    }

    if (firstOfficialCode && !secondOfficialCode) {
      return -1
    }

    const officialCodeComparison = userCollator.compare(firstOfficialCode, secondOfficialCode)
    if (officialCodeComparison !== 0) {
      return officialCodeComparison
    }
  }

  const lastNameComparison = userCollator.compare(String(a.lastName || ""), String(b.lastName || ""))
  if (lastNameComparison !== 0) {
    return lastNameComparison
  }

  const firstNameComparison = userCollator.compare(String(a.firstName || ""), String(b.firstName || ""))
  if (firstNameComparison !== 0) {
    return firstNameComparison
  }

  return userCollator.compare(String(a.username || ""), String(b.username || ""))
}

async function loadData() {
  isLoading.value = true
  errorMessage.value = ""
  try {
    const data = await usergroupAdminService.getAddUsersData(groupId.value, relationType.value)
    groupTitle.value = data.groupTitle
    isSocialGroup.value = data.isSocialGroup
    orderByOfficialCode.value = true === data.orderByOfficialCode
    searchableExtraFields.value = data.searchableExtraFields || []
    if (!searchableExtraFields.value.some((field) => field.id === selectedExtraFieldId.value)) {
      selectedExtraFieldId.value = null
    }
    // Merge both sides into a single list so transfers preserve the configured ordering.
    allUsers.value = [...data.usersInGroup, ...data.usersNotInGroup].sort(compareUsers)
    selectedIds.value = new Set(data.usersInGroup.map((u) => u.id))
  } catch {
    errorMessage.value = t("An error occurred. Please try again.")
  } finally {
    isLoading.value = false
  }
}

function onRelationChange() {
  // Reload from server — new relation means different group membership
  loadData()
}

function moveToGroup(user) {
  selectedIds.value = new Set([...selectedIds.value, user.id])
}

function moveFromGroup(user) {
  const next = new Set(selectedIds.value)
  next.delete(user.id)
  selectedIds.value = next
}

function moveAllToGroup() {
  if (isExtraFieldLoading.value || extraFieldError.value) return
  // Only move currently visible (filtered) left-panel users
  selectedIds.value = new Set([...selectedIds.value, ...usersNotInGroup.value.map((u) => u.id)])
}

function moveAllFromGroup() {
  selectedIds.value = new Set()
}

async function save() {
  if (isSocialGroup.value && !relationType.value) {
    errorMessage.value = t("Select role")
    return
  }
  isSaving.value = true
  errorMessage.value = ""
  successMessage.value = ""
  try {
    const formData = new FormData()
    formData.append("relationType", String(relationType.value))
    selectedIds.value.forEach((id) => formData.append("userIds[]", String(id)))
    await usergroupAdminService.saveUsers(groupId.value, formData)
    await router.push({ name: "AdminUsergroupList" })
  } catch {
    errorMessage.value = t("An error occurred. Please try again.")
    isSaving.value = false
  }
}

function exportCsv() {
  window.location.href = `/admin/usergroups/${groupId.value}/add-users-data/export`
}

onMounted(() => {
  loadData()
})
</script>

<template>
  <div class="flex flex-col gap-6">
    <SectionHeader :title="t('Subscribe users to class')">
      <BaseButton
        :label="t('Back')"
        icon="back"
        type="plain"
        :route="{ name: 'AdminUsergroupList' }"
      />
      <BaseButton
        :label="t('Export')"
        icon="export-csv"
        type="primary"
        @click="exportCsv"
      />
    </SectionHeader>

    <div
      v-if="groupTitle"
      class="flex flex-wrap items-center gap-2"
    >
      <span class="text-sm font-medium text-gray-500">{{ t("Group") }}:</span>
      <span class="text-lg font-semibold text-gray-800">{{ groupTitle }}</span>
    </div>

    <div
      v-if="errorMessage"
      class="rounded-lg bg-red-100 px-4 py-3 text-sm text-red-700"
      role="alert"
    >
      {{ errorMessage }}
    </div>
    <div
      v-if="successMessage"
      class="rounded-lg bg-green-100 px-4 py-3 text-sm text-green-700"
    >
      {{ successMessage }}
    </div>

    <section class="usergroup-filter-panel rounded-xl border bg-white p-5 shadow-sm">
      <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2">
          <BaseIcon
            icon="filter"
            size="small"
            class="text-gray-600"
          />
          <h2 class="text-base font-semibold text-gray-800">{{ t("Filters") }}</h2>
        </div>
        <BaseButton
          :label="t('Clear')"
          icon="close"
          type="plain"
          size="small"
          :disabled="!keyword && (!firstLetter || firstLetter === '%') && !selectedExtraFieldId"
          @click="clearFilters"
        />
      </div>

      <div
        v-if="isSocialGroup"
        class="assignment-filter-control mb-5 max-w-sm"
      >
        <BaseSelect
          id="usergroup-relation-type"
          v-model="relationType"
          name="relationType"
          :label="t('Relation type')"
          :options="relationOptions.map((option) => ({ ...option, label: t(option.label) }))"
          @change="onRelationChange"
        />
      </div>

      <div class="assignment-filter-grid">
        <div class="assignment-filter-control min-w-0">
          <BaseInputText
            id="usergroup-keyword"
            v-model="keyword"
            name="keyword"
            :label="t('Search')"
            :placeholder="t('Search by name, username, email')"
          />
        </div>

        <div class="assignment-filter-control min-w-0">
          <BaseSelect
            id="usergroup-first-letter"
            v-model="firstLetter"
            name="firstLetter"
            :label="t('First letter (last name)')"
            :options="letterOptions"
          />
        </div>

        <div
          v-if="searchableExtraFields.length"
          class="assignment-filter-control min-w-0"
        >
          <BaseSelect
            id="user-extra-field-filter"
            v-model="selectedExtraFieldId"
            name="extraFieldId"
            :label="t('Extra fields')"
            :options="searchableExtraFields"
            option-value="id"
            :allow-clear="true"
          />
        </div>

        <div
          v-if="selectedExtraField"
          class="assignment-filter-control min-w-0"
        >
          <BaseSelect
            v-if="extraFieldIsChoice"
            id="user-extra-field-value"
            v-model="selectedExtraFieldValue"
            name="extraFieldValue"
            :label="t('Value')"
            :options="extraFieldOptions.map((option) => ({ ...option, label: t(option.label) }))"
            :allow-clear="true"
          />
          <BaseInputText
            v-else
            id="user-extra-field-text"
            v-model="selectedExtraFieldValue"
            name="extraFieldValue"
            :label="t('Value')"
            maxlength="255"
          />
        </div>
      </div>

      <div
        v-if="isExtraFieldLoading"
        class="mt-4 text-sm text-gray-500"
        role="status"
      >
        {{ t("Loading") }}...
      </div>
      <div
        v-if="extraFieldError"
        class="mt-4 text-sm text-red-700"
        role="alert"
      >
        {{ extraFieldError }}
      </div>
    </section>

    <div
      v-if="isLoading"
      class="py-10 text-center text-sm text-gray-500"
      role="status"
    >
      {{ t("Loading") }}...
    </div>

    <div
      v-else
      class="usergroup-transfer-layout"
    >
      <!-- Available users: filters only affect this panel, not saved membership. -->
      <section class="usergroup-list-panel flex min-w-0 flex-col overflow-hidden rounded-xl border bg-white shadow-sm">
        <div class="usergroup-list-header flex items-center justify-between gap-3 border-b px-4 py-3">
          <h2 class="text-sm font-semibold text-gray-800">{{ t("Users on platform") }}</h2>
          <span class="rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-700">
            {{ usersNotInGroup.length }}
          </span>
        </div>
        <div class="h-80 overflow-y-auto">
          <ul class="divide-y divide-gray-100">
            <li
              v-for="user in usersNotInGroup"
              :key="user.id"
              class="usergroup-list-row flex min-h-11 items-center justify-between gap-3 px-4 py-2"
              @dblclick="moveToGroup(user)"
            >
              <span
                class="min-w-0 flex-1 truncate text-sm text-gray-700"
                :title="user.label"
              >
                {{ user.label }}
              </span>
              <BaseButton
                :label="t('Add')"
                icon="plus"
                type="success-text"
                only-icon
                size="small"
                @click="moveToGroup(user)"
              />
            </li>
            <li
              v-if="!usersNotInGroup.length"
              class="px-4 py-10 text-center text-sm text-gray-500"
            >
              {{ t("No results found") }}
            </li>
          </ul>
        </div>
      </section>

      <!-- Transfer buttons work with the visible available users only. -->
      <div class="usergroup-transfer-actions">
        <BaseButton
          :label="t('Add all')"
          icon="next"
          type="success"
          size="small"
          only-icon
          :disabled="!usersNotInGroup.length || isExtraFieldLoading || !!extraFieldError"
          @click="moveAllToGroup"
        />
        <BaseButton
          :label="t('Remove all')"
          icon="back"
          type="danger-text"
          size="small"
          only-icon
          :disabled="!usersInGroup.length"
          @click="moveAllFromGroup"
        />
      </div>

      <!-- Assigned users are always displayed, independent of filters. -->
      <section class="usergroup-list-panel flex min-w-0 flex-col overflow-hidden rounded-xl border bg-white shadow-sm">
        <div class="usergroup-list-header flex items-center justify-between gap-3 border-b px-4 py-3">
          <h2 class="text-sm font-semibold text-gray-800">{{ t("Users in group") }}</h2>
          <span class="rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-700">
            {{ usersInGroup.length }}
          </span>
        </div>
        <div class="h-80 overflow-y-auto">
          <ul class="divide-y divide-gray-100">
            <li
              v-for="user in usersInGroup"
              :key="user.id"
              class="usergroup-list-row flex min-h-11 items-center justify-between gap-3 px-4 py-2"
              @dblclick="moveFromGroup(user)"
            >
              <span
                class="min-w-0 flex-1 truncate text-sm text-gray-700"
                :title="user.label"
              >
                {{ user.label }}
              </span>
              <BaseButton
                :label="t('Remove')"
                icon="close"
                type="danger-text"
                only-icon
                size="small"
                @click="moveFromGroup(user)"
              />
            </li>
            <li
              v-if="!usersInGroup.length"
              class="px-4 py-10 text-center text-sm text-gray-500"
            >
              {{ t("No results found") }}
            </li>
          </ul>
        </div>
      </section>
    </div>

    <div class="flex flex-wrap items-center justify-end gap-3 border-t border-gray-200 pt-5">
      <BaseButton
        :label="t('Cancel')"
        icon="back"
        type="plain"
        :route="{ name: 'AdminUsergroupList' }"
      />
      <BaseButton
        :label="t('Subscribe users to class')"
        icon="subscribe-users"
        type="success"
        :disabled="isSaving"
        @click="save"
      />
    </div>
  </div>
</template>

<style scoped>
/* Consistent light surfaces irrespective of the active theme's gray utilities. */
.usergroup-filter-panel,
.usergroup-list-panel {
  border-color: #e2e8f0;
}

.usergroup-list-header {
  background-color: #f8fafc;
  border-bottom-color: #e2e8f0;
}

.usergroup-list-row {
  background-color: #fff;
  transition: background-color 150ms ease;
}

.usergroup-list-row:hover {
  background-color: #f1f5f9;
}

.usergroup-list-panel ul > li + li {
  border-top: 1px solid #e9eef5;
}

.usergroup-list-row:focus-within {
  background-color: #eff6ff;
}

.assignment-filter-grid {
  display: grid;
  grid-template-columns: minmax(0, 1fr);
  gap: 1rem;
  padding-top: 0.5rem;
}

.usergroup-transfer-layout {
  display: grid;
  grid-template-columns: minmax(0, 1fr);
  align-items: stretch;
  gap: 1rem;
}

.usergroup-transfer-actions {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
}

@media (min-width: 640px) {
  .assignment-filter-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (min-width: 768px) {
  .usergroup-transfer-layout {
    grid-template-columns: minmax(0, 1fr) 4.5rem minmax(0, 1fr);
  }

  .usergroup-transfer-actions {
    flex-direction: column;
    align-self: center;
  }
}

@media (min-width: 1280px) {
  .assignment-filter-grid {
    grid-template-columns: minmax(0, 2fr) minmax(0, 1fr) minmax(0, 1.5fr) minmax(0, 1.5fr);
  }
}

.assignment-filter-control :deep(.field),
.assignment-filter-control :deep(.p-floatlabel),
.assignment-filter-control :deep(.p-select),
.assignment-filter-control :deep(.p-inputtext) {
  width: 100%;
}
</style>
