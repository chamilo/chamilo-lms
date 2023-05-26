<template>
  <Button
    class="cursor-pointer"
    plain
    :label="label"
    :class="buttonClass"
    :outlined="primeOutlinedProperty"
    :text="primeTextProperty"
    :size="size"
    @click="$emit('click', $event)"
  >
    <BaseIcon class="text-inherit" :icon="icon" :size="size" />
    <span
      v-if="!onlyIcon && label"
      class="hidden md:block text-inherit"
    >
      {{ label }}
    </span>
  </Button>
</template>

<script setup>
import Button from "primevue/button";
import {chamiloIconToClass} from "./ChamiloIcons";
import BaseIcon from "./BaseIcon.vue";
import {computed} from "vue";

const props = defineProps({
  label: {
    type: String,
    default: "",
  },
  icon: {
    type: String,
    required: true,
    validator: (value) => {
      if (typeof (value) !== "string") {
        return false;
      }
      return Object.keys(chamiloIconToClass).includes(value);
    }
  },
  type: {
    type: String,
    required: true,
    validator: (value) => {
      if (typeof (value) !== "string") {
        return false;
      }
      return [
        "primary",
        "secondary",
        "black",
        "danger",
      ].includes(value);
    }
  },
  // associate this button to a popup through its identifier, this will make this button toggle the popup
  popupIdentifier: {
    type: String,
    default: ""
  },
  onlyIcon: {
    type: Boolean,
    default: false,
  },
  size: {
    type: String,
    default: "normal",
    validator: (value) => {
      if (typeof (value) !== "string") {
        return false
      }
      return [
        "normal",
        "small",
      ].includes(value);
    }
  },
});

defineEmits(["click"]);

const buttonClass = computed(() => {
  if (props.onlyIcon) {
    return "p-3";
  }
  let result =""
  switch (props.size) {
    case "normal":
      result += "py-2.5 px-4 ";
      break;
    case "small":
      result += "py-2 px-3.5 "
  }
  switch (props.type) {
    case "primary":
      result += "border-primary hover:bg-primary text-primary hover:text-white ";
      break;
    case "secondary":
      result += "bg-secondary hover:bg-secondary-gradient text-white ";
      break;
    case "danger":
      result += "border-error hover:bg-error text-error hover:text-white ";
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
