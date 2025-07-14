<template>
  <SectionHeader :title="t('Page Layout Details')">
    <BaseButton
      icon="back"
      type="gray"
      only-icon
      :label="t('Back')"
      @click="goBack"
    />
  </SectionHeader>

  <div class="p-6 mt-6 w-full bg-white">
    <div class="mb-6">
      <p>
        <strong>{{ t("URL") }}:</strong> {{ form.url }}
      </p>
      <p v-if="form.roles">
        <strong>{{ t("Roles") }}:</strong> {{ form.roles }}
      </p>
    </div>

    <PageLayoutRenderer
      v-if="layoutJson"
      :layout="layoutJson"
    />

    <p
      v-else
      class="text-gray-600"
    >
      {{ t("Loading layout...") }}
    </p>
  </div>
</template>
<script setup>
import { ref, onMounted } from "vue"
import { useRoute, useRouter } from "vue-router"
import { useI18n } from "vue-i18n"

import SectionHeader from "../../components/layout/SectionHeader.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import PageLayoutRenderer from "../../components/pageLayout/PageLayoutRenderer.vue"

import pageService from "../../services/pageService.js"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()

const form = ref({
  url: "",
  roles: "",
})

const layoutJson = ref(null)

onMounted(async () => {
  try {
    const layout = await pageService.getPageLayout(route.params.id)

    form.value.url = layout.url
    form.value.roles = layout.roles

    if (layout.layout) {
      layoutJson.value = JSON.parse(layout.layout)
    } else {
      layoutJson.value = null
    }
  } catch (e) {
    console.error("Error loading layout:", e)
  }
})

function goBack() {
  router.push({ name: "PageLayoutList" })
}
</script>
