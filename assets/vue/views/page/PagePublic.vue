<script setup>
import { ref } from "vue"
import { useRoute } from "vue-router"
import { useI18n } from "vue-i18n"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import pageService from "../../services/pageService"
import { useNotification } from "../../composables/notification"
import Loading from "../../components/Loading.vue"

const route = useRoute()
const { t } = useI18n()
const { showWarningNotification } = useNotification()

const isLoading = ref(true)
const page = ref()

pageService
  .getPublicPageBySlug(route.params.slug)
  .then((result) => {
    if (result) {
      page.value = result

      return
    }

    showWarningNotification(t("Not found"))
  })
  .finally(() => (isLoading.value = false))
</script>

<template>
  <div v-if="page">
    <SectionHeader :title="page.title" />

    <div
      class="wysiwyg"
      v-html="page.content"
    ></div>
  </div>
  <Loading :visible="isLoading" />
</template>
