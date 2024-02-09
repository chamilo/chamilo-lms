<script setup>
import { ref } from "vue"
import { useI18n } from "vue-i18n"
import PageCard from "./PageCard.vue"
import pageService from "../../services/page"

const props = defineProps({
  categoryTitle: {
    type: String,
    required: true,
  },
})

const { locale } = useI18n()

const pageList = ref([])

pageService
  .findAll({
    params : {
      "category.title": props.categoryTitle,
      enabled: "1",
      locale: locale.value,
    },
  })
  .then(response => response.json())
  .then(json => pageList.value = json["hydra:member"])
</script>

<template>
  <div class="mt-auto">
    <PageCard
      v-for="page in pageList"
      :key="page.id"
      :page="page"
    />
  </div>
</template>
