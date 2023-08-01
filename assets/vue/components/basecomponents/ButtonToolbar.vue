<template>
  <Toolbar :class="toolbarClass">
    <template #start>
      <slot></slot>
    </template>
  </Toolbar>
</template>

<script setup>
import Toolbar from "primevue/toolbar";
import { computed } from "vue";

const props = defineProps({
  showTopBorder: {
    type: Boolean,
    default: false,
  },
});

const toolbarClass = computed(() => {
  if (props.showTopBorder) {
    return "pt-5 border-t border-b";
  }
  return "";
});
</script>

<style scoped></style>
