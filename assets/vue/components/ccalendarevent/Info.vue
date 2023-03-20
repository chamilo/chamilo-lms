<template>
  <div class="flex flex-col gap-4">
    <h5 v-text="event.title" />

    <p v-text="useAbbreviatedDatetime(event.startDate)" />

    <p
      v-if="event.endDate"
      v-text="useAbbreviatedDatetime(event.endDate)"
    />

    <hr>

    <div v-html="event.content" />

    <h6 v-t="'Invitees'" />

    <ShowLinks
      :item="event"
      :show-status="false"
    />
  </div>
</template>

<script setup>
import { useAbbreviatedDatetime } from '../../composables/formatDate.js';

import ShowLinks from '../resource_links/ShowLinks';

// eslint-disable-next-line no-undef
defineProps({
  event: {
    type: Object,
    required: true
  }
});
</script>
