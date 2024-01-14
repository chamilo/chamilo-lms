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
    <div class="my-2 flex flex-col gap-4">
      <div class="flex gap-2">
        <BaseIcon
          icon="alert"
          size="big"
        />
        <p>{{ t("Are you sure you want to delete this item?") }}</p>
      </div>
      <div class="mx-2">
        <slot>{{ itemToDelete }}</slot>
      </div>
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
    default: '',
  },
})

const emit = defineEmits(["update:isVisible", "confirmClicked", "cancelClicked"])
</script>
