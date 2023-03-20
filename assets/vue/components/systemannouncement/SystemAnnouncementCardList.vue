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

<script>

import SystemAnnouncementCard from './SystemAnnouncementCard';

export default {
  name: 'SystemAnnouncementCardList',
  components: {
    SystemAnnouncementCard
  },
  props: {
    announcements: Array,
  }
};
</script>
