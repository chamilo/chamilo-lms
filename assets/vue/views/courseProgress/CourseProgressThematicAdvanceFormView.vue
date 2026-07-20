<template>
  <section class="space-y-6">
    <div
      v-if="isLoading"
      class="rounded-xl border border-gray-20 bg-white p-6 text-center text-sm text-gray-600 shadow-sm"
      role="status"
    >
      {{ t("Loading...") }}
    </div>

    <div
      v-else-if="loadErrorMessage"
      class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"
      role="alert"
    >
      {{ loadErrorMessage }}
    </div>

    <form
      v-else
      class="space-y-6"
      novalidate
      @submit.prevent="saveAdvance"
    >
      <div
        v-if="formErrorMessage"
        class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"
        role="alert"
        aria-live="polite"
      >
        {{ formErrorMessage }}
      </div>

      <BaseCard>
        <template #title>
          <div class="flex items-center gap-2">
            <BaseIcon
              icon="agenda-plan"
              size="normal"
            />
            <span>{{ form.iid ? t("Edit thematic advance") : t("New thematic advance") }}</span>
          </div>
        </template>

        <div class="space-y-6">
          <div
            class="prose max-w-none break-words text-gray-90"
            v-html="thematicTitle"
          ></div>

          <fieldset class="space-y-3 rounded-xl border border-gray-25 p-4">
            <legend class="px-2 text-sm font-semibold text-gray-90">
              {{ t("Start date options") }}
            </legend>

            <label class="flex cursor-pointer items-center gap-3 text-sm text-gray-90">
              <input
                id="from_attendance"
                v-model="form.dateSource"
                :disabled="attendanceOptions.length === 0"
                name="start_date_type"
                type="radio"
                value="attendance"
              />
              <span>{{ t("Start date taken from an attendance date") }}</span>
            </label>

            <label class="flex cursor-pointer items-center gap-3 text-sm text-gray-90">
              <input
                id="custom_date"
                v-model="form.dateSource"
                name="start_date_type"
                type="radio"
                value="custom"
              />
              <span>{{ t("Custom start date") }}</span>
            </label>
          </fieldset>

          <div
            v-if="form.dateSource === 'attendance'"
            class="space-y-5"
          >
            <div
              v-if="attendanceOptions.length === 0"
              class="rounded-lg border border-gray-25 bg-support-2 p-4 text-sm italic text-gray-90"
            >
              {{ t("There is no attendance sheet in this course") }}
            </div>

            <template v-else>
              <BaseSelect
                id="course_progress_attendance"
                v-model="form.attendanceId"
                :is-invalid="formSubmitted && !form.attendanceId"
                :label="t('Attendances')"
                :message-text="formSubmitted && !form.attendanceId ? t('Please fill all required fields') : null"
                name="attendance_select"
                :options="attendanceOptions"
              />

              <BaseSelect
                v-if="attendanceDateOptions.length > 0"
                id="course_progress_attendance_date"
                v-model="form.attendanceCalendarId"
                :is-invalid="formSubmitted && !form.attendanceCalendarId"
                :label="t('Start Date')"
                :message-text="
                  formSubmitted && !form.attendanceCalendarId ? t('Please fill all required fields') : null
                "
                name="start_date_by_attendance"
                :options="attendanceDateOptions"
              />

              <div
                v-else
                class="rounded-lg border border-gray-25 bg-support-2 p-4 text-sm italic text-gray-90"
              >
                {{ t("There is no date/time registered yet") }}
              </div>
            </template>
          </div>

          <BaseCalendar
            v-else
            id="course_progress_custom_start_date"
            v-model="form.customStartDate"
            :error-text="t('Please fill all required fields')"
            :is-invalid="formSubmitted && !form.customStartDate"
            :label="t('Start Date')"
            show-time
          />

          <BaseInputNumber
            id="course_progress_duration"
            v-model="form.duration"
            :error-text="t('Please fill all required fields')"
            :is-invalid="formSubmitted && (!Number.isInteger(form.duration) || form.duration < 1)"
            :label="t('Duration in hours')"
            :min="1"
          />

          <BaseTinyEditor
            v-model="form.content"
            editor-id="course_progress_thematic_advance_content"
            :editor-config="contentEditorConfig"
            :full-page="false"
            :title="t('Content')"
          />
        </div>
      </BaseCard>

      <div class="flex flex-wrap justify-end gap-2">
        <BaseButton
          icon="back"
          :label="t('Cancel')"
          type="plain"
          :route="listRoute"
        />
        <BaseButton
          icon="save"
          :is-loading="isSaving"
          :label="t('Save')"
          name="save"
          type="success"
          is-submit
        />
      </div>
    </form>
  </section>
