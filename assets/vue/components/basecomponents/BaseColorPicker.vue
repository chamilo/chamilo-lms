<template>
  <div class="flex flex-col justify-center gap-0">
    <p v-if="label">{{ label }}</p>
    <div class="flex flex-row gap-3 mb-3">
      <input
        type="color"
        :value="hexColor"
        class="grow h-10 rounded-lg mb-0 cursor-pointer"
        @input="colorPicked($event.target.value)"
      />
      <BaseInputText
        label=""
        class="max-w-32 mb-0"
        input-class="h-10"
        :model-value="hexColor"
        :error-text="inputHexError"
        :is-invalid="inputHexError !== ''"
        :form-submitted="inputHexError !== ''"
        @update:model-value="colorPicked"
      />
    </div>
  </div>
</template>

<script setup>
import Color from "colorjs.io"
import { computed, ref } from "vue"
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
})

const emit = defineEmits(["update:modelValue"])

const hexColor = computed(() => {
  return props.modelValue.toString({ format: "hex" })
})

const inputHexError = ref("")

function colorPicked(newHexColor) {
  inputHexError.value = ""
  if (!newHexColor.startsWith("#")) {
    newHexColor = `#${newHexColor}`
  }
  if (newHexColor.length < 7) {
    inputHexError.value = t("Invalid format")
    return
  }

  try {
    let color = new Color(newHexColor)
    emit("update:modelValue", color)
  } catch (error) {
    inputHexError.value = t("Invalid format")
  }
}
</script>
