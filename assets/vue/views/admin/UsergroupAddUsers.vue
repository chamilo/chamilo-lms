<script setup>
import { ref, computed, onMounted } from "vue"
import { useRoute } from "vue-router"
import { useI18n } from "vue-i18n"
import axios from "axios"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"

const { t } = useI18n()
const route = useRoute()

const groupId = computed(() => Number(route.params.id))

const groupTitle = ref("")
const isSocialGroup = ref(false)
const relationType = ref(2)
const csrfToken = ref("")

// Full platform user list — loaded once on mount and on relation change only
const allUsers = ref([])
// Set of IDs currently selected for the group — managed client-side
const selectedIds = ref(new Set())

const keyword = ref("")
const firstLetter = ref("")
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

const alphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ".split("")

// Right panel: all selected users (unfiltered)
const usersInGroup = computed(() => allUsers.value.filter((u) => selectedIds.value.has(u.id)))

// Left panel: non-selected users matching current filters
const usersNotInGroup = computed(() => {
  const kw = keyword.value.trim().toLowerCase()
  const fl = firstLetter.value
  return allUsers.value.filter((u) => {
    if (selectedIds.value.has(u.id)) return false
    if (fl && fl !== "%" && !u.label.toLowerCase().startsWith(fl.toLowerCase())) return false
    if (kw && !u.label.toLowerCase().includes(kw)) return false
    return true
  })
})

