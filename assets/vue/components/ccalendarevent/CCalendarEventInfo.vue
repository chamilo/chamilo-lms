<template>
  <div class="calendar-event-info">
    <h5 v-text="event.title" />

    <p v-text="abbreviatedDatetime(event.startDate)" />

    <p
      v-if="event.endDate"
      v-text="abbreviatedDatetime(event.endDate)"
    />

    <hr />

    <div v-html="event.content" />

    <CalendarEventSubscriptionsInfo
      v-if="type.subscription === event.invitationType"
      :event="event"
    />
    <CalendarEventInvitationsInfo
      v-else-if="type.invitation === event.invitationType"
      :event="event"
    />
    <div
      v-else-if="event.objectType === 'learning_calendar' && event.eventType"
      class="mt-2"
    >
      <strong>{{ t("Type") }}:</strong>
      {{ event.eventType }}
    </div>

    <ShowLinks
      v-else
      :clickable-course="true"
      :item="event"
      :show-status="false"
    />

    <CalendarRemindersInfo
      v-if="event.objectType !== 'learning_calendar'"
      :event="event"
    />

    <div
      v-if="event.room"
      class="mt-2"
    >
      <strong>{{ t("Room") }}:</strong>
      {{ event.room.branchTitle ? `${event.room.branchTitle} - ${event.room.title}` : event.room.title }}
    </div>
  </div>
</template>

<script setup>
import { useFormatDate } from "../../composables/formatDate"
import { useI18n } from "vue-i18n"
import ShowLinks from "../resource_links/ShowLinks"
import { type } from "../../constants/entity/ccalendarevent"
import CalendarEventSubscriptionsInfo from "./CalendarEventSubscriptionsInfo.vue"
import CalendarEventInvitationsInfo from "./CalendarEventInvitationsInfo.vue"
import CalendarRemindersInfo from "./CalendarRemindersInfo.vue"

const { abbreviatedDatetime } = useFormatDate()
const { t } = useI18n()

defineProps({
  event: {
    type: Object,
    required: true,
  },
})
</script>
