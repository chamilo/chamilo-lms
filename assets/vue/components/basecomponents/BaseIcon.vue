<template>
  <span
    class="base-icon"
    :class="[size !== 'custom' && `base-icon--${size}`, { 'base-icon--has-tooltip': tooltip }]"
    v-bind="attrs"
  >
    <i
      :class="iconClass"
      aria-hidden="true"
    />

    <!-- Optional badge: supports text or a chamilo icon -->
    <span
      v-if="badge || badgeIcon"
      class="base-icon__badge"
      :class="[`base-icon__badge--${badgePosition}`, badgeClass]"
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
      class="base-icon__tooltip"
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
    default: "",
  },
  badgePosition: {
    type: String,
    default: "bottom-left",
    validator: (value) => ["top-left", "top-right", "bottom-left", "bottom-right"].includes(value),
  },
  tooltip: {
    type: String,
    default: "",
  },
})

const iconClass = computed(() => chamiloIconToClass[props.icon])

const badgeIconClass = computed(() => {
  if (!props.badgeIcon) {
    return ""
  }

  return chamiloIconToClass[props.badgeIcon] || ""
})
</script>
