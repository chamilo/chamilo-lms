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
    <ShowLinks
      v-else
      :item="event"
      :show-status="false"
      :clickable-course="true"
    />

    <CalendarRemindersInfo :event="event" />
  </div>
</template>

<script setup>
import { useFormatDate } from "../../composables/formatDate"
import ShowLinks from "../resource_links/ShowLinks"
import { useCalendarInvitations } from "../../composables/calendar/calendarInvitations"
import { type } from "../../constants/entity/ccalendarevent"
import CalendarEventSubscriptionsInfo from "./CalendarEventSubscriptionsInfo.vue"
import CalendarEventInvitationsInfo from "./CalendarEventInvitationsInfo.vue"
import CalendarRemindersInfo from "./CalendarRemindersInfo.vue"

const { abbreviatedDatetime } = useFormatDate()
const { allowCollectiveInvitations } = useCalendarInvitations()

// eslint-disable-next-line no-undef
defineProps({
  event: {
    type: Object,
    required: true,
  },
})
</script>
