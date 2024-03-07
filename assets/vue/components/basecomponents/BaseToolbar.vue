<template>
  <Toolbar :class="toolbarClass">
    <template v-slot:start>
      <slot name="start"></slot>
    </template>
    <template v-slot:end>
      <slot name="end"></slot>
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
  return "p-toolbar";
});
</script>

<style scoped>
.p-toolbar {
  padding-top: 0.5rem;
  padding-bottom: 0.5rem;
}
</style>
