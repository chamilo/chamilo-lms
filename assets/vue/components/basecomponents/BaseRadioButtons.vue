<template>
  <div class="flex flex-col">
    <label v-if="title" :for="name" class="mb-2">{{ title }}</label>
    <div v-for="(option, index) in options" :key="option.value" class="flex items-center mr-2">
      <RadioButton
        :input-id="`${name}-${index}`"
        v-model="value"
        :name="name"
        :value="option.value"
      />
      <label :for="`${name}-${index}`" class="ml-2 cursor-pointer">{{ option.label }}</label>
    </div>
  </div>
</template>

<script setup>
import RadioButton from 'primevue/radiobutton'
import { ref, watch } from 'vue'
const props = defineProps({
  modelValue: {
    type: [String, Number],
    required: true
  },
  name: {
    type: String,
    required: true,
  },
  title: String,
  options: {
    type: Array,
    required: true,
  },
  initialValue: {
    type: String,
    default: ''
  },
})
const emit = defineEmits(['update:modelValue'])
const value = ref(props.modelValue)
watch(() => props.modelValue, (newValue) => {
  value.value = newValue
})
watch(value, (newValue) => {
  emit('update:modelValue', newValue)
})
</script>
