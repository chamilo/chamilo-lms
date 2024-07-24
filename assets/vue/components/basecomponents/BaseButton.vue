<template>
  <Button
    class="cursor-pointer"
    :aria-label="onlyIcon ? label : undefined"
    :class="buttonClass"
    :disabled="disabled"
    :icon="chamiloIconToClass[icon]"
    :label="onlyIcon ? undefined : label"
    :outlined="primeOutlinedProperty"
    :plain="primePlainProperty"
    :severity="primeSeverityProperty"
    :size="size"
    :text="onlyIcon"
    :title="tooltip || (onlyIcon ? label : undefined)"
    :type="isSubmit ? 'submit' : 'button'"
    :loading="isLoading"
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
    default: "",  // This ensures that popupIdentifier is still present
  },
})

defineEmits(["click"])

const primeSeverityProperty = computed(() => {
  if (["primary", "secondary", "success", "danger"].includes(props.type)) {
    return props.type
  }

  return undefined
})

const primePlainProperty = computed(() => {
  if ("black" === props.type) {
    return true
  }

  return undefined
})

const buttonClass = computed(() => {
  if (props.onlyIcon) {
    return "p-3 text-tertiary hover:bg-tertiary-gradient/30"
  }
  let result = ""
  switch (props.size) {
    case "normal":
      result += "py-2.5 px-4 "
      break
    case "small":
      result += "py-2 px-3.5 "
  }

  let commonDisabled =
    "disabled:bg-primary-bgdisabled disabled:border disabled:border-primary-borderdisabled disabled:text-fontdisabled disabled:pointer-events-auto disabled:cursor-not-allowed"
  switch (props.type) {
    case "primary":
      result += `bg-white border-primary text-primary-button-text hover:bg-primary hover:text-white ${commonDisabled} `
      break
    case "primary-alternative":
      result += `bg-primary text-primary-button-alternative-text hover:bg-primary-gradient ${commonDisabled} `
      break
    case "secondary":
      result += `bg-secondary text-secondary-button-text hover:bg-secondary-gradient disabled:bg-secondary-bgdisabled disabled:text-fontdisabled ${commonDisabled}`
      break
    case "success":
      result += `bg-success text-success-button-text hover:bg-success-gradient ${commonDisabled} `
      break
    case "info":
      result += `bg-info text-info-button-text hover:bg-info-gradient ${commonDisabled} `
      break
    case "warning":
      result += `bg-warning text-warning-button-text hover:bg-warning-gradient ${commonDisabled} `
      break
    case "danger":
      result += `bg-white border-error text-danger hover:bg-danger-gradient hover:text-white ${commonDisabled}`
      break
    case "black":
      result += `bg-white border-tertiary text-tertiary hover:bg-tertiary hover:text-white ${commonDisabled}`
      break
  }
  return result
})

// https://primevue.org/button/#outlined
const primeOutlinedProperty = computed(() => {
  if (props.onlyIcon) {
    return undefined
  }
  switch (props.type) {
    case "primary":
    case "danger":
    case "black":
      return true
    default:
      return false
  }
})
</script>
