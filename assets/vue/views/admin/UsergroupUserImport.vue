<script setup>
import { ref, onMounted } from "vue"
import { useI18n } from "vue-i18n"
import usergroupAdminService from "../../services/usergroupAdminService"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import SectionHeader from "../../components/layout/SectionHeader.vue"

const { t } = useI18n()

const csrfToken = ref("")
const fileInput = ref(null)
const unsubscribe = ref(false)
const isSubmitting = ref(false)
const errorMessage = ref("")
const successMessage = ref("")
const importErrors = ref([])

async function loadCsrf() {
  try {
    const data = await usergroupAdminService.list({ page: 1, limit: 1 })
    csrfToken.value = data.importCsrfToken
  } catch {
    errorMessage.value = t("An error occurred. Please try again.")
  }
}

async function handleSubmit() {
  errorMessage.value = ""
  successMessage.value = ""
  importErrors.value = []

  const file = fileInput.value?.files?.[0]
  if (!file) {
    errorMessage.value = t("Please select a CSV file to import.")
    return
  }

  isSubmitting.value = true
  try {
    const formData = new FormData()
    formData.append("import_file", file)
    formData.append("_token", csrfToken.value)
    if (unsubscribe.value) {
      formData.append("unsubscribe", "1")
    }

    const data = await usergroupAdminService.importUserLinks(formData)
    successMessage.value = t("%d user-class links imported", [data.imported])
    if (fileInput.value) {
      fileInput.value.value = ""
    }
    unsubscribe.value = false
  } catch (err) {
    if (422 === err.response?.status && err.response.data?.errors) {
      importErrors.value = err.response.data.errors
    } else {
      errorMessage.value = t("An error occurred. Please try again.")
    }
  } finally {
    isSubmitting.value = false
  }
}

onMounted(() => {
  loadCsrf()
})
</script>

<template>
  <div class="flex flex-col gap-8">
    <SectionHeader :title="`${t('Add users to a class')} CSV`">
      <BaseButton
        :label="t('Back')"
        icon="arrow-left"
        type="plain"
        :route="{ name: 'AdminUsergroupList' }"
      />
    </SectionHeader>

    <div
      v-if="successMessage"
      class="rounded bg-green-100 px-4 py-2 text-green-800 text-sm"
    >
      {{ successMessage }}
    </div>
    <div
      v-if="errorMessage"
      class="rounded bg-red-100 px-4 py-2 text-red-800 text-sm"
    >
      {{ errorMessage }}
    </div>
    <div
      v-if="importErrors.length"
      class="rounded bg-red-50 border border-red-200 px-4 py-3"
    >
      <p class="text-sm font-medium text-red-700 mb-2">
        {{ t("The following errors occurred during import:") }}
      </p>
      <ul class="list-disc list-inside text-sm text-red-600 space-y-1">
        <li
          v-for="(err, index) in importErrors"
          :key="index"
        >
          {{ t("Line") }} {{ err.line }}: {{ err.error }}
        </li>
      </ul>
    </div>

    <form
      class="flex flex-col gap-4"
      @submit.prevent="handleSubmit"
    >
      <div class="flex flex-col gap-1">
        <label
          class="text-sm font-medium text-gray-700"
          for="import_file"
        >
          {{ t("CSV file") }} <span class="text-red-500">*</span>
        </label>
        <input
          id="import_file"
          ref="fileInput"
          name="import_file"
          type="file"
          accept=".csv"
          class="border border-gray-300 rounded px-3 py-1.5 text-sm"
        />
      </div>

      <div class="flex items-center gap-2">
        <input
          id="unsubscribe"
          v-model="unsubscribe"
          name="unsubscribe"
          type="checkbox"
          class="w-4 h-4"
        />
        <label
          class="text-sm text-gray-700"
          for="unsubscribe"
        >
          {{ t("Remove user from all group-related entities if not in the list") }}
        </label>
      </div>

      <div class="flex gap-4">
        <BaseButton
          :label="t('Import')"
          icon="import"
          type="success"
          is-submit
          :disabled="isSubmitting"
        />
        <BaseButton
          :label="t('Cancel')"
          icon="arrow-left"
          type="plain"
          :to-url="'/admin/usergroups'"
        />
      </div>
    </form>

    <!-- CSV format info box -->
    <div class="rounded-lg border border-blue-200 bg-blue-50 px-6 py-4 flex flex-col gap-3">
      <h3 class="text-sm font-semibold text-blue-800">
        {{ t("The CSV file must look like this") }}
      </h3>
      <pre class="rounded bg-white border border-blue-100 px-4 py-3 text-xs text-gray-700 overflow-x-auto">
UserName,ClassName
jdoe,class01
adam,class01</pre
      >
      <p class="text-xs text-blue-700">
        <strong>{{ t("UserName") }}</strong>
        {{ t("and") }}
        <strong>{{ t("ClassName") }}</strong>
        {{ t("are required for each row.") }}
      </p>
    </div>
  </div>
</template>
