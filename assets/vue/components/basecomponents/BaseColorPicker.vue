<template>
  <div class="flex flex-col justify-center gap-0">
    <p
      v-if="label"
      class="text-body-2 mb-1.5"
      v-text="label"
    />
    <div class="flex flex-row gap-2 h-10">
      <input
        :value="hexColor"
        class="h-12 w-12 cursor-pointer rounded-lg"
        type="color"
        @input="inputColorPicked($event.target.value)"
      />
      <BaseInputText
        label=""
        class="w-32"
        input-class="mb-0"
        :model-value="inputText"
        :error-text="inputHexError"
        :is-invalid="inputHexError !== ''"
        :form-submitted="inputHexError !== ''"
        @update:model-value="inputColorPickedFromInputText"
      />
    </div>
    <small
      v-if="error"
      class="text-danger h-4"
    >
      {{ error }}
    </small>
    <div
      v-else
      class="h-4"
    ></div>
  </div>
</template>

<script setup>
import Color from "colorjs.io"
import { computed, onMounted, ref } from "vue"
import { useI18n } from "vue-i18n"
import BaseInputText from "./BaseInputText.vue"

const { t } = useI18n()

const props = defineProps({
  // this should be a Color instance from colorjs library
  modelValue: {
    type: Object,
    required: true,
  },
  label: {
    type: String,
    default: "",
  },
  error: {
    type: String,
    default: "",
  },
})

const emit = defineEmits(["update:modelValue"])

const hexColor = computed(() => {
  let hex = props.modelValue.toString({ format: "hex" })
  // convert #fff color format to full #ffffff, because otherwise input does not set the color right
  if (hex.length === 4) {
    hex = "#" + hex[1] + hex[1] + hex[2] + hex[2] + hex[3] + hex[3]
  }
  return hex
})

const inputHexError = ref("")
const inputText = ref("")

function inputColorPickedFromInputText(newHexColor) {
  // preserve text input when user is editing the field by hand
  inputText.value = newHexColor
  inputColorPicked(newHexColor)
}

function inputColorPicked(newHexColor) {
  inputHexError.value = ""
  if (newHexColor.length !== 7) {
    inputHexError.value = t("Invalid format")
    return
  }
  inputText.value = newHexColor
  try {
    let color = new Color(newHexColor)
    emit("update:modelValue", color)
  } catch (error) {
    inputHexError.value = t("Invalid format")
  }
}

onMounted(() => {
  inputText.value = hexColor.value
})
</script>
