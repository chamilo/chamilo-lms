<template>
  <div class="flex flex-col space-y-4">
    <h5 v-text="event.title" />

    <p v-text="abbreviatedDatetime(event.startDate)" />

    <p
      v-if="event.endDate"
      v-text="abbreviatedDatetime(event.endDate)"
    />

    <hr />

    <div v-html="event.content" />

    <div v-if="allowCollectiveInvitations && type.invitation === event.invitationType">
      <h6 v-t="'Invitees'" />

      <ShowLinks
        :item="event"
        :show-status="false"
      />
    </div>
  </div>
</template>

<script setup>
import { useFormatDate } from "../../composables/formatDate"
import ShowLinks from "../resource_links/ShowLinks"
import { useCalendarInvitations } from "../../composables/calendar/calendarInvitations"
import { type } from "../../constants/entity/ccalendarevent"

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
