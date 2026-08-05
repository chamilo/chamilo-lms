<script setup>
import { ref, computed, onMounted } from "vue"
import { useRoute, useRouter } from "vue-router"
import { useI18n } from "vue-i18n"
import usergroupAdminService from "../../services/usergroupAdminService"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()

const groupId = computed(() => Number(route.params.id))

const groupTitle = ref("")
const csrfToken = ref("")
const allSessions = ref([])
const selectedIds = ref(new Set())
const keyword = ref("")
const isLoading = ref(false)
const isSaving = ref(false)
const errorMessage = ref("")

const sessionsInGroup = computed(() => allSessions.value.filter((s) => selectedIds.value.has(s.id)))

const sessionsNotInGroup = computed(() => {
  const kw = keyword.value.trim().toLowerCase()
  return allSessions.value.filter((s) => {
    if (selectedIds.value.has(s.id)) return false
    if (kw && !s.label.toLowerCase().includes(kw)) return false
    return true
  })
})

async function loadData() {
  isLoading.value = true
  errorMessage.value = ""
  try {
    const data = await usergroupAdminService.getSessionsData(groupId.value)
    groupTitle.value = data.groupTitle
    csrfToken.value = data.csrfToken
    const merged = [...data.sessionsInGroup, ...data.sessionsNotInGroup].sort((a, b) => a.label.localeCompare(b.label))
    allSessions.value = merged
    selectedIds.value = new Set(data.sessionsInGroup.map((s) => s.id))
  } catch {
    errorMessage.value = t("An error occurred. Please try again.")
  } finally {
    isLoading.value = false
  }
}

function moveToGroup(session) {
  selectedIds.value = new Set([...selectedIds.value, session.id])
}

function moveFromGroup(session) {
  const next = new Set(selectedIds.value)
  next.delete(session.id)
  selectedIds.value = next
}

function moveAllToGroup() {
  selectedIds.value = new Set([...selectedIds.value, ...sessionsNotInGroup.value.map((s) => s.id)])
}

function moveAllFromGroup() {
  selectedIds.value = new Set()
}

async function save() {
  isSaving.value = true
  errorMessage.value = ""
  try {
    const formData = new FormData()
    formData.append("_token", csrfToken.value)
    selectedIds.value.forEach((id) => formData.append("sessionIds[]", String(id)))
    await usergroupAdminService.saveSessions(groupId.value, formData)
    await router.push({ name: "AdminUsergroupList" })
  } catch {
    errorMessage.value = t("An error occurred. Please try again.")
    isSaving.value = false
  }
}

onMounted(() => {
  loadData()
})
</script>

<template>
  <div class="flex flex-col gap-8">
    <SectionHeader :title="t('Subscribe class to sessions')">
      <BaseButton
        :label="t('Back')"
        icon="back"
        type="plain"
        :route="{ name: 'AdminUsergroupList' }"
      />
    </SectionHeader>

    <div
      v-if="groupTitle"
      class="text-xl font-semibold text-gray-700"
    >
      {{ t("Subscribe class to sessions") }}: {{ groupTitle }}
    </div>

    <div
      v-if="errorMessage"
      class="bg-red-100 text-red-700 rounded px-4 py-2"
    >
      {{ errorMessage }}
    </div>

    <div class="flex flex-col gap-1">
      <label class="text-sm text-gray-600">{{ t("Search") }}</label>
      <input
        v-model="keyword"
        type="text"
        :placeholder="t('Search by title')"
        class="border border-gray-300 rounded px-3 py-1.5 text-sm w-64"
      />
    </div>

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
      <div class="flex-1 flex flex-col gap-2">
        <div class="font-medium text-gray-700">{{ t("Sessions not subscribed") }}</div>
        <div class="border border-gray-200 rounded overflow-y-auto h-80 bg-white">
          <ul class="divide-y divide-gray-100">
            <li
              v-for="session in sessionsNotInGroup"
              :key="session.id"
              class="flex items-center justify-between px-3 py-1.5 text-sm hover:bg-gray-50 cursor-pointer"
              @dblclick="moveToGroup(session)"
            >
              <span>{{ session.label }}</span>
              <button
                type="button"
                class="text-green-600 hover:text-green-800"
                :title="t('Add')"
                @click="moveToGroup(session)"
              >
                <span class="mdi mdi-chevron-right" />
              </button>
            </li>
          </ul>
        </div>
        <div class="text-xs text-gray-500">
          {{ t("{0} sessions", [sessionsNotInGroup.length]) }}
        </div>
      </div>

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

      <div class="flex-1 flex flex-col gap-2">
        <div class="font-medium text-gray-700">{{ t("Sessions in group") }}</div>
        <div class="border border-gray-200 rounded overflow-y-auto h-80 bg-white">
          <ul class="divide-y divide-gray-100">
            <li
              v-for="session in sessionsInGroup"
              :key="session.id"
              class="flex items-center justify-between px-3 py-1.5 text-sm hover:bg-gray-50 cursor-pointer"
              @dblclick="moveFromGroup(session)"
            >
              <button
                type="button"
                class="text-red-500 hover:text-red-700"
                :title="t('Remove')"
                @click="moveFromGroup(session)"
              >
                <span class="mdi mdi-chevron-left" />
              </button>
              <span>{{ session.label }}</span>
            </li>
          </ul>
        </div>
        <div class="text-xs text-gray-500">
          {{ t("{0} sessions", [sessionsInGroup.length]) }}
        </div>
      </div>
    </div>

    <div class="flex gap-4 mt-4">
      <BaseButton
        :label="t('Subscribe class to sessions')"
        icon="sessions"
        type="success"
        :disabled="isSaving"
        @click="save"
      />
      <BaseButton
        :label="t('Cancel')"
        icon="back"
        type="plain"
        :route="{ name: 'AdminUsergroupList' }"
      />
    </div>
  </div>
</template>
