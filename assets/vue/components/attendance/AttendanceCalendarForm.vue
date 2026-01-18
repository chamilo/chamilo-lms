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
import { useLocale } from "../../composables/locale"

const { t } = useI18n()
const emit = defineEmits(["back-pressed"])
const route = useRoute()
const parentResourceNodeId = ref(Number(route.params.node))

const { appLocale } = useLocale()
const localePrefix = ref(getLocalePrefix(appLocale.value))

function getLocalePrefix(locale) {
  const defaultLang = "en"
  return typeof locale === "string" ? locale.split("_")[0] : defaultLang
}

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

const pad2 = (n) => String(n).padStart(2, "0")

const buildIsoLocalDateTime = (d) => {
  const year = d.getFullYear()
  const month = pad2(d.getMonth() + 1)
  const day = pad2(d.getDate())
  const hours = pad2(d.getHours())
  const minutes = pad2(d.getMinutes())
  const seconds = pad2(d.getSeconds())
  return `${year}-${month}-${day}T${hours}:${minutes}:${seconds}`
}

const parseIsoLocalToDate = (isoLocal) => {
  if (typeof isoLocal !== "string") return null
  const m = isoLocal.match(/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})$/)
  if (!m) return null
  const year = Number(m[1])
  const month = Number(m[2]) - 1
  const day = Number(m[3])
  const hh = Number(m[4])
  const mm = Number(m[5])
  const ss = Number(m[6])
  return new Date(year, month, day, hh, mm, ss)
}

const parseLocaleDateTimeStringToDate = (raw, locale = "en") => {
  if (!raw || typeof raw !== "string") return null

  const value = raw.trim()
  if (!value) return null

  // ISO-like "YYYY-MM-DD" or "YYYY-MM-DDTHH:mm(:ss)"
  {
    const m = value.match(/^(\d{4})-(\d{2})-(\d{2})(?:[T\s](\d{2}):(\d{2})(?::(\d{2}))?)?$/)
    if (m) {
      const year = Number(m[1])
      const month = Number(m[2]) - 1
      const day = Number(m[3])
      const hh = m[4] ? Number(m[4]) : 0
      const mm = m[5] ? Number(m[5]) : 0
      const ss = m[6] ? Number(m[6]) : 0
      return new Date(year, month, day, hh, mm, ss)
    }
  }

  // Common locale formats: "MM/DD/YYYY", "DD/MM/YYYY", "DD.MM.YYYY"
  // Optional time: "HH:MM", "HH:MM:SS", optional AM/PM.
  {
    const cleaned = value.replace(/,/g, " ")
    const m = cleaned.match(/^(\d{1,2})[\/.](\d{1,2})[\/.](\d{2,4})(?:\s+(\d{1,2}):(\d{2})(?::(\d{2}))?\s*(AM|PM)?)?$/i)
    if (m) {
      const a = Number(m[1])
      const b = Number(m[2])
      const yearRaw = Number(m[3])
      const year = yearRaw < 100 ? 2000 + yearRaw : yearRaw

      let month
      let day

      // Decide ordering:
      // - If locale is "en", default is MM/DD
      // - Otherwise default is DD/MM
      // - If the first number > 12, it's definitely a day
      // - If the second number > 12, it's definitely a day (so first is month)
      if (a > 12) {
        day = a
        month = b
      } else if (b > 12) {
        month = a
        day = b
      } else if (locale === "en") {
        month = a
        day = b
      } else {
        day = a
        month = b
      }

      let hh = m[4] ? Number(m[4]) : 0
      const mm = m[5] ? Number(m[5]) : 0
      const ss = m[6] ? Number(m[6]) : 0
      const ap = m[7] ? String(m[7]).toUpperCase() : null

      if (ap === "AM" || ap === "PM") {
        if (hh === 12) {
          hh = ap === "AM" ? 0 : 12
        } else if (ap === "PM") {
          hh += 12
        }
      }

      return new Date(year, month - 1, day, hh, mm, ss)
    }
  }

  return null
}

/**
 * Normalize a value coming from BaseCalendar into a local date-time string
 * without timezone information.
 *
 * Goal:
 * - Treat picked time as local time.
 * - Avoid sending UTC with Z or offsets to the backend.
 * - Always send an unambiguous ISO local format: "YYYY-MM-DDTHH:mm:ss".
 */
const normalizeToLocalDateTime = (value) => {
  if (!value) {
    return null
  }

  // Case 1: Date instance -> build local "YYYY-MM-DDTHH:mm:ss"
  if (value instanceof Date) {
    return buildIsoLocalDateTime(value)
  }

  // Case 2: string
  if (typeof value === "string") {
    // Strip timezone/offset, keep local time if it already looks ISO-like.
    const isoMatch = value.match(/^(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(?::\d{2})?)/)
    if (isoMatch) {
      const base = isoMatch[1]
      return base.length === 16 ? `${base}:00` : base
    }

    // Try to parse locale-specific strings like "09/01/2025" or "01/09/2025"
    const parsed = parseLocaleDateTimeStringToDate(value, localePrefix.value)
    if (parsed instanceof Date && !Number.isNaN(parsed.getTime())) {
      return buildIsoLocalDateTime(parsed)
    }

    // Fallback: do not send ambiguous raw strings to backend
    console.error("[Attendance] Unsupported date format received from calendar:", value)
    return null
  }

  // Unsupported types
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
  const normalizedRepeatEndDate = formData.repeatDate ? normalizeToLocalDateTime(formData.repeatEndDate) : null

  if (!normalizedStartDate) {
    console.error("[Attendance] Failed to normalize startDate. Aborting submit.")
    return
  }

  if (formData.repeatDate && !normalizedRepeatEndDate) {
    console.error("[Attendance] Failed to normalize repeatEndDate. Aborting submit.")
    return
  }

  // Validate repeat range
  if (formData.repeatDate) {
    const start = parseIsoLocalToDate(normalizedStartDate)
    const end = parseIsoLocalToDate(normalizedRepeatEndDate)
    if (!start || !end) {
      console.error("[Attendance] Invalid normalized date range. Aborting submit.")
      return
    }
    if (end.getTime() < start.getTime()) {
      console.error("[Attendance] repeatEndDate is before startDate. Aborting submit.")
      alert(t("Repeat end date must be after start date."))
      return
    }
  }

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
    console.error("[Attendance] Error adding attendance calendar entry:", error)
  }
}

const loadGroups = async () => {
  try {
    groupOptions.value = await attendanceService.fetchGroups(parentResourceNodeId.value)
  } catch (error) {
    console.error("[Attendance] Error loading groups:", error)
  }
}

onMounted(loadGroups)
</script>
