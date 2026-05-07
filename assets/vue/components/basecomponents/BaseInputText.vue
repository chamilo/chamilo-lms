<template>
  <div class="field">
    <!-- Date/time types always show a browser-native placeholder, so a floating
         label would overlap it. Use a static label above the input instead. -->
    <template v-if="isDateType">
      <label
        :for="id"
        class="block text-sm font-medium text-gray-700 mb-1"
      >{{ label }}</label>
      <InputText
        :id="id"
        :aria-label="label"
        :disabled="disabled"
        :invalid="isInvalid"
        :model-value="modelValue"
        :required="required"
        type="text"
        v-bind="$attrs"
        @update:model-value="updateValue"
      />
    </template>
    <FloatLabel
      v-else
      variant="on"
    >
      <InputText
        :id="id"
        :aria-label="label"
        :disabled="disabled"
        :invalid="isInvalid"
        :model-value="modelValue"
        :required="required"
        type="text"
        v-bind="$attrs"
        @update:model-value="updateValue"
      />
      <label :for="id">
        {{ label }}
      </label>
    </FloatLabel>
    <small
      v-if="formSubmitted && isInvalid"
      class="p-error"
    >
      {{ errorText }}
    </small>
    <small
      v-if="helpText"
      class="form-text text-muted"
    >
      {{ helpText }}
    </small>
  </div>
</template>

<script setup>
import { useAttrs, computed } from "vue"
import FloatLabel from "primevue/floatlabel"
import InputText from "primevue/inputtext"

defineOptions({ inheritAttrs: false })

defineProps({
  id: {
    type: String,
    required: true,
    default: "",
  },
  label: {
    type: String,
    required: true,
    default: "",
  },
  modelValue: {
    type: [String, null],
    required: true,
  },
  errorText: {
    type: String,
    required: false,
    default: "",
  },
  isInvalid: {
    type: Boolean,
    default: false,
  },
  required: {
    type: Boolean,
    default: false,
  },
  helpText: {
    type: String,
    default: "",
  },
  formSubmitted: {
    type: Boolean,
    default: false,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
})

const attrs = useAttrs()
const isDateType = computed(() => ["date", "datetime-local", "time", "month", "week"].includes(attrs.type))

const emits = defineEmits(["update:modelValue"])
const updateValue = (value) => {
  emits("update:modelValue", value)
}
</script>
