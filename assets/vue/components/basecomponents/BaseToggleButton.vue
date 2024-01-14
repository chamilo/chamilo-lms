<template>
  <BaseButton
    v-if="modelValue"
    type="black"
    :label="onLabel"
    :icon="onIcon"
    :size="size"
    :class="customClass"
    @click="$emit('update:modelValue', false)"
  />
  <BaseButton
    v-else
    type="black"
    :label="offLabel"
    :icon="offIcon"
    :size="size"
    :class="customClass"
    @click="$emit('update:modelValue', true)"
  />
</template>

<script setup>
import {iconValidator, sizeValidator} from "./validators";
import BaseButton from "./BaseButton.vue";
import {computed} from "vue";

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
    validator: iconValidator,
  },
  offLabel: {
    type: String,
    required: true,
  },
  offIcon: {
    type: String,
    required: true,
    validator: iconValidator,
  },
  size: {
    type: String,
    default: "normal",
    validator: sizeValidator,
  },
  withoutBorders: {
    type: Boolean,
    default: false,
  },
});

defineEmits(["update:modelValue"]);

const customClass = computed(() => {
  if (props.withoutBorders) {
    if (props.modelValue) {
      return '!bg-primary/10 text-primary border-none hover:bg-primary/30 hover:text-primary/90 '
    } else {
      return 'bg-white text-black border-none hover:bg-primary/10 hover:text-primary/90 '
    }
  }
  return ''
})
</script>
