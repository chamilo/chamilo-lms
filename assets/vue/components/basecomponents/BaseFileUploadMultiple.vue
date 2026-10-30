<template>
  <div class="flex items-center gap-2">
    <BaseButton
      :label="label"
      :size="size"
      :type="type"
      icon="attachment"
      @click="showFileDialog"
    />
    <div v-if="files?.length > 0">
      <p
        v-for="file in files"
        :key="file.name"
        class="text-body-2 text-gray-90"
      >
        {{ file.name }}
      </p>
    </div>
    <input
      ref="inputFile"
      :accept="acceptFileType"
      :name="name"
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
import { buttonTypeValidator } from "./validators"

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
  name: {
    type: String,
    default: undefined,
  },
  type: {
    type: String,
    default: "primary",
    validator: buttonTypeValidator,
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