</template>

<script setup>
import { computed, onMounted, ref, watch } from "vue"
import { DateTime } from "luxon"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseCalendar from "../../components/basecomponents/BaseCalendar.vue"
import BaseCard from "../../components/basecomponents/BaseCard.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import BaseInputNumber from "../../components/basecomponents/BaseInputNumber.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseTinyEditor from "../../components/basecomponents/BaseTinyEditor.vue"
import { useFormatDate } from "../../composables/formatDate"
import courseProgressService from "../../services/courseProgressService"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const { getCurrentTimezone } = useFormatDate()

const isLoading = ref(false)
const isSaving = ref(false)
const formSubmitted = ref(false)
const loadErrorMessage = ref("")
const formErrorMessage = ref("")
const thematicTitle = ref("")
const attendanceOptions = ref([])

const form = ref({
  iid: null,
  thematicId: 0,
  dateSource: "custom",
  customStartDate: null,
  attendanceId: null,
  attendanceCalendarId: null,
  duration: 1,
  content: "",
  csrfToken: "",
})

const contentEditorConfig = {
  toolbar: "bold italic underline | bullist numlist | link unlink | removeformat",
  menubar: false,
  height: 240,
}

const thematicId = computed(() => Number(route.params.thematicId || 0))
const advanceId = computed(() => Number(route.params.advanceId || 0))

const listRoute = computed(() => ({
  name: "CourseProgressThematicAdvanceList",
  params: {
    node: route.params.node,
    thematicId: thematicId.value,
  },
  query: getContextParams(),
}))

const selectedAttendance = computed(() =>
  attendanceOptions.value.find((attendance) => Number(attendance.value) === Number(form.value.attendanceId)),
)

const attendanceDateOptions = computed(() =>
  Array.isArray(selectedAttendance.value?.dates) ? selectedAttendance.value.dates : [],
)

function getQueryValue(value) {
  return Array.isArray(value) ? value[0] : value
}

function getContextParams() {
  const params = {
    cid: getQueryValue(route.query.cid),
  }
  const sid = Number(getQueryValue(route.query.sid) || 0)
  const gid = Number(getQueryValue(route.query.gid) || 0)

  if (sid > 0) {
    params.sid = sid
  }

  if (gid > 0) {
    params.gid = gid
  }

  if (Object.prototype.hasOwnProperty.call(route.query, "isStudentView")) {
    params.isStudentView = getQueryValue(route.query.isStudentView)
  }

  return params
}

function getFormParams() {
  const params = getContextParams()

  if (advanceId.value > 0) {
    params.id = advanceId.value
  }

  return params
}

function parseDate(value) {
  if (typeof value !== "string" || value === "") {
    return null
  }

  const strippedValue = value.replace(/[Z]$|[+-]\d{2}:\d{2}$/, "")
  const parts = strippedValue.match(/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2})(?::(\d{2}))?$/)

  if (!parts) {
    return null
  }

  const date = new Date(
    Number(parts[1]),
    Number(parts[2]) - 1,
    Number(parts[3]),
    Number(parts[4]),
    Number(parts[5]),
    Number(parts[6] || 0),
  )

  return Number.isNaN(date.getTime()) ? null : date
}

function serializeDate(value) {
  if (!(value instanceof Date) || Number.isNaN(value.getTime())) {
    return null
  }

  const dateTime = DateTime.fromObject(
    {
      year: value.getFullYear(),
      month: value.getMonth() + 1,
      day: value.getDate(),
      hour: value.getHours(),
      minute: value.getMinutes(),
      second: value.getSeconds(),
    },
    { zone: getCurrentTimezone() },
  )

  return dateTime.isValid ? dateTime.toISO({ suppressMilliseconds: true }) : null
}

