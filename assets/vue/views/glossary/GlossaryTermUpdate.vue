<template>
  <LayoutFormGeneric>
    <template #header>
      <BaseIcon icon="plus" />
      {{ t("Edit glossary term") }}
    </template>

    <GlossaryForm
      :term-id="termId"
      @back-pressed="goBack"
    />
  </LayoutFormGeneric>
</template>

<script setup>
import GlossaryForm from "../../components/glossary/GlossaryForm.vue"
import { useRoute, useRouter } from "vue-router"
import { computed } from "vue"
import LayoutFormGeneric from "../../components/layout/LayoutFormGeneric.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import { useI18n } from "vue-i18n"

const router = useRouter()
const route = useRoute()
const { t } = useI18n()

const termId = computed(() => route.params.id)

const goBack = () => {
  router.push({
    name: "GlossaryList",
    query: route.query,
  })
}
</script>