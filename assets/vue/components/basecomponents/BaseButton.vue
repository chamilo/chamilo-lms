<template>
  <Button
    class="cursor-pointer"
    plain
    :label="label"
    :class="buttonClass"
    :outlined="primeOutlinedProperty"
    :text="primeTextProperty"
    :severity="primeSeverityProperty"
    @click="$emit('click', $event)"
  >
    <BaseIcon class="text-inherit" :icon="icon"/>
    <span
      v-if="!props.onlyIcon"
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
        return false
      }
      return ["primary", "secondary", "black"].includes(value);
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
  }
});

defineEmits(["click"]);

const buttonClass = computed(() => {
  if (props.onlyIcon) {
    return "p-3";
  }
  let result = "py-2.5 px-4 ";
  switch (props.type) {
    case "primary":
      result += "border-primary hover:bg-primary text-primary hover:text-white";
      break;
    case "secondary":
      result += "bg-secondary hover:bg-secondary-gradient text-white";
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
      return true;
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

// https://primevue.org/button/#severity primary and secondary modified by chamilo
const primeSeverityProperty = computed(() => {
  if (props.onlyIcon) {
    return "primary";
  }
  switch (props.type) {
    case "secondary":
      return "danger";
    default:
      return "primary";
  }
});
</script>
