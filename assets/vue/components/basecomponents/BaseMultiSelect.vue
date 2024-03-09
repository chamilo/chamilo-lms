<template>
  <div class="field">
    <div class="p-float-label">
      <MultiSelect
        id="multiSelect"
        v-model="selectedValues"
        :options="options"
        optionLabel="name"
        optionValue="id"
        display="chip"
        @update:model-value="updateModelValue"
        @focus="isFocused = true"
        @blur="isFocused = false"
        panelClass="multi-select-panel"
      />
      <label :for="inputId" v-text="label" />
    </div>
      <small v-if="isInvalid" :class="{ 'p-error': isInvalid }" v-text="errorText" />
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import MultiSelect from 'primevue/multiselect'
const props = defineProps({
  modelValue: {
    type: Array,
    default: () => []
  },
  options: {
    type: Array,
    default: () => []
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
})
const emit = defineEmits(['update:modelValue'])
const selectedValues = ref([...props.modelValue])
const isFocused = ref(false)

watch(() => props.modelValue, (newValue) => {
  selectedValues.value = [...newValue]
})

const updateModelValue = (newValue) => {
  emit('update:modelValue', newValue)
}
</script>
