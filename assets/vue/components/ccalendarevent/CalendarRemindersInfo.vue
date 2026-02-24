<script setup>
import { useI18n } from "vue-i18n"
import { useCalendarReminders } from "../../composables/calendar/calendarReminders"
import BaseIcon from "../basecomponents/BaseIcon.vue"

const { t } = useI18n()
const { decodeDateInterval } = useCalendarReminders()

defineProps({
  event: {
    type: Object,
    required: true,
  },
})
</script>

<template>
  <div
    v-if="event.reminders && event.reminders.length > 0"
    class="reminders-info"
  >
    <h6
      v-text="t('Notification to remind the event')"
      class="reminders-info__title"
    />

    <ul class="reminders-info__list">
      <li
        v-for="(reminder, i) in event.reminders"
        :key="i"
        class="reminders-info__item"
      >
        <BaseIcon
          icon="event-reminder"
          size="small"
        />
        {{ decodeDateInterval(reminder) }}
      </li>
    </ul>
  </div>
</template>
