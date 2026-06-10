<template>
  <div
    v-if="announcements.length > 0"
    class="flex flex-col gap-4"
  >
    <SystemAnnouncementCard
      v-for="announcement in announcements"
      :key="announcement.id"
      :announcement="announcement"
    />
  </div>
</template>

<script setup>
import { ref } from "vue"
import systemAnnouncementService from "../../services/systemAnnouncementService"

import SystemAnnouncementCard from "./SystemAnnouncementCard.vue"

const announcements = ref([])

systemAnnouncementService
  .list()
  .then((data) => {
    if (Array.isArray(data)) {
      announcements.value = data
    }
  })
  .catch(function (error) {
    console.log(error)
  })
</script>
