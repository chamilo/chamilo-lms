<template>
  <LayoutFormGeneric>
    <template #header>
      <BaseIcon icon="plus" />
      {{ t("Add new glossary term") }}
    </template>

    <GlossaryForm @back-pressed="goBack" />
  </LayoutFormGeneric>
</template>

<script setup>
import GlossaryForm from "../../components/glossary/GlossaryForm.vue"
import { useRoute, useRouter } from "vue-router"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import { useI18n } from "vue-i18n"
import LayoutFormGeneric from "../../components/layout/LayoutFormGeneric.vue"

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