<template>
  <!-- Public homepage (no login required) -->
  <div class="container mx-auto flex flex-columm md:flex-row gap-8">
    <Login
      v-if="!isAuthenticated"
      class="md:w-4/12 lg:order-1"
    />
    <div class="flex-1 md:w-8/12 lg:order-0">
      <SystemAnnouncementCardList />

      <PageCardList
        :pages="pages"
        class="grid gap-4 grid-cols-1"
      />
    </div>
  </div>
  <div class="container mt-4">
    <PageList category-title="footer_public" />
  </div>
</template>

<script setup>
import { computed, ref } from "vue"
import { useStore } from "vuex"
import { useI18n } from "vue-i18n"
import Login from "../components/Login"
import PageCardList from "../components/page/PageCardList"
import SystemAnnouncementCardList from "../components/systemannouncement/SystemAnnouncementCardList.vue"
import PageList from "../components/page/PageList.vue"
import { useSecurityStore } from "../store/securityStore"

const store = useStore()
const { locale } = useI18n()

const securityStore = useSecurityStore()
const isAuthenticated = computed(() => securityStore.isAuthenticated)

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
