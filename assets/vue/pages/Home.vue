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
import { useRouter } from 'vue-router';
import PageCardList from "../components/page/PageCardList";
import SystemAnnouncementCardList from "../components/systemannouncement/SystemAnnouncementCardList";
import { usePlatformConfig } from "../store/platformConfig";

const announcements = ref([]);
const router = useRouter();
const platformConfigStore = usePlatformConfig();

const redirectValue = platformConfigStore.getSetting("platform.redirect_index_to_url_for_logged_users");
if (typeof redirectValue === 'string' && redirectValue.trim() !== '') {
  router.push(`/${redirectValue}`);
}

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
