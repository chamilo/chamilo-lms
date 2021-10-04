<template>
  <div v-if="visible" class="w-full h-full fixed block top-0 left-0 bg-white opacity-60 text-center"
       style="z-index: 9999;">
    <div class="spinner-border text-green-500 opacity-75 top-1/2 my-0 mx-auto block relative w-0 h-0" role="status">
      <span class="sr-only">Loading</span>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    visible: {
      type: Boolean,
      required: true
    }
  }
};
</script>
