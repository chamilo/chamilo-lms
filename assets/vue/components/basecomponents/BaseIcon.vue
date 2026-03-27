<template>
  <span
    class="relative inline-flex cursor-pointer"
    :class="{ group: tooltip }"
    v-bind="{ ...$attrs, class: undefined }"
  >
    <i
      :class="[iconClass, $attrs.class]"
      aria-hidden="true"
    />

    <!-- Optional badge: supports text or a chamilo icon -->
    <span
      v-if="badge || badgeIcon"
      class="absolute text-base font-bold leading-none"
      :class="[badgePositionClass, badgeClass]"
    >
      <i
        v-if="badgeIconClass"
        :class="badgeIconClass"
        aria-hidden="true"
      />
      <template v-else>{{ badge }}</template>
    </span>

    <!-- Optional tooltip shown below on hover -->
    <span
      v-if="tooltip"
      class="absolute top-full left-1/2 -translate-x-1/2 mt-1 px-2 py-1 text-xs text-white bg-gray-90 rounded whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-10"
    >
      {{ tooltip }}
    </span>
  </span>
</template>

<script setup>
import { computed, useAttrs } from "vue"
import { chamiloIconToClass } from "./ChamiloIcons"

defineOptions({ inheritAttrs: false })

const attrs = useAttrs()

const props = defineProps({
  icon: {
    type: String,
    required: true,
    validator: (value) => typeof value === "string" && Object.keys(chamiloIconToClass).includes(value),
  },
  size: {
    type: String,
    default: "normal",
    validator: (value) => ["big", "normal", "small", "custom"].includes(value),
  },
  title: {
    type: String,
    default: "",
  },
  badge: {
    type: String,
    default: "",
  },
  badgeIcon: {
    type: String,
    default: "",
    validator: (value) => value === "" || Object.keys(chamiloIconToClass).includes(value),
  },
  badgeClass: {
    type: String,
    default: "text-success text-base",
  },
  badgePosition: {
    type: String,
    default: "top-left",
    validator: (value) => ["top-left", "top-right", "bottom-left", "bottom-right"].includes(value),
  },
  tooltip: {
    type: String,
    default: "",
  },
})

const iconClass = computed(() => {
  let cls = chamiloIconToClass[props.icon] + " "
  switch (props.size) {
    case "big":
      cls += "text-3xl/4 "
      break
    case "normal":
      cls += "text-xl/4 "
      break
    case "small":
      cls += "text-base/4 "
      break
  }
  return cls
})

const badgeIconClass = computed(() => {
  if (!props.badgeIcon) return ""
  return chamiloIconToClass[props.badgeIcon] || ""
})

const badgePositionClass = computed(() => {
  const positions = {
    "top-left": "-top-2 -left-4",
    "top-right": "-top-2 -right-4",
    "bottom-left": "-bottom-2 -left-4",
    "bottom-right": "-bottom-2 -right-4",
  }
  return positions[props.badgePosition]
})
</script>
