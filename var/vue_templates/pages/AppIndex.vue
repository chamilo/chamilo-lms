<script setup>
import { ref } from "vue"
import { useStore } from "vuex"
import { useI18n } from "vue-i18n"
import SystemAnnouncementCardList from "../../../assets/vue/components/systemannouncement/SystemAnnouncementCardList.vue"
import PageCardList from "../../../assets/vue/components/page/PageCardList.vue"

const store = useStore()
const { locale } = useI18n()

const pages = ref([])

const findAllPages = () => {
  pages.value = []

  store
    .dispatch("page/findAll", {
      "category.title": "index",
      enabled: "1",
      locale: locale.value,
    })
    .then((response) => (pages.value = response))
}

findAllPages()
</script>

<template>
  <div class="container mx-auto flex flex-columm md:flex-row gap-8">
    <div class="flex-1 md:w-8/12 lg:order-0">
      <SystemAnnouncementCardList />

      <PageCardList
        :pages="pages"
        class="grid gap-4 grid-cols-1"
      />
    </div>
  </div>
</template>
