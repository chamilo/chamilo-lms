<script setup>
import Dialog from "primevue/dialog"
import { iconValidator } from "./validators"
import BaseIcon from "./BaseIcon.vue"

defineProps({
  title: {
    type: String,
    required: true,
  },
  isVisible: {
    type: Boolean,
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

defineEmits(["update:isVisible"])
</script>

<template>
  <Dialog
    :modal="true"
    :visible="isVisible"
    class="p-fluid"
    @update:visible="$emit('update:isVisible', $event)"
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
