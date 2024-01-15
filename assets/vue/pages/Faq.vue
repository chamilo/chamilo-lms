<template>
  <!-- List of pages of category "faq" -->
  <div class="container mx-auto flex gap-8">
    <div
      v-if="pages.length"
      class="flex-1"
    >
      <PageCardList
        :pages="pages"
        class="grid gap-4 grid-cols-1"
      />
    </div>
  </div>
</template>

<script setup>
import { ref } from "vue"
import { useStore } from "vuex"
import { useI18n } from "vue-i18n"
import PageCardList from "../components/page/PageCardList"

const store = useStore()
const { locale } = useI18n()

const pages = ref([])

const findAllPages = () => {
  pages.value = []

  store
    .dispatch("page/findAll", {
      "category.title": "faq",
      enabled: "1",
      locale: locale.value,
    })
    .then((response) => (pages.value = response))
}

findAllPages()
</script>
