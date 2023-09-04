<template>
  <div class="flex flex-col gap-4">
    <div v-if="announcements.length">
      <SystemAnnouncementCardList :announcements="announcements" />
    </div>

    <PageCardList />
  </div>
</template>

<script setup>
import axios from "axios";
import { ref } from "vue";
import PageCardList from "../components/page/PageCardList";
import SystemAnnouncementCardList from "../components/systemannouncement/SystemAnnouncementCardList";

const announcements = ref([]);

axios
  .get("/news/list")
  .then((response) => {
    if (Array.isArray(response.data)) {
      announcements.value = response.data;
    }
  })
  .catch(function (error) {
    console.log(error);
  });
</script>
