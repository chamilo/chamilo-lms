<template>
  <CourseCard
    v-for="course in courses"
    :key="course.id"
    :course="course"
  />
</template>

<script setup>
import CourseCard from './CourseCard.vue';

// eslint-disable-next-line no-undef
defineProps({
  courses: {
    type: Array,
    default: () => [],
  },
});
</script>
