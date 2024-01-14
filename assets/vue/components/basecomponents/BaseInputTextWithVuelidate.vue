<template>
  <BaseInputText
    :id="id"
    :model-value="modelValue"
    :label="labelWithRequiredIfNeeded"
    :is-invalid="vuelidateProperty.$error"
    @update:model-value="emit('update:modelValue', $event)"
  >
    <template #errors>
      <p
        v-for="error in vuelidateProperty.$errors"
        :key="error.$uid"
        class="mt-1 text-error"
      >
        {{ error.$message }}
      </p>
    </template>
  </BaseInputText>
</template>

<script setup>
import BaseInputText from "./BaseInputText.vue"
import {computed} from "vue"

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
  vuelidateProperty: {
    type: Object,
    required: true,
  },
})

const emit = defineEmits(["update:modelValue"])

const labelWithRequiredIfNeeded = computed(() => {
  if (Object.hasOwn(props.vuelidateProperty, 'required')) {
    return `* ${props.label}`
  }
  return props.label
})
</script>
