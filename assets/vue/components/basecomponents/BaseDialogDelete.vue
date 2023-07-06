<template>
  <BaseDialogConfirmCancel
    :is-visible="isVisible"
    :title="t('Confirm')"
    confirm-icon="delete"
    confirm-type="danger"
    @confirm-clicked="emit('confirmClicked', $event)"
    @cancel-clicked="emit('cancelClicked', $event)"
    @update:is-visible="emit('update:isVisible', $event)"
  >
    <div class="confirmation-content">
      <BaseIcon
        class="mr-2"
        icon="alert"
        size="big"
      />
      {{ t("Are you sure you want to delete") }}: {{ itemToDelete }}
    </div>
  </BaseDialogConfirmCancel>
</template>

<script setup>
import BaseIcon from "./BaseIcon.vue"
import BaseDialogConfirmCancel from "./BaseDialogConfirmCancel.vue"
import { useI18n } from "vue-i18n"

const { t } = useI18n()

defineProps({
  isVisible: {
    type: Boolean,
    required: true,
  },
  itemToDelete: {
    type: String,
    required: true,
  },
})

const emit = defineEmits(["update:isVisible", "confirmClicked", "cancelClicked"])
</script>
