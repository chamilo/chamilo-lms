<template>
  <div class="flex gap-2 items-center">
    <BaseButton
      :label="label"
      :size="size"
      type="primary"
      icon="attachment"
      @click="showFileDialog"
    />
    <p class="text-gray-90">
      {{ fileName }}
    </p>
  </div>
  <input
    ref="inputFile"
    type="file"
    class="hidden"
    :accept="acceptFileType"
  >
</template>

<script setup>
import BaseButton from "./BaseButton.vue"
import {computed, onMounted, ref} from "vue";

const props = defineProps({
  modelValue: {
    type: File,
    required: true,
  },
  label: {
    type: String,
    required: true
  },
  accept: {
    type: String,
    default: '',
    validator: (value) => {
      if (value === '') { return true }
      return ['image'].includes(value);
    },
  },
  size: {
    type: String,
    default: "normal",
    validator: (value) => {
      if (typeof value !== "string") {
        return false;
      }
      return ["normal", "small"].includes(value);
    },
  },
})

const emit = defineEmits(['fileSelected'])

const inputFile = ref(null)
const fileName = ref('')

const acceptFileType = computed(() => {
  switch (props.accept) {
    case '':
      return ''
    case 'image':
      return 'image/*'
    default:
      return ''
  }
})

onMounted(() => {
  inputFile.value.addEventListener("change", fileSelected);
})

const fileSelected = () => {
  let file = inputFile.value.files[0];
  fileName.value = file.name
  emit('fileSelected', file)
}

const showFileDialog = () => {
  inputFile.value.click()
}
</script>
