<template>
  <div class="flex items-center gap-2">
    <BaseButton
      :label="label"
      :size="size"
      icon="attachment"
      type="primary"
      @click="showFileDialog"
    />
    <div v-if="files.length > 0">
      <p
        v-for="file in files"
        :key="file.name"
        class="text-gray-500"
      >
        {{ file.name }}
      </p>
    </div>
    <input
      ref="inputFile"
      :accept="acceptFileType"
      class="hidden"
      multiple
      type="file"
      @change="filesSelected"
    />
  </div>
</template>

<script setup>
import BaseButton from "./BaseButton.vue"
import { computed, ref } from "vue"

const props = defineProps({
  modelValue: Array,
  label: String,
  accept: {
    type: String,
    default: "",
  },
  size: {
    type: String,
    default: "normal",
  },
})

const emit = defineEmits(["update:modelValue"])

const inputFile = ref(null)

const acceptFileType = computed(() => {
  if (props.accept === "image") {
    return "image/*"
  }
  return props.accept
})

const files = computed({
  get: () => props.modelValue,
  set: (newValue) => {
    emit("update:modelValue", newValue)
  },
})

const filesSelected = () => {
  files.value = Array.from(inputFile.value.files)
}

const showFileDialog = () => {
  inputFile.value.click()
}
</script>
