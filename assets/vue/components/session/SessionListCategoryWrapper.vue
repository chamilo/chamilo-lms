<template>
  <div v-if="sessions.length"
       class="grid gap-4 grid-cols-1 md:grid-cols-2 lg:grid-cols-4"
  >
    <div
        v-for="session in sessions"
        :key="session.id"
    >
      <SessionCardSimple
          :session="session"
      />
    </div>
  </div>
</template>

<script>
import SessionCardSimple from './SessionCardSimple.vue';

export default {
  name: 'SessionListCategoryWrapper',
  components: {
    SessionCardSimple
  },
  props: {
    sessions: Array,
  },
  setup() {
  }
}

</script>
