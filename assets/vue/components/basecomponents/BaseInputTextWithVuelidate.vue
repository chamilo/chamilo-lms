<template>
  <BaseInputText
    :id="inputId"
    :aria-describedby="vuelidateProperty.$error ? errorId : undefined"
    :aria-invalid="vuelidateProperty.$error ? 'true' : 'false'"
    :is-invalid="vuelidateProperty.$error"
    :label="labelWithRequiredIfNeeded"
    :model-value="modelValue"
    @update:model-value="emit('update:modelValue', $event)"
  >
    <template #errors>
      <div
        v-if="vuelidateProperty.$error"
        :id="errorId"
        class="mt-1 text-error"
        role="alert"
      >
        <p
          v-for="error in vuelidateProperty.$errors"
          :key="error.$uid"
        >
          {{ error.$message }}
        </p>
      </div>
    </template>
  </BaseInputText>
</template>

<script setup>
import BaseInputText from "./BaseInputText.vue"
import { computed, useId } from "vue"

const props = defineProps({
  id: {
    type: String,
    required: false,
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

const generatedId = useId()
const inputId = computed(() => props.id || generatedId)
const errorId = computed(() => `${inputId.value}-errors`)

const labelWithRequiredIfNeeded = computed(() => {
  if (Object.hasOwn(props.vuelidateProperty, "required")) {
    return `* ${props.label}`
  }
  return props.label
})
</script>
