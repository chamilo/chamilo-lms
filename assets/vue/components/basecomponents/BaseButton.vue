<template>
  <Button
    :class="buttonClass"
    :disabled="disabled"
    :label="label"
    :outlined="primeOutlinedProperty"
    :size="size"
    :text="primeTextProperty"
    :type="isSubmit ? 'submit': 'button'"
    class="cursor-pointer"
    plain
    @click="$emit('click', $event)"
  >
    <BaseIcon :icon="icon" :size="size" class="text-inherit" />
    <span v-if="!onlyIcon && label" class="hidden md:block text-inherit">
      {{ label }}
    </span>
  </Button>
</template>

<script setup>
import Button from "primevue/button";
import { chamiloIconToClass } from "./ChamiloIcons";
import BaseIcon from "./BaseIcon.vue";
import { computed } from "vue";
import {sizeValidator} from "./validators";

const props = defineProps({
  label: {
    type: String,
    default: "",
  },
  isSubmit: {
    type: Boolean,
    required: false,
    default: () => false,
  },
  icon: {
    type: String,
    required: true,
    validator: (value) => {
      if (typeof value !== "string") {
        return false;
      }
      return Object.keys(chamiloIconToClass).includes(value);
    },
  },
  type: {
    type: String,
    required: true,
    validator: (value) => {
      if (typeof value !== "string") {
        return false;
      }
      return ["primary", "secondary", "black", "success", "danger"].includes(value);
    },
  },
  // associate this button to a popup through its identifier, this will make this button toggle the popup
  popupIdentifier: {
    type: String,
    default: "",
  },
  onlyIcon: {
    type: Boolean,
    default: false,
  },
  size: {
    type: String,
    default: "normal",
    validator: sizeValidator,
  },
  disabled: {
    type: Boolean,
    required: false,
    default: false,
  }
});

defineEmits(["click"]);

const buttonClass = computed(() => {
  if (props.onlyIcon) {
    return "p-3";
  }
  let result = "";
  switch (props.size) {
    case "normal":
      result += "py-2.5 px-4 ";
      break;
    case "small":
      result += "py-2 px-3.5 ";
  }
  let commonDisabled =
    "disabled:bg-primary-bgdisabled disabled:border disabled:border-primary-borderdisabled disabled:text-fontdisabled";
  switch (props.type) {
    case "primary":
      result += `border-primary hover:bg-primary text-primary hover:text-white ${commonDisabled} `;
      break;
    case "secondary":
      result +=
        "bg-secondary text-white hover:bg-secondary-gradient disabled:bg-secondary-bgdisabled disabled:text-fontdisabled";
      break;
    case "success":
      result += `bg-success hover:bg-success-gradient ${commonDisabled} `;
      break;
    case "danger":
      result += `border-error hover:bg-error text-error hover:text-white ${commonDisabled} `;
      break;
    case "black":
      result += "bg-white text-black hover:bg-gray-90 hover:text-white ";
      break;
  }
  return result;
});

// https://primevue.org/button/#outlined
const primeOutlinedProperty = computed(() => {
  if (props.onlyIcon) {
    return false;
  }
  switch (props.type) {
    case "primary":
    case "danger":
    case "black":
      return true;
    default:
      return false;
  }
});

// https://primevue.org/button/#text
const primeTextProperty = computed(() => {
  return props.onlyIcon;
});
</script>
