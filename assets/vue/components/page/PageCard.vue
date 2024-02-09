<template>
  <BaseCard plain>
    <template #header>
      <div class="-mb-2 flex items-center justify-between gap-2 bg-gray-15 px-4 py-2">
        <h6 v-text="page.title" />
        <BaseButton
          v-if="isAdmin"
          icon="edit"
          label="Edit"
          type="black"
          size="small"
          @click="handleClick(page)"
        />
      </div>
    </template>

    <div v-html="page.content" />
  </BaseCard>
</template>

<script setup>
import { useRouter } from "vue-router"
import { useSecurityStore } from "../../store/securityStore"
import { storeToRefs } from "pinia"
import BaseCard from "../basecomponents/BaseCard.vue"
import BaseButton from "../basecomponents/BaseButton.vue"

const router = useRouter()
const securityStore = useSecurityStore()
const { isAdmin } = storeToRefs(securityStore)

defineProps({
  page: {
    type: Object,
    required: true,
  },
})

const handleClick = (page) => {
  router.push({
    name: "PageUpdate",
    query: { id: page["@id"] },
  })
}
</script>
