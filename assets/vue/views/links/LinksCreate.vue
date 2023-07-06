<template>
  <LayoutFormGeneric>
    <template #header>
      <BaseIcon icon="link-add" />
      {{ t("Add a link") }}
    </template>

    <LinkForm @back-pressed="goBack" />
  </LayoutFormGeneric>
</template>

<script setup>
import LinkForm from "../../components/links/LinkForm.vue"
import { useRoute, useRouter } from "vue-router"
import { useI18n } from "vue-i18n"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import LayoutFormGeneric from "../../components/layout/LayoutFormGeneric.vue"

const { t } = useI18n()
const router = useRouter()
const route = useRoute()

const goBack = () => {
  router.push({
    name: "LinksList",
    query: route.query,
  })
}
</script>
