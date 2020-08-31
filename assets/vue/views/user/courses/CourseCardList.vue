<template>
  <span>

    <b-button
      variant="primary"
      rounded
      @click="changeLayout"
    >
      <font-awesome-icon icon="th-large" />
    </b-button>

    <b-button
      rounded
      @click="changeLayout"
    >
      <font-awesome-icon icon="bars" />
    </b-button>

    <b-card-group
      v-if="deck"
    >
      <b-card
        v-for="card in courses"
        :key="card.course.id"
        no-body
        class="overflow-hidden"
        style="max-width: 540px;"
      >
        <CourseCard
          :course="card.course"
        />
      </b-card>
    </b-card-group>

    <span v-else>
      <b-card
        v-for="card in courses"
        :key="card.course.id"
        no-body
        class="overflow-hidden"
        style="max-width: 540px;"
      >
        <CourseCard
          :course="card.course"
        />
      </b-card>
      <span />
    </span>
  </span>
</template>

<script>

import CourseCard from './CourseCard';
export default {
  name: 'CourseCardList',
  components: {
    CourseCard
  },
  props: {
    courses: Array,
  },
  data() {
    return {
      deck: true
    };
  },
  methods: {
    changeLayout: function () {
      this.deck = !this.deck;
    },
  }
};
</script>
