<template>
    <div v-if="sessions.length > 0">
      <h5 class="mb-2">
        <BaseIcon icon="folder-generic"/>
        {{ $t('No category') }}
      </h5>
      <SessionListCategoryWrapper :sessions="sessions"/>
    </div>
</template>

<script setup>
import SessionListCategoryWrapper from '../../components/session/SessionListCategoryWrapper'
import BaseIcon from "../basecomponents/BaseIcon.vue"

defineProps({
  sessions: {
    type: Array,
    required: true,
  },
})
</script>
