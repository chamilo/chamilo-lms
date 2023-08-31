<template>
  <LayoutFormGeneric>
    <template #header>
      <BaseIcon icon="import" />
      {{ t("Import glossary") }}
    </template>

    <GlossaryImportForm @back-pressed="goBack" />
  </LayoutFormGeneric>
</template>

<script setup>
import GlossaryImportForm from "../../components/glossary/GlossaryImportForm.vue"
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