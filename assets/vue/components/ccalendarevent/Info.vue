<template>
  <h5>{{ event.title }}</h5>
  {{ $filters.abbreviatedDatetime(event.startDate) }}
    
  <p v-if="event.endDate">
    {{ $filters.abbreviatedDatetime(event.endDate) }}
  </p>

  <hr class="my-2">

  <div class="mb-3" v-html="event.content" />

  <h6 class="text-h5"> {{ $t('Invitees') }}</h6>

  <ShowLinks
      :item="event"
      :show-status="false"
  />
</template>

<script>
import ShowLinks from "../resource_links/ShowLinks";
export default {
  name: "CCalendarEventInfo",
  components: {ShowLinks},
  props: {
    event: {
      type: Object,
      required: true
    }
  }
}
</script>
