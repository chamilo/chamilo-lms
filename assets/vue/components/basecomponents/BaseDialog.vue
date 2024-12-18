<script setup>
import Dialog from "primevue/dialog"
import { iconValidator } from "./validators"
import BaseIcon from "./BaseIcon.vue"

const isVisible = defineModel("isVisible", {
  required: true,
  type: Boolean,
})

defineProps({
  title: {
    type: String,
    required: true,
  },
  headerIcon: {
    type: String,
    default: "",
    validator: (value) => {
      if (value === "") {
        return true
      }
      return iconValidator(value)
    },
  },
})
</script>

<template>
  <Dialog
    :modal="true"
    v-model:visible="isVisible"
    class="p-fluid"
  >
    <template #header>
      <div class="text-left">
        <BaseIcon
          v-if="headerIcon"
          :icon="headerIcon"
          class="mr-2"
        />
        <span class="font-semibold">{{ title }}</span>
      </div>
    </template>
    <slot></slot>
    <template #footer>
      <slot name="footer"></slot>
    </template>
  </Dialog>
</template>
