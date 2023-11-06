<template>
  <div
    v-if="pageList.length"
  >
    <PageCard
      v-for="page in pageList"
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

const props = defineProps({
  pages: {
    type: Array,
    required: false,
    default: () => [],
  }
});

const pageList = ref([])

if (props.pages.length) {
  pageList.value = props.pages;
} else {
  pageService
    .findAll({
      params : {
        "category.title": "home",
        enabled: "1",
        locale: locale.value,
      },
    })
    .then(response => response.json())
    .then(json => pageList.value = json["hydra:member"])
}
</script>
