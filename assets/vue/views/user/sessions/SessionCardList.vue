<template>
  <span>
    <b-button-group>
      <b-button
        rounded
        :variant="isList()"
        @click="changeLayout"
      >
        <font-awesome-icon icon="bars" />
      </b-button>
      <b-button
        :variant="isDeck()"
        rounded
        @click="changeLayout"
      >
        <font-awesome-icon icon="th-large" />
      </b-button>
    </b-button-group>

    <b-card-group
      v-if="deck"
      columns
    >
      <b-card
        v-for="card in sessions"
        :key="card.session.id"
        no-body
        class="overflow-hidden"
        style="max-width: 540px;"
      >
        <SessionCard
          :session="card"
        />
      </b-card>
    </b-card-group>
    <span v-else>
      <b-card
        v-for="card in sessions"
        :key="card.session.id"
        no-body
        class="overflow-hidden"
        style="max-width: 540px;"
      >
        <SessionCard
          :sessionRelUser="card"
        />
      </b-card>
      <span />
    </span>
  </span>
</template>

<script>

import SessionCard from './SessionCard';
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
