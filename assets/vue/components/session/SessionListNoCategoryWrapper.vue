<template>
    <div v-if="sessions.length > 0">
      <SessionListCategoryWrapper :sessions="sessions"/>
    </div>
</template>

<script setup>
import SessionListCategoryWrapper from '../../components/session/SessionListCategoryWrapper'

defineProps({
  sessions: {
    type: Array,
    required: true,
  },
})
</script>
