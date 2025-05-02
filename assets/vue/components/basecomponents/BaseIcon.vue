<template>
  <i
    :class="iconClass"
    aria-hidden="true"
    @click="$emit('click', $event)"
    class="cursor-pointer"
    :title="title"
  />
</template>

<script setup>
import { computed } from "vue"
import { chamiloIconToClass } from "./ChamiloIcons"

const props = defineProps({
  icon: {
    type: String,
    required: true,
    validator: (value) => typeof value === "string" && Object.keys(chamiloIconToClass).includes(value),
  },
  size: {
    type: String,
    default: "normal",
    validator: (value) => ["big", "normal", "small"].includes(value),
  },
  title: {
    type: String,
    default: "",
  },
})

const iconClass = computed(() => {
  let iconClass = chamiloIconToClass[props.icon] + " "
  switch (props.size) {
    case "big":
      iconClass += "text-3xl/4 "
      break
    case "normal":
      iconClass += "text-xl/4 "
      break
    case "small":
      iconClass += "text-base/4 "
      break
  }
  return iconClass
})
</script>
