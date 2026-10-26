<template>
  <ul
    class="divide-y divide-gray-25 rounded-lg border border-gray-25 text-sm text-gray-90"
    data-testid="learner-classes"
  >
    <li
      v-for="className in classes"
      :key="className"
      class="px-3 py-2"
    >
      {{ className }}
    </li>
  </ul>
</template>

<script setup>
defineProps({
  classes: { type: Array, required: true },
})
</script>
