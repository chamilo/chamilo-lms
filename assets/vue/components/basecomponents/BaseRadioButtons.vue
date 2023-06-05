<template>
  <div class="flex flex-col">
    <div v-for="(option, index) in options" :key="option.value" class="flex items-center mr-2">
      <RadioButton
        :input-id="name + index"
        :model-value="value"
        :name="name"
        :value="option.value"
        @update:model-value="value = $event"
      />
      <label :for="name + index" class="ml-2 cursor-pointer">{{ option.label }}</label>
    </div>
  </div>
</template>

<script setup>
import RadioButton from 'primevue/radiobutton';
import {ref} from "vue";

const props = defineProps({
  modelValue: {
    type: String,
    required: true
  },
  name: {
    type: String,
    required: true,
  },
  // Array with {label: x, value: y} for every option you want to support
  options: {
    type: Array,
    required: true,
  },
  initialValue: {
    type: String,
    default: ''
  },
})

defineEmits(['update:modelValue'])

const value = ref(props.initialValue)
</script>
