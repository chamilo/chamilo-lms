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
    default: null,
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

watchEffect(async (onCleanup) => {
  let cancelled = false

  onCleanup(() => {
    cancelled = true
  })

  // When the parent provides the pages prop, it owns the list even while it is
  // temporarily empty during an API request. Falling back to the home pages in
  // that state starts a second request that can finish later and overwrite the
  // parent-provided Index/FAQ/Demo pages.
  if (Array.isArray(props.pages)) {
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

  if (cancelled) {
    return
  }

  if (localizedPages.length) {
    pageList.value = localizedPages
    return
  }

  const fallbackPages = await fetchPages(baseParams)

  if (cancelled) {
    return
  }

  pageList.value = fallbackPages.length ? [fallbackPages[0]] : []
})
</script>
