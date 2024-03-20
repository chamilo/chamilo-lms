<script setup>
import ShowLinks from "../resource_links/ShowLinks.vue"

defineProps({
  event: {
    type: Object,
    required: true,
  },
})
</script>

<template>
  <div class="invitations-info">
    <h6
      v-t="'Invitations'"
      class="invitations-info__title"
    />

    <div
      v-if="event.resourceLinkListFromEntity.length"
      class="invitations-info__item"
    >
      <p v-t="'Invitees'" />
      <div>
        <ShowLinks
          :item="event"
          :show-status="false"
        />
      </div>
    </div>
  </div>
</template>
