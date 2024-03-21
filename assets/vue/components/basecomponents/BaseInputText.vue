<template>
  <div class="field">
    <div class="p-float-label">
      <InputText
        :id="id"
        :model-value="modelValue"
        :class="{ 'p-invalid': isInvalid, [inputClass]: true }"
        :aria-label="label"
        type="text"
        @update:model-value="updateValue"
      />
      <label
        :for="id"
        :class="{ 'p-error': isInvalid }"
      >
        {{ label }}
      </label>
    </div>
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
import InputText from "primevue/inputtext"

const props = defineProps({
  id: {
    type: String,
    require: true,
    default: "",
  },
  label: {
    type: String,
    required: true,
    default: "",
  },
  modelValue: {
    type: String,
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
  inputClass: {
    type: String,
    default: "",
  },
})

const emits = defineEmits(["update:modelValue"])
const updateValue = (value) => {
  emits("update:modelValue", value)
}
</script>
