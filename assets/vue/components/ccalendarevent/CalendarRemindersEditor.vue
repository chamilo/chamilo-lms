<script setup>
import { useI18n } from "vue-i18n"
import BaseButton from "../basecomponents/BaseButton.vue"
import Fieldset from "primevue/fieldset"
import InputNumber from "primevue/inputnumber"
import Select from "primevue/select"
import { useCalendarReminders } from "../../composables/calendar/calendarReminders"

const { t } = useI18n()

const { periodList } = useCalendarReminders()

const model = defineModel({
  type: Object,
})

model.value.reminders = model.value.reminders || []

function addEmptyReminder() {
  model.value.reminders.push({
    count: 0,
    period: "i",
  })
}
</script>

<template>
  <Fieldset :legend="t('Reminders')">
    <div class="reminder-list space-y-4">
      <BaseButton
        :label="t('Add reminder')"
        icon="add-event-reminder"
        type="black"
        @click="addEmptyReminder"
      />

      <div
        v-for="(reminderItem, i) in model.reminders"
        :key="i"
        class="flex flex-row gap-4"
      >
        <div class="p-inputgroup">
          <InputNumber
            v-model="reminderItem.count"
            :min="0"
            :step="1"
            class="w-20"
          />
          <Select
            v-model="reminderItem.period"
            :options="periodList"
            option-label="label"
            option-value="value"
          />
          <div
            v-text="t('Before')"
            class="p-inputgroup-addon"
          />
        </div>

        <BaseButton
          :label="t('Delete')"
          icon="delete"
          only-icon
          type="danger"
          @click="model.reminders.splice(i, 1)"
        />
      </div>
    </div>
  </Fieldset>
</template>
