<template>
  <div class="color-picker">
    <label
      v-if="label"
      v-text="label"
    />
    <InputGroup>
      <input
        :value="hexColor"
        type="color"
        @input="inputColorPicked($event.target.value)"
      />
      <InputText
        :invalid="inputHexError !== ''"
        :model-value="inputText"
        @update:model-value="inputColorPickedFromInputText"
      />
    </InputGroup>
    <small
      v-if="error"
      class="p-error"
    >
      {{ error }}
    </small>
  </div>
</template>

<script setup>
import Color from "colorjs.io"
import { computed, onMounted, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import InputGroup from "primevue/inputgroup"
import InputText from "primevue/inputtext"

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

watch(
  () => props.modelValue,
  () => {
    inputText.value = hexColor.value
  },
)

onMounted(() => {
  inputText.value = hexColor.value
})
</script>
