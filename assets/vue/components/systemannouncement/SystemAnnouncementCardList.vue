<template>
  <div v-if="announcements.length > 0">
    <SystemAnnouncementCard
      v-for="announcement in announcements"
      :key="announcement.id"
      :announcement="announcement"
    />
  </div>
</template>

<script setup>
import { ref } from "vue"
import axios from "axios"

import SystemAnnouncementCard from "./SystemAnnouncementCard.vue"

const announcements = ref([])

axios
  .get("/news/list")
  .then((response) => {
    if (Array.isArray(response.data)) {
      announcements.value = response.data
    }
  })
  .catch(function (error) {
    console.log(error)
  })
</script>
