<template>
  <BaseCard plain>
    <template #header>
      <div class="-mb-2 flex items-center justify-between gap-2 bg-gray-15 px-4 py-2">
        <h6 v-text="page.title" />
        <BaseButton
          v-if="isAdmin"
          icon="edit"
          :label="t('Edit')"
          size="small"
          type="black"
          @click="handleClick(page)"
        />
      </div>
    </template>

    <div v-html="safeContent" />
  </BaseCard>
</template>

<script setup>
import { computed } from "vue"
import { useI18n } from "vue-i18n"
import { useRouter } from "vue-router"
import { useSecurityStore } from "../../store/securityStore"
import { storeToRefs } from "pinia"
import DOMPurify from "dompurify"
import BaseCard from "../basecomponents/BaseCard.vue"
import BaseButton from "../basecomponents/BaseButton.vue"

const { t } = useI18n()
const router = useRouter()
const securityStore = useSecurityStore()
const { isAdmin } = storeToRefs(securityStore)

const props = defineProps({
  page: {
    type: Object,
    required: true,
  },
})

// Sanitize stored rich HTML content before rendering it with v-html.
const safeContent = computed(() => {
  return DOMPurify.sanitize(props.page?.content ?? "", {
    ADD_ATTR: ["target", "rel"],
  })
})

const handleClick = (page) => {
  router.push({
    name: "PageUpdate",
    query: { id: page["@id"] },
  })
}
</script>
