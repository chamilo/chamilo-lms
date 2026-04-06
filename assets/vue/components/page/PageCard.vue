<template>
  <BaseCard plain>
    <template #title>
      <div class="flex items-center">
        {{ page.title }}
        <BaseButton
          v-if="isAdmin"
          :label="t('Edit')"
          :route="{ name: 'PageUpdate', query: { id: page['@id'] } }"
          class="ml-auto"
          icon="edit"
          only-icon
          size="small"
          type="secondary-text"
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
</script>
