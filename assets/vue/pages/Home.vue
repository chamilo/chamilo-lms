<template>
  <div v-if="announcements.length">
    <SystemAnnouncementCardList :announcements="announcements" />
  </div>

  <div v-if="pages.length" class="grid gap-4 grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-2 mt-2">
    <PageCardList :pages="pages" />
  </div>
</template>

<script setup>
import axios from "axios";
import { ref } from "vue";
import { useStore } from "vuex";
import { useI18n } from "vue-i18n";
import PageCardList from "../components/page/PageCardList";
import SystemAnnouncementCardList from "../components/systemannouncement/SystemAnnouncementCardList";

const store = useStore();

const announcements = ref([]);
const pages = ref([]);

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

const { locale } = useI18n();

store
  .dispatch("page/findAll", {
    "category.title": "home",
    enabled: "1",
    locale: locale.value,
  })
  .then((response) => {
    pages.value = response;
  });
</script>
