<template>
  <i :class="iconClass" />
</template>

<script setup>
import { computed } from "vue";
import { chamiloIconToClass } from "./ChamiloIcons";

const props = defineProps({
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
  size: {
    type: String,
    default: "normal",
    validator: (value) => {
      if (typeof value !== "string") {
        return false;
      }
      return ["big", "normal", "small"].includes(value);
    },
  },
});

const iconClass = computed(() => {
  let iconClass = chamiloIconToClass[props.icon] + " ";
  switch (props.size) {
    case "big":
      iconClass += "text-3xl/4 ";
      break;
    case "normal":
      iconClass += "text-xl/4 ";
      break;
    case "small":
      iconClass += "text-base/4 ";
      break;
  }
  return iconClass;
});
</script>
