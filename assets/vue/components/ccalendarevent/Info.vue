<template>
  <h5 class="text-xl font-semibold">{{ event.title }}</h5>

  {{ $luxonDateTime.fromISO(event.startDate).toRelative() }} ({{ $luxonDateTime.fromISO(event.startDate).toLocaleString($luxonDateTime.DATETIME_MED) }})
    
  <p v-if="event.endDate">
    {{ event.endDate }}
  </p>

  <hr class="my-2">

  <div class="text-base mb-3" v-html="event.content" />

  <h6 class="text-xl"> {{ $t('Invitees') }}</h6>

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
