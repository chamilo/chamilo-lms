<template>
  <Card class="course-card">
    <template #header>
      <router-link
        :to="{ name: 'CourseHome', params: {id: course._id}, query: { sid: sessionId } }"
        class="course-card__home-link"
      >
        <img
          :src="course.illustrationUrl"
          :alt="course.title"
        >
      </router-link>
    </template>
    <template #title>
      <router-link
        :to="{ name: 'CourseHome', params: {id: course._id}, query: { sid: sessionId } }"
        class="course-card__home-link"
      >
        <span v-if="session">
          {{ session.title }} -
        </span>
        {{ course.title }}
      </router-link>
    </template>
    <template #footer>
      <TeacherBar :teachers="teachers" />
    </template>
  </Card>
</template>

<script setup>
import Card from 'primevue/card';
import TeacherBar from '../TeacherBar';
import { computed } from "vue";

// eslint-disable-next-line no-undef
const props = defineProps(
    {
      course: Object,
      session: Object,
      sessionId: {
        type: Number,
        required: false,
        default: 0
      }
    }
);

const teachers = computed(() => {
  if (props.course.users && props.course.users.edges) {
    return props.course.users.edges.map(
      edge => ({
        id: edge.node.id,
        ...edge.node.user,
      })
    );
  }

  return [];
});
</script>
