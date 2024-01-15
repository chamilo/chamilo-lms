<template>
  <div
      v-for="announcement in announcements"
      :key="announcement.id"
  >
    <SystemAnnouncementCard
        :announcement="announcement"
    />
  </div>
</template>

<script setup>
import SystemAnnouncementCard from './SystemAnnouncementCard.vue';

defineProps({
  announcements: {
    type: Array,
    required: true,
  },
})
</script>
