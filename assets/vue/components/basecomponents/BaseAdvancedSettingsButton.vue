<template>
  <div class="my-3">
    <button
      type="button"
      class="inline-flex items-center gap-2 text-sm font-semibold text-primary hover:text-primary/80"
      @click="$emit('update:modelValue', !modelValue)"
    >
      <span
        class="mdi"
        :class="modelValue ? 'mdi-chevron-down' : 'mdi-chevron-right'"
        aria-hidden="true"
      />
      <span>{{ showAdvancedSettingsLabel }}</span>
    </button>

    <div
      v-if="modelValue && hasDefaultSlot"
      class="mt-3 rounded-lg border border-gray-25 bg-gray-10 p-4"
    >
      <slot />
    </div>
  </div>
</template>

<script setup>
import { computed, useSlots } from "vue"
import { useI18n } from "vue-i18n"

const props = defineProps({
  modelValue: {
    type: Boolean,
    required: true,
  },
})

defineEmits(["update:modelValue"])

const { t } = useI18n()
const slots = useSlots()

const hasDefaultSlot = computed(() => !!slots.default)

const showAdvancedSettingsLabel = computed(() => {
  if (props.modelValue) {
    return t("Hide advanced settings")
  }

  return t("Advanced settings")
})
</script>
