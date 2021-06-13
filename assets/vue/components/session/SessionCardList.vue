<template>
    <div
        v-for="session in sessions"
        :key="session.id"
    >
      <SessionCard
          :session="session"
      />
    </div>
</template>

<script>

import SessionCard from './SessionCard.vue';
export default {
  name: 'SessionCardList',
  components: {
    SessionCard
  },
  props: {
    sessions: Array,
  },
  data() {
    return {
      deck: false
    };
  },
  methods: {
    isList: function (){
      if (!this.deck) {
        return 'primary';
      }
      return 'secondary';
    },
    isDeck: function (){
      if (this.deck) {
        return 'primary';
      }
      return 'secondary';
    },
    changeLayout: function () {
      this.deck = !this.deck;
    },
  }
};
</script>
