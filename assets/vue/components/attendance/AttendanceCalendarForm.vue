<template>
  <form class="flex flex-col gap-6 mt-6">
    <!-- Start Date -->
    <BaseCalendar
      id="date_time"
      v-model="formData.startDate"
      :label="t('Start Date')"
      required
      show-time
    />

    <!-- Repeat Date -->
    <BaseCheckbox
      id="repeat"
      v-model="formData.repeatDate"
      :label="t('Repeat date')"
      @change="toggleRepeatOptions"
      name="repeat"
    />

    <div v-if="formData.repeatDate">
      <!-- Repeat Type -->
      <BaseSelect
        v-model="formData.repeatType"
        :label="t('Repeat type')"
        :options="repeatTypeOptions"
        required
      />

      <!-- Number of Days for Every X Days -->
      <div v-if="formData.repeatType === 'every-x-days'">
        <BaseInputNumber
          v-model="formData.repeatDays"
          :label="t('Number of days')"
          :min="1"
          id="xdays_number"
        />
      </div>

      <!-- Repeat End Date -->
      <BaseCalendar
        id="end_date_time"
        v-model="formData.repeatEndDate"
        :label="t('Repeat end date')"
        :show-time="true"
        required
      />
    </div>

    <!-- Duration in minutes -->
    <BaseInputNumber
      id="duration_minutes"
      v-model="formData.duration"
      :label="t('Duration (minutes)')"
      :min="1"
    />

    <!-- Group -->
    <BaseSelect
      v-model="formData.group"
      :label="t('Group')"
      :options="groupOptions"
      required
    />

    <!-- Buttons -->
    <LayoutFormButtons>
      <BaseButton
        :label="t('Back')"
        icon="arrow-left"
        type="black"
        @click="$emit('back-pressed')"
      />
      <BaseButton
        :label="t('Save')"
        icon="check"
        type="success"
        @click="submitForm"
      />
    </LayoutFormButtons>
  </form>
</template>
<script setup>
import { onMounted, reactive, ref } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute } from "vue-router"
import attendanceService from "../../services/attendanceService"
import BaseCalendar from "../../components/basecomponents/BaseCalendar.vue"
import BaseCheckbox from "../../components/basecomponents/BaseCheckbox.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import LayoutFormButtons from "../../components/layout/LayoutFormButtons.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseInputNumber from "../basecomponents/BaseInputNumber.vue"

const { t } = useI18n()
const emit = defineEmits(["back-pressed"])
const route = useRoute()
const parentResourceNodeId = ref(Number(route.params.node))

const formData = reactive({
  startDate: "",
  repeatDate: false,
  repeatType: "",
  repeatEndDate: "",
  repeatDays: 0,
  group: "",
  duration: 60,
})

const repeatTypeOptions = [
  { label: t("Daily"), value: "daily" },
  { label: t("Weekly"), value: "weekly" },
  { label: t("Bi-weekly"), value: "bi-weekly" },
  { label: t("Every x days"), value: "every-x-days" },
  { label: t("Monthly, by date"), value: "monthly-by-date" },
]

const groupOptions = ref([])

/**
 * Normalize a value coming from BaseCalendar into a local date-time string
 * without timezone information.
 *
 * Goal:
 * - Treat picked time as local time.
 * - Avoid sending UTC with Z or offsets to the backend.
 */
const normalizeToLocalDateTime = (value) => {
  if (!value) {
    return null
  }

  // Case 1: Date instance -> build local "YYYY-MM-DDTHH:mm:ss"
  if (value instanceof Date) {
    const year = value.getFullYear()
    const month = String(value.getMonth() + 1).padStart(2, "0")
    const day = String(value.getDate()).padStart(2, "0")
    const hours = String(value.getHours()).padStart(2, "0")
    const minutes = String(value.getMinutes()).padStart(2, "0")
    const seconds = String(value.getSeconds()).padStart(2, "0")

    return `${year}-${month}-${day}T${hours}:${minutes}:${seconds}`
  }

  // Case 2: string -> strip timezone/offset, keep local time
  if (typeof value === "string") {
    // Examples:
    // - "2025-11-20T13:00"
    // - "2025-11-20T13:00:00"
    // - "2025-11-20T13:00:00.000Z"
    // - "2025-11-20T13:00:00+01:00"
    const match = value.match(/^(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(?::\d{2})?)/)

    if (match) {
      const base = match[1]
      // If we only have "YYYY-MM-DDTHH:mm", add ":00" for seconds
      if (base.length === 16) {
        return `${base}:00`
      }

      return base
    }

    // Fallback: if unknown format, send as-is
    return value
  }

  // Fallback for unsupported types
  return null
}

const toggleRepeatOptions = () => {
  if (!formData.repeatDate) {
    formData.repeatType = ""
    formData.repeatEndDate = ""
    formData.repeatDays = 0
  }
}

const submitForm = async (event) => {
  // Prevent default form submit behavior
  if (event && typeof event.preventDefault === "function") {
    event.preventDefault()
  }

  // Basic required fields checks
  if (!formData.startDate) {
    console.error("[Attendance] Start date is required.")
    return
  }

  if (formData.repeatDate && (!formData.repeatType || !formData.repeatEndDate)) {
    console.error("[Attendance] Repeat settings are incomplete.")
    return
  }

  // Normalize date/time values as local strings without timezone
  const normalizedStartDate = normalizeToLocalDateTime(formData.startDate)
  const normalizedRepeatEndDate = formData.repeatDate
    ? normalizeToLocalDateTime(formData.repeatEndDate)
    : null

  const payload = {
    startDate: normalizedStartDate,
    repeatDate: formData.repeatDate,
    repeatType: formData.repeatType || null,
    repeatEndDate: normalizedRepeatEndDate,
    repeatDays: formData.repeatType === "every-x-days" ? formData.repeatDays : null,
    group: formData.group ? parseInt(formData.group, 10) : null,
    duration: formData.duration,
  }

  try {
    await attendanceService.addAttendanceCalendar(route.params.id, payload)
    emit("back-pressed")
  } catch (error) {
    console.error("Error adding attendance calendar entry:", error)
  }
}

const loadGroups = async () => {
  try {
    groupOptions.value = await attendanceService.fetchGroups(parentResourceNodeId.value)
  } catch (error) {
    console.error("Error loading groups:", error)
  }
}

onMounted(loadGroups)
</script>
