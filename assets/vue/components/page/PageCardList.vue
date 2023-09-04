<template>
  <div
    v-if="pages.length"
    class="grid gap-4 grid-cols-1 lg:grid-cols-2 xl:grid-cols-2"
  >
    <PageCard
      v-for="page in pages"
      :key="page.id"
      :page="page"
    />
  </div>
</template>

<script setup>
import PageCard from "./PageCard.vue"
import pageService from "../../services/page"
import { useI18n } from "vue-i18n"
import { ref } from "vue"

const { locale } = useI18n()

const pages = ref([])

pageService
  .findAll({
    params : {
      "category.title": "home",
      enabled: "1",
      locale: locale.value,
    },
  })
  .then(response => response.json())
  .then(json => pages.value = json["hydra:member"])
</script>