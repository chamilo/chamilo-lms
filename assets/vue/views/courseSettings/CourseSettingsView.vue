<template>
  <section class="space-y-4 pb-8">
    <div
      v-if="isLoading"
      class="rounded-lg border border-gray-20 bg-white p-8 text-center text-gray-600"
      role="status"
    >
      {{ t("Loading...") }}
    </div>

    <div
      v-else-if="loadError"
      class="rounded-lg border border-red-200 bg-red-50 p-4 text-red-700"
      role="alert"
    >
      {{ loadError }}
    </div>

    <form
      v-else
      class="space-y-4"
      novalidate
      @submit.prevent="saveSettings"
    >
      <div
        v-if="successMessage"
        class="rounded-lg border border-green-200 bg-green-50 p-4 text-green-700"
        role="status"
      >
        {{ successMessage }}
      </div>

      <div
        v-if="formError"
        class="rounded-lg border border-red-200 bg-red-50 p-4 text-red-700"
        role="alert"
      >
        {{ formError }}
      </div>

      <details
        v-for="section in configuration.sections"
        :key="section.key"
        :open="section.key === firstSectionKey"
        class="group overflow-hidden rounded-lg border border-gray-25 border-l-[4px] border-l-[#f97316] bg-white shadow-sm"
      >
        <summary
          class="flex cursor-pointer list-none items-center gap-2 bg-[#fffaf7] px-4 py-3 font-semibold text-gray-800 transition hover:bg-[#fff3eb]"
        >
          <span
            class="text-gray-700 transition-transform group-open:rotate-90"
            aria-hidden="true"
            >›</span
          >
          <span class="text-[#f97316]">
            <BaseIcon :icon="section.icon || 'settings'" />
          </span>
          <span>{{ t(section.title) }}</span>
        </summary>

        <div class="border-t border-gray-25 p-4">
          <div class="grid gap-4 lg:grid-cols-2">
            <CourseSettingsField
              v-for="field in visibleFields(section)"
              :key="field.key"
              v-model="configuration.values[field.key]"
              :field="field"
            />
          </div>

          <CourseSettingsMediaPanel
            v-for="mode in mediaModes(section.key)"
            :key="`${section.key}_${mode}`"
            class="mt-5"
            :course-id="configuration.courseId"
            :integrations="configuration.integrations"
            :media="configuration.media"
            :mode="mode"
            :params="contextParams"
            :values="configuration.values"
            @error="showError"
            @message="showSuccess"
            @refresh="loadConfiguration"
          />

          <BaseTable
            v-if="section.key === 'course_legal' && legalAgreements.length > 0"
            class="mt-5"
            data-key="id"
            :text-for-empty="t('No users')"
            :total-items="legalAgreements.length"
            :values="legalAgreements"
          >
            <Column
              field="firstname"
              :header="t('First name')"
            />
            <Column
              field="lastname"
              :header="t('Last name')"
            />
            <Column
              field="username"
              :header="t('Username')"
            />
            <Column
              field="email"
              :header="t('E-mail')"
            />
            <Column :header="t('Accepted on web')">
              <template #body="{ data }">
                {{ formatAgreement(data.web_agreement, data.web_agreement_date) }}
              </template>
            </Column>
            <Column :header="t('Accepted by e-mail')">
              <template #body="{ data }">
                {{ formatAgreement(data.mail_agreement, data.mail_agreement_date) }}
              </template>
            </Column>
          </BaseTable>
        </div>
      </details>

      <div class="flex justify-end pt-2">
        <BaseButton
          icon="save"
          is-submit
          :is-loading="isSaving"
          :label="t('Save settings')"
          name="save_course_settings"
          type="success"
        />
      </div>
    </form>
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref, toRaw } from "vue"
import { useI18n } from "vue-i18n"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import { useRouteCourseContext } from "../../composables/useRouteCourseContext"
import { useCidReqStore } from "../../store/cidReq"
import courseSettingsService from "../../services/courseSettingsService"
import CourseSettingsField from "./CourseSettingsField.vue"
import CourseSettingsMediaPanel from "./CourseSettingsMediaPanel.vue"

const { t } = useI18n()
const { cid, sid, gid } = useRouteCourseContext()
const cidReqStore = useCidReqStore()

const isLoading = ref(false)
const isSaving = ref(false)
const loadError = ref("")
const formError = ref("")
const successMessage = ref("")

const configuration = reactive({
  courseId: 0,
  sessionId: null,
  resourceNodeId: 0,
  values: {},
  sections: [],
  permissions: {},
  media: {},
  integrations: {},
})

