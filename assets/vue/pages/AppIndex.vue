<template>
  <!-- Public homepage (no login required) -->
  <div class="container mx-auto mt-4 flex flex-col md:flex-row gap-8">
    <div
      v-if="!isAuthenticated"
      class="public-home-login md:w-4/12 lg:order-1 flex flex-col gap-4"
    >
      <PluginRegion region="login_top" />
      <Login />
      <PluginRegion region="login_bottom" />
    </div>
    <div class="flex-1 md:w-8/12 lg:order-0 flex flex-col gap-4">
      <SystemAnnouncementCardList />

      <PageCardList :pages="pages" />
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
import PluginRegion from "../components/layout/PluginRegion.vue"
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

<style scoped>
.public-home-login :deep(.login-section) {
  min-height: 0;
  justify-content: flex-start;
  padding-top: 0.75rem;
}

@media (min-width: 768px) {
  .public-home-login :deep(.login-section) {
    padding-top: 2rem;
  }
}
</style>
