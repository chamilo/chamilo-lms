<template>
  <div
    v-if="pageList.length"
    class="flex flex-col gap-4"
  >
    <PageCard
      v-for="page in pageList"
      :key="page.id"
      :page="page"
    />
  </div>
</template>

<script setup>
import { ref, watchEffect } from "vue"
import { useI18n } from "vue-i18n"

import PageCard from "./PageCard.vue"
import pageService from "../../services/page"

const { locale } = useI18n()

const props = defineProps({
  pages: {
    type: Array,
    required: false,
    default: () => [],
  },
})

const pageList = ref([])

async function fetchPages(params) {
  const response = await pageService.findAll({
    params,
  })

  const json = await response.json()

  return json["hydra:member"] ?? []
}

watchEffect(async () => {
  if (props.pages.length) {
    pageList.value = props.pages
    return
  }

  const baseParams = {
    "category.title": "home",
    enabled: "1",
  }

  const localizedPages = await fetchPages({
    ...baseParams,
    locale: locale.value,
  })

  if (localizedPages.length) {
    pageList.value = localizedPages
    return
  }

  const fallbackPages = await fetchPages(baseParams)

  pageList.value = fallbackPages.length ? [fallbackPages[0]] : []
})
</script>
