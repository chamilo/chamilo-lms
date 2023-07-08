<template>
  <LayoutFormGeneric>
    <template #header>
      <BaseIcon icon="file-export" />
      {{ t("Export glossary") }}
    </template>

    <GlossaryExportForm @back-pressed="goBack" />
  </LayoutFormGeneric>
</template>

<script setup>
import GlossaryExportForm from "../../components/glossary/GlossaryExportForm.vue"
import { useRoute, useRouter } from "vue-router"
import LayoutFormGeneric from "../../components/layout/LayoutFormGeneric.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import { useI18n } from "vue-i18n"

const router = useRouter()
const route = useRoute()
const { t } = useI18n()

const goBack = () => {
  router.push({
    name: "GlossaryList",
    query: route.query,
  })
}
</script>
