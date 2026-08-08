<template>
  <section class="space-y-6">
    <BaseToolbar>
      <template #start>
        <BaseButton
          :label="t('Back to group list')"
          :route="listRoute"
          icon="back"
          only-icon
          type="plain"
        />
      </template>
    </BaseToolbar>

    <div
      v-if="errorMessage"
      class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"
    >
      {{ errorMessage }}
    </div>
    <div
      v-if="resultMessage"
      class="rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-700"
    >
      {{ resultMessage }}
    </div>

    <div
      v-if="resultSections.length > 0"
      class="space-y-4"
    >
      <article
        v-for="section in resultSections"
        :key="section.status"
        class="rounded-xl border border-gray-20 bg-white p-4 shadow-sm"
      >
        <h2 class="font-semibold text-gray-90">{{ t(section.status) }}</h2>
        <div
          v-if="section.categories.length > 0"
          class="mt-3"
        >
          <h3 class="text-sm font-medium text-gray-90">{{ t("Categories") }}</h3>
          <ul class="mt-1 list-inside list-disc text-sm text-gray-70">
            <li
              v-for="item in section.categories"
              :key="item"
            >
              {{ item }}
            </li>
          </ul>
        </div>
        <div
          v-if="section.groups.length > 0"
          class="mt-3"
        >
          <h3 class="text-sm font-medium text-gray-90">{{ t("Groups") }}</h3>
          <ul class="mt-1 list-inside list-disc text-sm text-gray-70">
            <li
              v-for="item in section.groups"
              :key="item"
            >
              {{ item }}
            </li>
          </ul>
        </div>
        <ul
          v-if="section.messages.length > 0"
          class="mt-3 list-inside list-disc text-sm text-red-700"
        >
          <li
            v-for="item in section.messages"
            :key="item"
          >
            {{ item }}
          </li>
        </ul>
      </article>
    </div>

    <article class="rounded-xl border border-gray-20 bg-white p-6 shadow-sm">
      <h1 class="text-xl font-semibold text-gray-90">{{ t("Import groups") }}</h1>
      <p class="mt-1 text-sm text-gray-50">
        {{ t("Import groups from a CSV file and optionally remove items that are not present in the file.") }}
      </p>

      <div class="mt-6 space-y-5">
        <BaseFileUpload
          accept=".csv,text/csv"
          :label="t('Select CSV file')"
          name="file"
          @file-selected="file = $event"
        />
        <div class="rounded-lg border border-orange-200 bg-orange-50 p-4 text-sm text-orange-800">
          <BaseCheckbox
            id="delete-missing-groups"
            v-model="deleteMissing"
            :label="t('Delete items not in file')"
            name="deleteMissing"
          />
          <p class="mt-2">
            {{
              t(
                "Use this option carefully because items missing from the CSV can be removed during the import process.",
              )
            }}
          </p>
        </div>
        <div class="flex flex-wrap items-center justify-between gap-3">
          <BaseButton
            :label="t('Example CSV file')"
            icon="download"
            type="primary"
            @click="downloadExampleCsv"
          />
          <BaseButton
            :disabled="!file"
            :is-loading="saving"
            :label="t('Import')"
            icon="import"
            type="success"
            @click="submit"
          />
        </div>
      </div>
    </article>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseCheckbox from "../../components/basecomponents/BaseCheckbox.vue"
import BaseFileUpload from "../../components/basecomponents/BaseFileUpload.vue"
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue"
import courseGroupService from "../../services/courseGroupService"
import { useRouteCourseContext } from "../../composables/useRouteCourseContext"

const { t } = useI18n()
const route = useRoute()
const { cid, sid, contextQuery } = useRouteCourseContext()
const file = ref(null)
const deleteMissing = ref(false)
const saving = ref(false)
const errorMessage = ref("")
const resultMessage = ref("")
const importResult = ref({})
const requestParams = computed(() => ({ cid: cid.value, sid: sid.value }))
const listRoute = computed(() => ({
  name: "CourseUserGroups",
  params: route.params,
  query: { ...contextQuery.value, gid: 0 },
}))
const resultSections = computed(() =>
  Object.entries(importResult.value || {})
    .map(([status, value]) => {
      const data = value && typeof value === "object" ? value : {}
      return {
        status: status.charAt(0).toUpperCase() + status.slice(1),
        categories: normalizeResultItems(data.category),
        groups: normalizeResultItems(data.group),
        messages: status === "error" ? normalizeResultItems(value) : [],
      }
    })
    .filter((section) => section.categories.length || section.groups.length || section.messages.length),
)

function downloadExampleCsv() {
  const rows = [
    '"category","group","description","announcements_state","calendar_state","chat_state","doc_state","forum_state","work_state","wiki_state","max_student","self_reg_allowed","self_unreg_allowed","groups_per_user","students","tutors"',
    '"Category 1","","This is a category","2","2","2","2","2","2","2","0","0","0","0","",""',
    '"","Group 1","This is a group with no category","2","2","2","2","2","2","2","0","0","0","","username1,username2","username3,username4"',
    '"Category 1","Group 2","This is a group in Category 1","2","2","2","2","2","2","2","0","0","0","","username1,username2","username3,username4"',
  ]
  const blob = new Blob([`\uFEFF${rows.join("\n")}\n`], { type: "text/csv;charset=utf-8" })
  const url = URL.createObjectURL(blob)
  const link = document.createElement("a")
  link.href = url
  link.download = "course-groups-example.csv"
  document.body.appendChild(link)
  link.click()
  link.remove()
  URL.revokeObjectURL(url)
}

function normalizeResultItems(value) {
  const items = Array.isArray(value) ? value : value && typeof value === "object" ? Object.values(value) : []
  return items
    .map((item) => {
      if (item && typeof item === "object") {
        return String(item.category || item.group || item.title || item.name || item.message || item.id || "")
      }
      return String(item || "")
    })
    .filter(Boolean)
}

async function load() {
  try {
    await courseGroupService.getImport(requestParams.value)
  } catch (error) {
    console.error("[CourseGroup] Failed to load import form", error)
    errorMessage.value = error?.response?.data?.detail || error?.message || t("An error occurred")
  }
}

async function submit() {
  if (!file.value || saving.value) return
  saving.value = true
  errorMessage.value = ""
  resultMessage.value = ""
  importResult.value = {}
  try {
    const response = await courseGroupService.importGroups(file.value, deleteMissing.value, requestParams.value)
    resultMessage.value = response.message ? t(response.message) : t("Import completed")
    importResult.value = response.result || {}
  } catch (error) {
    console.error("[CourseGroup] Import failed", error)
    errorMessage.value = error?.response?.data?.detail || error?.message || t("An error occurred")
  } finally {
    saving.value = false
  }
}

onMounted(load)
</script>
