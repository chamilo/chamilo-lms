<template>
  <BaseCard plain>
    <template #header>
      <div class="-mb-2 flex items-center justify-between gap-2 bg-gray-15 px-4 py-2">
        <h6 v-text="announcement.title" />

        <BaseButton
          v-if="securityStore.isAdmin"
          icon="edit"
          label="Edit"
          type="black"
          @click="handleAnnouncementClick(announcement)"
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

function handleAnnouncementClick(announcement) {
  // until announcement is migrated to vue we need to use a browser action
  // when announcement is migrated we should use router.push here
  location.assign(`/main/admin/system_announcements.php?id=${announcement["id"]}&action=edit`)
}
</script>
