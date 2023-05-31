<template>
  <BaseDialog
    :title="title"
    :is-visible="isVisible"
    @update:is-visible="$emit('update:isVisible', $event)"
  >
    <slot></slot>
    <template #footer>
      <BaseButton
        :label="innerCancelLabel"
        type="black"
        icon="close"
        @click="$emit('cancelClicked')"
      />
      <BaseButton
        :label="innerConfirmLabel"
        type="secondary"
        icon="confirm"
        @click="$emit('confirmClicked')"
      />
    </template>
  </BaseDialog>
</template>

<script setup>
import BaseDialog from "./BaseDialog.vue";
import BaseButton from "./BaseButton.vue";
import {computed} from "vue";
import {useI18n} from "vue-i18n";

const {t} = useI18n()

const props = defineProps({
  title: {
    type: String,
    required: true,
  },
  isVisible: {
    type: Boolean,
    required: true,
  },
  confirmLabel: {
    type: String,
    default: '',
  },
  cancelLabel: {
    type: String,
    default: '',
  },
})

defineEmits(['update:isVisible', 'confirmClicked', 'cancelClicked'])

const innerConfirmLabel = computed(() => {
  return props.confirmLabel === "" ? t('Yes') : props.confirmLabel
})

const innerCancelLabel = computed(() => {
  return props.cancelLabel === "" ? t('No') : props.cancelLabel
})
</script>
