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
  const lpId = Number(route.query.lp_id || 0)
  const isLearningPathContext = "learnpath" === String(route.query.origin || "").toLowerCase() && lpId > 0

  if (isLearningPathContext) {
    const query = { ...route.query }
    delete query.action
    delete query.create
    delete query.content

    router.push({
      name: "LpBuilder",
      params: {
        node: Number(route.query.node || route.params.node || 0),
        lpId,
      },
      query,
    })

    return
  }

  router.push({
    name: "LinksList",
    query: route.query,
  })
}
</script>
