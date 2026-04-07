<template>
  <BaseCard plain>
    <template #title>
      <div class="flex items-center">
        {{ announcement.title }}
        <BaseButton
          v-if="securityStore.isAdmin"
          :label="$t('Edit')"
          :to-url="`/main/admin/system_announcements.php?id=${announcement['id']}&action=edit`"
          class="ml-auto"
          icon="edit"
          only-icon
          size="small"
          type="secondary-text"
        />
      </div>
    </template>

    <div v-html="announcement.content" />
  </BaseCard>
</template>

<script setup>
import BaseButton from "../basecomponents/BaseButton.vue"
import BaseCard from "../basecomponents/BaseCard.vue"
import { useSecurityStore } from "../../store/securityStore"

const securityStore = useSecurityStore()

defineProps({
  announcement: {
    type: Object,
    required: true,
  },
})
</script>
