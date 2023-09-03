<template>
  <div class="flex flex-col gap-4">
    <h5 v-text="event.title" />

    <p v-text="abbreviatedDatetime(event.startDate)" />

    <p
      v-if="event.endDate"
      v-text="abbreviatedDatetime(event.endDate)"
    />

    <hr />

    <div v-html="event.content" />

    <h6 v-t="'Invitees'" />

    <ShowLinks
      :item="event"
      :show-status="false"
    />
  </div>
</template>

<script setup>
import { useFormatDate } from "../../composables/formatDate"

import ShowLinks from "../resource_links/ShowLinks"

const { abbreviatedDatetime } = useFormatDate()

// eslint-disable-next-line no-undef
defineProps({
  event: {
    type: Object,
    required: true,
  },
})
</script>