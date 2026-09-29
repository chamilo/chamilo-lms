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

const learningPathId = computed(() => Number(route.query.lp_id || 0))
const isLearningPathContext = computed(
  () => "learnpath" === String(route.query.origin || "").toLowerCase() && learningPathId.value > 0,
)

function buildLearningPathBuilderRoute() {
  const query = { ...route.query }
  delete query.action
  delete query.create
  delete query.content
  delete query.lpItemId

  return {
    name: "LpBuilder",
    params: {
      node: Number(route.query.node || route.params.node || 0),
      lpId: learningPathId.value,
    },
    query,
  }
}

const goBack = () => {
  if (isLearningPathContext.value) {
    return router.push(buildLearningPathBuilderRoute())
  }

  return router.push({
    name: "LinksList",
    query: route.query,
  })
}

const linkId = computed(() => {
  return route.params.id
})
</script>
