<template>
  <ToggleButton
    v-model="primeModelValue"
    :off-icon="chamiloIconToClass[offIcon]"
    :off-label="offLabel"
    :on-icon="chamiloIconToClass[onIcon]"
    :on-label="onLabel"
    @change="$emit('update:modelValue', primeModelValue)"
  />
</template>

<script setup>
import ToggleButton from "primevue/togglebutton";
import { ref } from "vue";
import { chamiloIconToClass, validator } from "./ChamiloIcons";

const props = defineProps({
  modelValue: {
    type: Boolean,
    required: true,
  },
  onLabel: {
    type: String,
    required: true,
  },
  onIcon: {
    type: String,
    required: true,
    validator,
  },
  offLabel: {
    type: String,
    required: true,
  },
  offIcon: {
    type: String,
    required: true,
    validator,
  },
});

defineEmits(["update:modelValue"]);

const primeModelValue = ref(props.modelValue);
</script>
