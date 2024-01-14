<template>
  <Button
    :class="buttonClass"
    :disabled="disabled"
    :icon="chamiloIconToClass[icon]"
    :label="label"
    :outlined="primeOutlinedProperty"
    :plain="primePlainProperty"
    :severity="primeSeverityProperty"
    :size="size"
    :text="primeTextProperty"
    :type="isSubmit ? 'submit' : 'button'"
    @click="$emit('click', $event)"
  />
</template>

<script setup>
import Button from "primevue/button"
import { computed } from "vue"
import { chamiloIconToClass } from "./ChamiloIcons";
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
    return "p-3"
  }
  let result = ""
  switch (props.size) {
    case "normal":
      result += "py-2.5 px-4 "
      break
    case "small":
      result += "py-2 px-3.5 "
  }

  return result
})

// https://primevue.org/button/#outlined
const primeOutlinedProperty = computed(() => {
  if (props.onlyIcon) {
    return false
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

// https://primevue.org/button/#text
const primeTextProperty = computed(() => {
  return props.onlyIcon
})
</script>
