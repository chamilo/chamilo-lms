<template>
  <div
    class="empty-state"
  >
    <div class="empty-state__container">
      <span
        aria-hidden="true"
        class="empty-state__icon"
        :class="icon"
      />
      <p
        class="empty-state__summary"
        v-text="summary"
      />
      <p
        class="empty-state__detail"
        v-text="detail"
      />
    </div>
  </div>
</template>

<script setup>
// eslint-disable-next-line no-undef
defineProps({
  summary: {
    type: String,
    default: '',
    required: true,
  },
  detail: {
    type: String,
    default: '',
    required: false,
  },
  icon: {
    type: String,
    default: '',
    required: false,
  },
});
</script>
