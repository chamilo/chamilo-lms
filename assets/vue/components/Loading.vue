<template>
  <div class="text-center" v-if="visible">
    <div class="spinner-border " role="status">
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
