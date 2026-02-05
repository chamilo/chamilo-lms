<template>
  <div class="p-inputgroup">
    <InputText
      :id="id"
      :name="inputName"
      :placeholder="inputPlaceholder"
      type="text"
    />
    <BaseButton
      :icon="buttonIcon"
      :label="buttonLabel"
      :id="buttonId"
      is-submit
      type="primary"
      @click="$emit('button-click', $event)"
    />
  </div>
</template>

<script setup>
import InputText from "primevue/inputtext"
import BaseButton from "./BaseButton.vue"

defineProps({
  inputName: {
    type: String,
    required: true,
  },
  inputPlaceholder: {
    type: String,
    required: true,
  },
  buttonLabel: {
    type: String,
    required: true,
  },
  buttonIcon: {
    type: String,
    required: true,
  },
  id: {
    type: String,
    required: false,
    default: undefined,
  },
  buttonId: {
    type: String,
    required: false,
    default: undefined,
  },
})

defineEmits(["button-click"])
</script>
