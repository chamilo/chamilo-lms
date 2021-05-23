<template>
    <div
         v-for="course in courses"
         :key="course.id"
    >
      <CourseCard
          :course="course"
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
