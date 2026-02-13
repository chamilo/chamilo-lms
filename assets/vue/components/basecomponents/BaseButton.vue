<template>
  <Button
    :aria-label="onlyIcon ? label : undefined"
    :disabled="disabled"
    :icon="chamiloIconToClass[icon]"
    :label="onlyIcon ? undefined : label"
    :loading="isLoading"
    :variant="primeOutlinedProperty"
    :severity="primeSeverityProperty"
    :size="size"
    :text="onlyIcon"
    :title="tooltip || (onlyIcon ? label : undefined)"
    :type="isSubmit ? 'submit' : 'button'"
    :name="name"
    :id="id"
    @click="$emit('click', $event)"
  />
</template>

<script setup>
import Button from "primevue/button"
import { computed } from "vue"
import { chamiloIconToClass } from "./ChamiloIcons"
import { buttonTypeValidator, iconValidator, sizeValidator } from "./validators"

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
    validator: iconValidator,
  },
  type: {
    type: String,
    required: true,
    validator: buttonTypeValidator,
  },
  tooltip: {
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
  },
  isLoading: {
    type: Boolean,
    default: false,
  },
  popupIdentifier: {
    type: String,
    default: "", // This ensures that popupIdentifier is still present
  },
  name: {
    type: String || undefined,
    required: false,
    default: undefined,
  },
  id: {
    type: String,
    required: false,
    default: undefined,
  },
  route: {
    type: Object,
    required: false,
    default: () => ({ name: "", params: {} }),
  },
})

defineEmits(["click"])

const primeSeverityProperty = computed(() => {
  if (["primary", "secondary", "success", "danger", "info"].includes(props.type)) {
    return props.type
  }

  if ("warning" === props.type) {
    return "warn"
  }

  if ("black" === props.type) {
    return "contrast"
  }

  if ("tertiary" === props.type) {
    return "help"
  }

  return undefined
})

const primeOutlinedProperty = computed(() => {
  if (props.onlyIcon) {
    return undefined
  }
  switch (props.type) {
    case "primary-alternative":
    case "black":
      return "outlined"
    default:
      return undefined
  }
})
</script>
