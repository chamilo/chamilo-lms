<script setup>
import { useI18n } from "vue-i18n"
import BaseSelect from "../basecomponents/BaseSelect.vue"
import { introductionPositionOptions } from "../../utils/introductionPosition"

const { t } = useI18n()

const modelValue = defineModel({
  type: String,
  default: "above",
})

defineProps({
  disabled: {
    type: Boolean,
    default: false,
  },
  error: {
    type: String,
    default: "",
  },
})

const emit = defineEmits(["change"])
const options = introductionPositionOptions(t)

function onChange(event) {
  emit("change", event?.value ?? modelValue.value)
}
</script>

<template>
  <div class="mb-2 w-full max-w-xs ms-auto">
    <BaseSelect
      id="introduction-position"
      v-model="modelValue"
      :disabled="disabled"
      :is-invalid="Boolean(error)"
      :label="t('Introduction position')"
      :message-text="error"
      :options="options"
      name="introduction_position"
      option-label="label"
      option-value="value"
      @change="onChange"
    />
  </div>
</template>
