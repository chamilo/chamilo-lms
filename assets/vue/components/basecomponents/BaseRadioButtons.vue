<template>
  <div
    :aria-labelledby="title ? titleId : undefined"
    class="flex flex-col"
    role="radiogroup"
  >
    <!-- Not a <label>: the title names the whole group, not one input. -->
    <span
      v-if="title"
      :id="titleId"
      class="mb-2"
    >
      {{ title }}
    </span>
    <div
      v-for="(option, index) in options"
      :key="option.value"
      class="flex items-center me-2"
    >
      <RadioButton
        v-model="value"
        :input-id="`${name}-${index}`"
        :name="name"
        :value="option.value"
      />
      <label
        :for="`${name}-${index}`"
        class="ms-2 cursor-pointer"
      >
        {{ option.label }}
      </label>
    </div>
  </div>
</template>

<script setup>
import RadioButton from "primevue/radiobutton"
import { computed, ref, watch } from "vue"

const props = defineProps({
  modelValue: {
    type: [String, Number],
    required: true,
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
    default: "",
  },
})
const emit = defineEmits(["update:modelValue"])
const titleId = computed(() => `${props.name}-title`)
const value = ref(props.modelValue)
watch(
  () => props.modelValue,
  (newValue) => {
    value.value = newValue
  },
)
watch(value, (newValue) => {
  emit("update:modelValue", newValue)
})
</script>
