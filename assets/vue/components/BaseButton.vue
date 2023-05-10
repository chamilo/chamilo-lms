<template>
  <Button
    :label="label"
    :icon="primeIcon"
    :class="classes"
    :aria-controls="popupIdentifier"
    :aria-haspopup="areaHasPopup"
    class="p-button-plain cursor-pointer"
    style="cursor: pointer;"
    type="button"
    @click="$emit('click', $event)"
  />
</template>

<script setup>
import Button from "primevue/button";
import {computed} from "vue";
import {chamiloVuePrimeIconConversor} from "./ChamiloIcons";

const props = defineProps({
  label: {
    type: String,
    default: "",
  },
  // use syntax from default icons terminology
  // https://github.com/chamilo/chamilo-lms/wiki/Graphical-design-guide#default-icons-terminology
  icon: {
    type: String,
    required: true,
    validator: (value) => {
      if (typeof(value) !== "string") {
        return false
      }
      return Object.keys(chamiloVuePrimeIconConversor).includes(value)
    }
  },
  type: {
    type: String,
    required: true,
    validator: (value) => {
      if (typeof(value) !== "string") {
        return false
      }
      return ["text", "outlined"].includes(value);
    },
  },
  popupIdentifier: {
    type: String,
    default: ""
  },
});

defineEmits(['click']);

const classes = computed(() => {
  const buttonType = {
    "outlined": "p-button-outlined ",
    "text": "p-button-text",
  }
  return buttonType[props.type]
});

const primeIcon = computed(() => {
  return chamiloVuePrimeIconConversor[props.icon];
});

const areaHasPopup = computed(() => {
  return props.popupIdentifier === "" ? "false" : "true";
});
</script>
