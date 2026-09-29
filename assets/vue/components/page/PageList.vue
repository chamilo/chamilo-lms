<script setup>
import { computed, watch } from "vue"
import { useI18n } from "vue-i18n"
import PageCard from "./PageCard.vue"
import { usePagesStore } from "../../store/pagesStore"

const props = defineProps({
  categoryTitle: {
    type: String,
    required: true,
  },
})

const { locale } = useI18n()
const pagesStore = usePagesStore()

const pageList = computed(() => pagesStore.getPages(props.categoryTitle, locale.value))

pagesStore.fetchByCategory(props.categoryTitle, locale.value)

watch(locale, (newLocale) => pagesStore.fetchByCategory(props.categoryTitle, newLocale))
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