const contextParams = computed(() => ({
  cid: cid.value,
  sid: sid.value,
  gid: gid.value,
}))

const firstSectionKey = computed(() => configuration.sections[0]?.key || "")

const legalAgreements = computed(() => {
  const agreements = configuration.values.course_legal_agreements

  return Array.isArray(agreements)
    ? agreements
    : agreements && typeof agreements === "object"
      ? Object.values(agreements)
      : []
})

onMounted(loadConfiguration)

async function loadConfiguration() {
  isLoading.value = true
  loadError.value = ""
  formError.value = ""
  successMessage.value = ""

  try {
    const data = await courseSettingsService.getConfiguration(contextParams.value)
    applyConfiguration(data)
  } catch (error) {
    loadError.value = extractError(error)
  } finally {
    isLoading.value = false
  }
}

async function saveSettings() {
  if (!String(configuration.values.title || "").trim()) {
    formError.value = t("The course title is required")

    return
  }

  isSaving.value = true
  formError.value = ""
  successMessage.value = ""

  try {
    const response = await courseSettingsService.save(sanitizeValues(configuration.values), contextParams.value)
    applyConfiguration(response)
    await cidReqStore.refreshCourseById(configuration.courseId, configuration.sessionId || 0)
    successMessage.value = t(response.message || "Update successful")
    window.scrollTo({ top: 0, behavior: "smooth" })
  } catch (error) {
    formError.value = extractError(error)
    window.scrollTo({ top: 0, behavior: "smooth" })
  } finally {
    isSaving.value = false
  }
}

function applyConfiguration(data) {
  configuration.courseId = Number(data.courseId || 0)
  configuration.sessionId = data.sessionId === null || data.sessionId === undefined ? null : Number(data.sessionId)
  configuration.resourceNodeId = Number(data.resourceNodeId || 0)
  configuration.values = toPlainValue(data.values || {})
  configuration.sections = Array.isArray(data.sections) ? data.sections : []
  configuration.permissions = data.permissions || {}
  configuration.media = data.media || {}
  configuration.integrations = data.integrations || {}
}

function mediaModes(sectionKey) {
  return (
    {
      course_main: ["picture", "watermark"],
      course_legal: ["legal"],
      custom_certificate: ["certificate"],
    }[sectionKey] || []
  )
}

function visibleFields(section) {
  let fields = Array.isArray(section.fields) ? section.fields : []

  if (section.key === "course_home_notify" && !isEnabled(configuration.values.course_home_notify_enabled)) {
    return fields.filter((field) => field.key === "course_home_notify_enabled")
  }

  if (section.key !== "custom_certificate") {
    return fields
  }

  if (configuration.values.customcertificate_mode !== "course") {
    return fields.filter((field) => field.key === "customcertificate_mode")
  }

  if (Number(configuration.values.customcertificate_contents_type) !== 2) {
    fields = fields.filter((field) => field.key !== "customcertificate_contents")
  }

  if (Number(configuration.values.customcertificate_date_change) !== 1) {
    fields = fields.filter(
      (field) => !["customcertificate_date_start", "customcertificate_date_end"].includes(field.key),
    )
  }

  if (Number(configuration.values.customcertificate_type_date_expediction) !== 2) {
    fields = fields.filter(
      (field) => !["customcertificate_day", "customcertificate_month", "customcertificate_year"].includes(field.key),
    )
  }

  return fields
}

function isEnabled(value) {
  return [true, 1, "1", "true", "yes", "on"].includes(value)
}

function toPlainValue(value) {
  const rawValue = toRaw(value)

  if (Array.isArray(rawValue)) {
    return rawValue.map((item) => toPlainValue(item))
  }

  if (rawValue !== null && typeof rawValue === "object") {
    return Object.fromEntries(Object.entries(rawValue).map(([key, item]) => [key, toPlainValue(item)]))
  }

  return rawValue
}

function sanitizeValues(values) {
  const payload = toPlainValue(values)
  delete payload.course_legal_agreements
  delete payload.course_id
  delete payload.disk_quota_display
  delete payload.direct_invitation_url
  delete payload.lti_configuration_url

  return payload
}

function formatAgreement(status, date) {
  if (![1, "1", true].includes(status)) {
    return t("No")
  }

  return date ? `${t("Yes")} - ${date}` : t("Yes")
}

function extractError(error) {
  return (
    error?.response?.data?.detail ||
    error?.response?.data?.message ||
    error?.response?.data?.error ||
    error?.message ||
    t("Unexpected error")
  )
}

function showSuccess(message) {
  successMessage.value = message
  formError.value = ""
}

function showError(message) {
  formError.value = message
  successMessage.value = ""
}
</script>
