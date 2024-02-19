<template>
  <div class="friends-invitations">
    <BaseCard plain class="bg-white mt-4">
      <template #header>
        <div class="px-4 py-2 bg-gray-15">
          <h2 class="text-h5">{{ title }}</h2>
        </div>
      </template>
      <hr class="my-4">
      <div v-if="invitations && invitations.length > 0" class="invitation-list">
        <div v-for="invitation in invitations" :key="invitation.id" class="invitation-item">
          <div class="invitation-content">
            <img :src="invitation.itemPicture" class="item-picture" alt="Item picture">
            <div class="invitation-info">
              <h4><a :href="'profile.php?u=' + invitation.itemId">{{ invitation.itemName }}</a></h4>
              <p>{{ invitation.content }}</p>
              <span>{{ invitation.date }}</span>
            </div>
            <div class="invitation-actions">
              <BaseButton
                v-if="invitation.canAccept"
                label="Accept"
                icon="check"
                type="success"
                @click="emitEvent('accept', invitation.id)"
              />
              <BaseButton
                v-if="invitation.canDeny"
                label="Deny"
                icon="times"
                type="danger"
                @click="emitEvent('deny', invitation.id)"
              />
            </div>
          </div>
        </div>
      </div>
      <div v-else class="no-invitations-message">
        <p>{{ t("No invitations or records found") }}</p>
      </div>
    </BaseCard>
  </div>
</template>

<script setup>
import BaseCard from "../basecomponents/BaseCard.vue"
import BaseButton from "../basecomponents/BaseButton.vue"
import { useI18n } from "vue-i18n"
import { useFormatDate } from "../../composables/formatDate"

const { t } = useI18n()
const { relativeDatetime } = useFormatDate()
const props = defineProps({
  invitations: Array,
  title: String
})
const emit = defineEmits(['accept', 'deny'])
function emitEvent(event, id) {
  emit(event, id)
}
</script>
