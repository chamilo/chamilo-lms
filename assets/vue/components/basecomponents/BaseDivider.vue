<script setup>
import Divider from "primevue/divider"

defineProps({
  align: {
    type: String,
    required: false,
    default: null,
  },
  layout: {
    type: String,
    required: false,
    default: "horizontal",
  },
  title: {
    type: [String, undefined],
    required: false,
    default: null,
  },
})
</script>

<template>
  <Divider
    :align="align"
    :layout="layout"
    class="divider"
    unstyled
  >
    {{ title }}
  </Divider>
</template>
