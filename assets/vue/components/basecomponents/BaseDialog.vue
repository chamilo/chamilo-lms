<template>
  <Dialog
    :header="title"
    :modal="true"
    :style="{ width: '450px' }"
    :visible="isVisible"
    class="p-fluid"
    @update:visible="$emit('update:isVisible', $event)"
  >
    <slot></slot>
    <template #footer>
      <slot name="footer"></slot>
    </template>
  </Dialog>
</template>

<script setup>
import Dialog from "primevue/dialog";

defineProps({
  title: {
    type: String,
    required: true,
  },
  isVisible: {
    type: Boolean,
    required: true,
  },
});

defineEmits(["update:isVisible"]);
</script>
