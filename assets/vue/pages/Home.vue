<template>
  <!-- Homepage for logged-in users -->
  <div class="flex flex-col gap-4 items-center">
    <SystemAnnouncementCardList />

    <PageCardList class="grid gap-4 grid-cols-1" />
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router';
import PageCardList from "../components/page/PageCardList";
import SystemAnnouncementCardList from "../components/systemannouncement/SystemAnnouncementCardList";
import { usePlatformConfig } from "../store/platformConfig";

const router = useRouter();
const platformConfigStore = usePlatformConfig();

const redirectValue = platformConfigStore.getSetting("platform.redirect_index_to_url_for_logged_users");
if (typeof redirectValue === 'string' && redirectValue.trim() !== '') {
  router.replace(`/${redirectValue}`);
}
</script>
