<template>
  <LayoutFormGeneric>
    <template #header>
      <BaseIcon icon="link-add" />
      {{ t("Update link") }}
    </template>

    <LinkForm
      :link-id="linkId"
      @back-pressed="goBack"
    />
  </LayoutFormGeneric>
</template>

<script setup>
import LinkForm from "../../components/links/LinkForm.vue"
import { useRoute, useRouter } from "vue-router"
import { computed } from "vue"
import { useI18n } from "vue-i18n"
import LayoutFormGeneric from "../../components/layout/LayoutFormGeneric.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"

const { t } = useI18n()
const router = useRouter()
const route = useRoute()

const goBack = () => {
  router.push({
    name: "LinksList",
    query: route.query,
  })
}

const linkId = computed(() => {
  return route.params.id
})
</script>
