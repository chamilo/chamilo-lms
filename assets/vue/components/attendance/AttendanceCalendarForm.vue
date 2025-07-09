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
      v-model="formData.repeatDate"
      :label="t('Repeat Date')"
      @change="toggleRepeatOptions"
    />

    <div v-if="formData.repeatDate">
      <!-- Repeat Type -->
      <BaseSelect
        v-model="formData.repeatType"
        :label="t('Repeat Type')"
        :options="repeatTypeOptions"
        required
      />

      <!-- Number of Days for Every X Days -->
      <div v-if="formData.repeatType === 'every-x-days'">
        <BaseInputNumber
          v-model="formData.repeatDays"
          :label="t('Number of days')"
          type="number"
          min="1"
          required
        />
      </div>

      <!-- Repeat End Date -->
      <BaseCalendar
        id="end_date_time"
        v-model="formData.repeatEndDate"
        :label="t('Repeat End Date')"
        :show-time="true"
        required
      />
    </div>

    <BaseInputNumber
      v-model="formData.duration"
      :label="t('Duration (minutes)')"
      type="number"
      min="1"
      required
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
import attendanceService from "../../services/attendanceService"
import BaseCalendar from "../../components/basecomponents/BaseCalendar.vue"
import BaseCheckbox from "../../components/basecomponents/BaseCheckbox.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import LayoutFormButtons from "../../components/layout/LayoutFormButtons.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import { useRoute } from "vue-router"
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
  duration: null,
})

const repeatTypeOptions = [
  { label: t("Daily"), value: "daily" },
  { label: t("Weekly"), value: "weekly" },
  { label: t("Bi-Weekly"), value: "bi-weekly" },
  { label: t("Every X Days"), value: "every-x-days" },
  { label: t("Monthly by Date"), value: "monthly-by-date" },
]

const groupOptions = ref([])

const toggleRepeatOptions = () => {
  if (!formData.repeatDate) {
    formData.repeatType = ""
    formData.repeatEndDate = ""
    formData.repeatDays = 0
  }
}

const submitForm = async () => {
  if (!formData.startDate) {
    return
  }

  if (formData.repeatDate && (!formData.repeatType || !formData.repeatEndDate)) {
    return
  }

  const payload = {
    startDate: formData.startDate,
    repeatDate: formData.repeatDate,
    repeatType: formData.repeatType,
    repeatEndDate: formData.repeatEndDate,
    repeatDays: formData.repeatType === "every-x-days" ? formData.repeatDays : null,
    group: formData.group ? parseInt(formData.group) : null,
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