async function loadData() {
  isLoading.value = true
  errorMessage.value = ""
  try {
    const { data } = await axios.get(`/admin/usergroups/${groupId.value}/add-users-data`, {
      params: { relation: relationType.value },
    })
    groupTitle.value = data.groupTitle
    isSocialGroup.value = data.isSocialGroup
    csrfToken.value = data.csrfToken
    // Merge both sides into a single sorted list
    const merged = [...data.usersInGroup, ...data.usersNotInGroup].sort((a, b) =>
      a.label.localeCompare(b.label),
    )
    allUsers.value = merged
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
    formData.append("_token", csrfToken.value)
    formData.append("relationType", String(relationType.value))
    selectedIds.value.forEach((id) => formData.append("userIds[]", String(id)))
    await axios.post(`/admin/usergroups/${groupId.value}/add-users-data`, formData)
    window.location.href = "/main/admin/usergroups.php"
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
  <div class="flex flex-col gap-8">
    <SectionHeader :title="t('Subscribe users to class')">
      <BaseButton
        :label="t('Back')"
        icon="back"
        type="plain"
        :to-url="'/main/admin/usergroups.php'"
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
      class="text-xl font-semibold text-gray-700"
    >
      {{ t("Subscribe users to class") }}: {{ groupTitle }}
    </div>

    <div
      v-if="errorMessage"
      class="bg-red-100 text-red-700 rounded px-4 py-2"
    >
      {{ errorMessage }}
    </div>
    <div
      v-if="successMessage"
      class="bg-green-100 text-green-700 rounded px-4 py-2"
    >
      {{ successMessage }}
    </div>

    <!-- Relation type selector (social groups only) -->
    <div
      v-if="isSocialGroup"
      class="flex flex-col gap-2"
    >
      <label class="text-sm font-medium text-gray-700">{{ t("Relation type") }}</label>
      <select
        v-model="relationType"
        class="border border-gray-300 rounded px-3 py-1.5 text-sm w-64"
        @change="onRelationChange"
      >
        <option
          v-for="opt in relationOptions"
          :key="opt.value"
          :value="opt.value"
        >
          {{ t(opt.label) }}
        </option>
      </select>
    </div>

    <!-- Search filters -->
    <div class="flex flex-wrap gap-4 items-end">
      <div class="flex flex-col gap-1">
        <label class="text-sm text-gray-600">{{ t("Search") }}</label>
        <input
          v-model="keyword"
          type="text"
          :placeholder="t('Search by name, username, email')"
          class="border border-gray-300 rounded px-3 py-1.5 text-sm w-64"
        />
      </div>
      <div class="flex flex-col gap-1">
        <label class="text-sm text-gray-600">{{ t("First letter (last name)") }}</label>
        <select
          v-model="firstLetter"
          class="border border-gray-300 rounded px-3 py-1.5 text-sm"
        >
          <option value="%">--</option>
          <option
            v-for="letter in alphabet"
            :key="letter"
            :value="letter"
          >
            {{ letter }}
          </option>
        </select>
      </div>
    </div>

    <!-- Dual-list selector -->
    <div
      v-if="isLoading"
      class="text-gray-500 text-sm"
    >
      {{ t("Loading") }}...
    </div>
    <div
      v-else
      class="flex flex-col md:flex-row gap-6 items-start"
    >
      <!-- Users not in group -->
      <div class="flex-1 flex flex-col gap-2">
        <div class="font-medium text-gray-700">{{ t("Users on platform") }}</div>
        <div class="border border-gray-200 rounded overflow-y-auto h-80 bg-white">
          <ul class="divide-y divide-gray-100">
            <li
              v-for="user in usersNotInGroup"
              :key="user.id"
              class="flex items-center justify-between px-3 py-1.5 text-sm hover:bg-gray-50 cursor-pointer"
              @dblclick="moveToGroup(user)"
            >
              <span>{{ user.label }}</span>
              <button
                type="button"
                class="text-green-600 hover:text-green-800"
                :title="t('Add')"
                @click="moveToGroup(user)"
              >
                <span class="mdi mdi-chevron-right ch-tool-icon" />
              </button>
            </li>
          </ul>
        </div>
        <div class="text-xs text-gray-500">{{ usersNotInGroup.length }} {{ t("Users") }}</div>
      </div>

      <!-- Transfer buttons -->
      <div class="flex flex-col items-center justify-center gap-4 mt-8">
        <button
          type="button"
          class="w-10 h-10 rounded-full bg-green-100 hover:bg-green-200 shadow flex items-center justify-center"
          :title="t('Add all')"
          @click="moveAllToGroup"
        >
          <span class="mdi mdi-chevron-double-right text-green-700" />
        </button>
        <button
          type="button"
          class="w-10 h-10 rounded-full bg-red-100 hover:bg-red-200 shadow flex items-center justify-center"
          :title="t('Remove all')"
          @click="moveAllFromGroup"
        >
          <span class="mdi mdi-chevron-double-left text-red-700" />
        </button>
      </div>

      <!-- Users in group -->
      <div class="flex-1 flex flex-col gap-2">
        <div class="font-medium text-gray-700">{{ t("Users in group") }}</div>
        <div class="border border-gray-200 rounded overflow-y-auto h-80 bg-white">
          <ul class="divide-y divide-gray-100">
            <li
              v-for="user in usersInGroup"
              :key="user.id"
              class="flex items-center justify-between px-3 py-1.5 text-sm hover:bg-gray-50 cursor-pointer"
              @dblclick="moveFromGroup(user)"
            >
              <button
                type="button"
                class="text-red-500 hover:text-red-700"
                :title="t('Remove')"
                @click="moveFromGroup(user)"
              >
                <span class="mdi mdi-chevron-left ch-tool-icon" />
              </button>
              <span>{{ user.label }}</span>
            </li>
          </ul>
        </div>
        <div class="text-xs text-gray-500">{{ usersInGroup.length }} {{ t("Users") }}</div>
      </div>
    </div>

    <!-- Save button -->
    <div class="flex gap-4 mt-4">
      <BaseButton
        :label="t('Subscribe users to class')"
        icon="subscribe-users"
        type="success"
        :disabled="isSaving"
        @click="save"
      />
      <BaseButton
        :label="t('Cancel')"
        icon="back"
        type="plain"
        :to-url="'/main/admin/usergroups.php'"
      />
    </div>
  </div>
</template>
