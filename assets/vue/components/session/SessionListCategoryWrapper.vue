<template>
  <div
    v-if="sessions.length > 0"
    class="grid gap-6 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4 items-stretch justify-items-start"
  >
    <SessionCardSimple
      v-for="session in sessions"
      :key="session.id"
      :session="session"
      layout="flat"
    />
  </div>
</template>

<script setup>
import SessionCardSimple from "./SessionCardSimple.vue"

defineProps({
  sessions: {
    type: Array,
    required: true,
  },
})
</script>
