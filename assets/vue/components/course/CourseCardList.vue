<template>
    <div
         v-for="card in courses"
         :key="card.course.id"
    >
      <CourseCard
          :course="card.course"
      />
    </div>
</template>

<script>

import CourseCard from './CourseCard.vue';
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
