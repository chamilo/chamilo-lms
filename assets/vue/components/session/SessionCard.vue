<template>
  <div class="text-h6 mt-4">{{ session.name }}</div>
  <div class="grid gap-4 grid-cols-1 md:grid-cols-2 lg:grid-cols-4">
      <div
          v-for="edge in session.sessionRelCourseRelUsers.edges"
          :key="edge.node.course.id"
          no-body
          style="max-width: 540px;"
      >
        <CourseCard
            :course="edge.node.course"
        />
      </div>
  </div>
</template>
<style scoped>
.my-card {
  width: 100%;
  max-width: 370px;
}
</style>
<script>

import CourseCard from '../course/CourseCard.vue';

export default {
  name: 'SessionCard',
  components: {
    CourseCard,
  },
  props: {
    session: Object,
  }
};
</script>
