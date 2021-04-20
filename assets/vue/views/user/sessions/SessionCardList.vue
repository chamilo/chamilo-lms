<template>
    <div
        v-for="card in sessions"
        :key="card.session.id"
    >
      <SessionCard
          :sessionRelUser="card"
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
