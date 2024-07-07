<template>
  <div class="friends-invitations">
    <BaseCard plain class="bg-white mt-4">
      <template #header>
        <div class="px-4 py-2 bg-gray-100 border-b border-gray-300">
          <h2 class="text-xl font-semibold">{{ title }}</h2>
        </div>
      </template>
      <hr class="my-4">
      <div v-if="invitations && invitations.length > 0" class="space-y-4">
        <div v-for="invitation in invitations" :key="invitation.id" class="flex items-center p-4 border rounded-lg shadow-sm bg-white">
          <img :src="invitation.itemPicture" class="w-16 h-16 rounded-full" alt="Item picture">
          <div class="ml-4 flex-grow">
            <h4 class="text-lg font-semibold"><a :href="'profile.php?u=' + invitation.itemId" class="text-blue-600 hover:underline">{{ invitation.itemName }}</a></h4>
            <p class="text-gray-600">{{ invitation.content }}</p>
            <span class="text-sm text-gray-500">{{ invitation.date }}</span>
          </div>
          <div class="flex space-x-2">
            <BaseButton
              v-if="invitation.canAccept"
              label="Accept"
              icon="check"
              type="success"
              @click="emitEvent('accept', invitation.itemId)"
              class="ml-2"
            />
            <BaseButton
              v-if="invitation.canDeny"
              label="Deny"
              icon="times"
              type="danger"
              @click="emitEvent('deny', invitation.itemId)"
              class="ml-2"
            />
          </div>
        </div>
      </div>
      <div v-else class="p-4 text-center text-gray-500">
        <p>{{ t("No invitations or records found") }}</p>
      </div>
    </BaseCard>
  </div>
</template>

<script setup>
import BaseCard from "../basecomponents/BaseCard.vue"
import BaseButton from "../basecomponents/BaseButton.vue"
import { useI18n } from "vue-i18n"

const { t } = useI18n()
const props = defineProps({
  invitations: Array,
  title: String
})
const emit = defineEmits(['accept', 'deny'])
function emitEvent(event, id) {
  emit(event, id)
}
</script>
