<template>
  <div
    v-if="visible"
    class="absolute inset-0 flex items-center justify-center bg-white bg-opacity-50 backdrop-blur-md"
    style="z-index: 10"
  >
    <div class="loader" role="status">
      <span class="sr-only">Loading</span>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    visible: {
      type: Boolean,
      required: true,
    },
  },
}
</script>
