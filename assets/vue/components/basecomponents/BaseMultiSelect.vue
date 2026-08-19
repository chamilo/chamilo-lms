<template>
  <div class="field">
    <FloatLabel variant="on">
      <MultiSelect
        v-model="selectedValues"
        :options="normalizedOptions"
        display="chip"
        fluid
        :filter="filter"
        :input-id="inputId"
        :option-label="optionLabel"
        :option-value="optionValue"
        @blur="isFocused = false"
        @focus="isFocused = true"
        @update:model-value="updateModelValue"
        :loading="isLoading"
      />
      <label
        :for="inputId"
        v-text="label"
      />
    </FloatLabel>
    <small
      v-if="isInvalid"
      :class="{ 'p-error': isInvalid }"
      v-text="errorText"
    />
  </div>
</template>

<script setup>
import { computed, ref, watch } from "vue"
import FloatLabel from "primevue/floatlabel"
import MultiSelect from "primevue/multiselect"

const props = defineProps({
  modelValue: {
    type: [Array, Object],
    default: () => [],
  },
  options: {
    type: [Array, Object],
    default: () => [],
  },
  placeholder: String,
  inputId: {
    type: String,
    required: true,
    default: "",
  },
  label: {
    type: String,
    required: true,
    default: "",
  },
  errorText: {
    type: String,
    required: false,
    default: null,
  },
  isInvalid: {
    type: Boolean,
    required: false,
    default: false,
  },
  isLoading: {
    type: Boolean,
    required: false,
    default: false,
  },
  optionLabel: {
    type: String,
    required: false,
    default: "name",
  },
  optionValue: {
    type: String,
    required: false,
    default: "id",
  },
  filter: {
    type: Boolean,
    required: false,
    default: false,
  },
})
const emit = defineEmits(["update:modelValue"])

function normalizeValues(value) {
  if (Array.isArray(value)) {
    return [...value]
  }

  if (value && typeof value === "object") {
    return Object.values(value)
  }

  return []
}

const normalizedOptions = computed(() => normalizeValues(props.options))
const selectedValues = ref(normalizeValues(props.modelValue))
const isFocused = ref(false)

watch(
  () => props.modelValue,
  (newValue) => {
    selectedValues.value = normalizeValues(newValue)
  },
)

const updateModelValue = (newValue) => {
  emit("update:modelValue", normalizeValues(newValue))
}
</script>