async function loadForm() {
  isLoading.value = true
  loadErrorMessage.value = ""
  formErrorMessage.value = ""
  formSubmitted.value = false

  try {
    const response = await courseProgressService.getThematicAdvanceForm(thematicId.value, getFormParams())
    thematicTitle.value = response.thematicTitle || ""
    attendanceOptions.value = Array.isArray(response.attendances) ? response.attendances : []
    form.value = {
      iid: response.iid ?? null,
      thematicId: response.thematicId || thematicId.value,
      dateSource: response.dateSource === "attendance" ? "attendance" : "custom",
      customStartDate: parseDate(response.startDate),
      attendanceId: response.attendanceId ?? null,
      attendanceCalendarId: response.attendanceCalendarId ?? null,
      duration: Number(response.duration || 1),
      content: response.content || "",
      csrfToken: response.csrfToken || "",
    }
  } catch (error) {
    console.error("Error loading thematic advance form", error)
    loadErrorMessage.value =
      error?.response?.data?.detail || error?.response?.data?.["hydra:description"] || t("An error occurred")
  } finally {
    isLoading.value = false
  }
}

function validateForm() {
  if (!Number.isInteger(form.value.duration) || form.value.duration < 1) {
    return false
  }

  if (form.value.dateSource === "custom") {
    return form.value.customStartDate instanceof Date && !Number.isNaN(form.value.customStartDate.getTime())
  }

  return Boolean(form.value.attendanceId && form.value.attendanceCalendarId)
}

async function saveAdvance() {
  if (isSaving.value) {
    return
  }

  formSubmitted.value = true
  formErrorMessage.value = ""

  if (!validateForm()) {
    formErrorMessage.value = t("Please fill all required fields")

    return
  }

  const payload = {
    iid: form.value.iid,
    thematicId: thematicId.value,
    dateSource: form.value.dateSource,
    startDate: form.value.dateSource === "custom" ? serializeDate(form.value.customStartDate) : null,
    attendanceId: form.value.dateSource === "attendance" ? Number(form.value.attendanceId) : null,
    attendanceCalendarId: form.value.dateSource === "attendance" ? Number(form.value.attendanceCalendarId) : null,
    duration: Number(form.value.duration),
    content: form.value.content,
    csrfToken: form.value.csrfToken,
  }

  isSaving.value = true

  try {
    if (form.value.iid) {
      await courseProgressService.updateThematicAdvance(thematicId.value, form.value.iid, payload, getContextParams())
    } else {
      await courseProgressService.createThematicAdvance(thematicId.value, payload, getContextParams())
    }

    await router.push({
      ...listRoute.value,
      query: {
        ...listRoute.value.query,
        saved: 1,
      },
    })
  } catch (error) {
    console.error("Error saving thematic advance", error)
    formErrorMessage.value =
      error?.response?.data?.detail || error?.response?.data?.["hydra:description"] || t("An error occurred")
  } finally {
    isSaving.value = false
  }
}

watch(
  () => form.value.attendanceId,
  () => {
    if (form.value.dateSource !== "attendance") {
      return
    }

    const availableIds = attendanceDateOptions.value.map((date) => Number(date.value))
    if (availableIds.includes(Number(form.value.attendanceCalendarId))) {
      return
    }

    form.value.attendanceCalendarId = attendanceDateOptions.value[0]?.value ?? null
  },
)

watch(
  () => form.value.dateSource,
  (dateSource) => {
    if (dateSource !== "attendance") {
      return
    }

    if (!form.value.attendanceId && attendanceOptions.value.length > 0) {
      const firstAvailableAttendance =
        attendanceOptions.value.find((attendance) => Array.isArray(attendance.dates) && attendance.dates.length > 0) ||
        attendanceOptions.value[0]

      form.value.attendanceId = firstAvailableAttendance?.value ?? null
    }
  },
)

onMounted(loadForm)

watch(
  () => [
    route.params.thematicId,
    route.params.advanceId,
    route.query.cid,
    route.query.sid,
    route.query.gid,
    route.query.isStudentView,
  ],
  loadForm,
)
</script>
