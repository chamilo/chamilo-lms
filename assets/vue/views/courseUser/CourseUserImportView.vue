<template>
  <section class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <h2 class="text-xl font-semibold text-gray-90">
        {{ userType === TEACHER ? t("Import teachers") : t("Import learners") }}
      </h2>
      <BaseButton
        :label="t('Back')"
        :route="buildListRoute()"
        icon="back"
        type="plain"
      />
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

    <div class="rounded-xl border border-gray-20 bg-white p-6 shadow-sm">
      <div class="space-y-5">
        <BaseFileUpload
          accept=".csv,.txt,text/csv"
          :label="t('Select CSV file')"
          name="import_file"
          @file-selected="selectedFile = $event"
        />

        <BaseCheckbox
          id="course-user-import-replace"
          v-model="replaceUsers"
          :label="t('Unsubscribe users already added')"
          name="replace"
        />

        <div class="flex flex-wrap gap-2">
          <BaseButton
            :disabled="!selectedFile || !canImport"
            :is-loading="saving"
            :label="t('Import')"
            icon="import"
            type="success"
            @click="runImport"
          />
          <BaseButton
            :label="t('Cancel')"
            :route="buildListRoute()"
            icon="close"
            type="plain"
          />
        </div>
      </div>
    </div>

    <div class="rounded-xl border border-gray-20 bg-gray-10 p-5 text-sm text-gray-700">
      <p class="font-semibold text-gray-90">{{ t("The CSV file must look like this") }}</p>
      <div class="mt-3 grid gap-4 md:grid-cols-2">
        <pre class="overflow-auto rounded-lg bg-white p-4">{{ sampleByUsername }}</pre>
        <pre class="overflow-auto rounded-lg bg-white p-4">{{ sampleById }}</pre>
      </div>
    </div>

    <div
      v-if="invalidRows.length > 0"
      class="rounded-xl border border-red-200 bg-red-50 p-4"
    >
      <h3 class="font-semibold text-red-800">{{ t("Errors when importing file") }}</h3>
      <pre class="mt-3 max-h-64 overflow-auto text-xs text-red-700">{{ JSON.stringify(invalidRows, null, 2) }}</pre>
    </div>

    <div
      v-if="failedRows.length > 0"
      class="rounded-xl border border-orange-200 bg-orange-50 p-4"
    >
      <h3 class="font-semibold text-orange-800">{{ t("Some users could not be subscribed") }}</h3>
      <ul class="mt-2 list-disc pl-5 text-sm text-orange-800">
        <li
          v-for="row in failedRows"
          :key="row.id"
        >
          #{{ row.id }} — {{ row.message }}
        </li>
      </ul>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseCheckbox from "../../components/basecomponents/BaseCheckbox.vue"
import BaseFileUpload from "../../components/basecomponents/BaseFileUpload.vue"
import courseUserService from "../../services/courseUserService"
import { useRouteCourseContext } from "../../composables/useRouteCourseContext"

const TEACHER = 1

const { t } = useI18n()
const route = useRoute()
const { cid, sid, gid, contextQuery } = useRouteCourseContext()

const selectedFile = ref(null)
const replaceUsers = ref(false)
const saving = ref(false)
const canImport = ref(false)
const sampleByUsername = ref("username\njdoe")
const sampleById = ref("id\n23")
const errorMessage = ref("")
const successMessage = ref("")
const invalidRows = ref([])
const failedRows = ref([])

const userType = computed(() => (Number(route.query.type) === TEACHER ? TEACHER : 5))

function requestParams() {
  return {
    cid: cid.value,
    sid: sid.value,
    gid: gid.value,
    type: userType.value,
  }
}

function buildListRoute() {
  return { name: "CourseUserList", params: route.params, query: { ...contextQuery.value, type: userType.value } }
}

async function loadImportConfiguration() {
  try {
    const response = await courseUserService.getImport(requestParams())
    canImport.value = Boolean(response.canImport)
    sampleByUsername.value = response.sampleByUsername || sampleByUsername.value
    sampleById.value = response.sampleById || sampleById.value
  } catch (error) {
    console.error("[CourseUser] Failed to load import configuration", error)
    errorMessage.value = error?.response?.data?.detail || error?.message || t("An error occurred")
  }
}

async function runImport() {
  if (!selectedFile.value) {
    return
  }

  saving.value = true
  errorMessage.value = ""
  successMessage.value = ""
  invalidRows.value = []
  failedRows.value = []

  try {
    const response = await courseUserService.importCsv(selectedFile.value, replaceUsers.value, requestParams())
    invalidRows.value = response.invalidRows || []
    failedRows.value = response.failed || []

    if (response.success) {
      successMessage.value = response.message || t("List of users subscribed to course")
      return
    }

    errorMessage.value = response.message || t("Errors when importing file")
  } catch (error) {
    console.error("[CourseUser] Failed to import course users", error)
    errorMessage.value = error?.response?.data?.detail || error?.message || t("An error occurred")
  } finally {
    saving.value = false
  }
}

onMounted(loadImportConfiguration)
</script>
