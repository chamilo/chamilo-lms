<template>
  <div class="field flex flex-col gap-1">
    <label
      v-if="fieldLabel"
      :for="id"
      class="text-sm font-medium text-gray-700"
    >
      {{ fieldLabel }}
      <span
        v-if="showRequiredMarker"
        aria-hidden="true"
        class="text-red-500"
      >
        *
      </span>
    </label>

    <div class="flex items-center gap-2">
      <BaseButton
        :label="label"
        :size="size"
        icon="attachment"
        type="primary"
        @click="showFileDialog"
      />
      <p class="min-w-0 truncate text-gray-90">
        {{ fileName }}
      </p>
      <input
        :id="id"
        ref="inputFile"
        :accept="accept"
        :aria-required="required ? 'true' : undefined"
        class="hidden"
        :name="name"
        :required="required"
        type="file"
      />
    </div>

    <small
      v-if="helpText"
      class="form-text text-muted"
    >
      {{ helpText }}
    </small>

    <small
      v-if="isInvalid && errorText"
      class="p-error"
    >
      {{ errorText }}
    </small>
  </div>
</template>

<script setup>
import { onMounted, ref } from "vue"
import BaseButton from "./BaseButton.vue"
import { sizeValidator } from "./validators"

defineProps({
  id: {
    type: String,
    default: undefined,
  },
  label: {
    type: String,
    required: true,
  },
  fieldLabel: {
    type: String,
    default: "",
  },
  helpText: {
    type: String,
    default: "",
  },
  errorText: {
    type: String,
    default: "",
  },
  isInvalid: {
    type: Boolean,
    default: false,
  },
  required: {
    type: Boolean,
    default: false,
  },
  showRequiredMarker: {
    type: Boolean,
    default: false,
  },
  accept: {
    type: String,
    default: undefined,
  },
  name: {
    type: String,
    default: undefined,
  },
  size: {
    type: String,
    default: "normal",
    validator: sizeValidator,
  },
})

const emit = defineEmits(["fileSelected"])

const inputFile = ref(null)
const fileName = ref("")

onMounted(() => {
  inputFile.value.addEventListener("change", fileSelected)
})

const fileSelected = () => {
  const file = inputFile.value.files[0]

  if (!file) {
    return
  }

  fileName.value = file.name
  emit("fileSelected", file)
}

const showFileDialog = () => {
  inputFile.value.click()
}
</script>
