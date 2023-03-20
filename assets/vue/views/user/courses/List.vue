<template>
  <StickyCourses />

  <div
    v-if="isLoading"
    class="grid gap-4 grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
  >
    <Skeleton
      height="16rem"
    />
    <Skeleton
      class="hidden md:block"
      height="16rem"
    />
    <Skeleton
      class="hidden lg:block"
      height="16rem"
    />
    <Skeleton
      class="hidden xl:block"
      height="16rem"
    />
  </div>

  <div
    v-if="!isLoading && courses.length > 0"
    class="grid gap-4 grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
  >
    <CourseCardList
      :courses="courses"
    />
  </div>
  <EmptyState
    v-else-if="!isLoading && 0 === courses.length"
    :detail="t('Go to Explore to find a topic of interest, or wait for someone to subscribe you')"
    :summary="t('You don\'t have any course yet')"
    icon="mdi mdi-book-open-page-variant"
  />
</template>

<script setup>
import { computed, ref } from 'vue';
import { useStore } from 'vuex';
import { useQuery } from '@vue/apollo-composable';
import { useI18n } from 'vue-i18n';
import { GET_COURSE_REL_USER } from '../../../graphql/queries/CourseRelUser.js';
import Skeleton from 'primevue/skeleton';
import StickyCourses from '../../../views/user/courses/StickyCourses.vue';
import CourseCardList from '../../../components/course/CourseCardList.vue';
import EmptyState from '../../../components/EmptyState';

const store = useStore();
const { t } = useI18n();

let user = computed(() => store.getters['security/getUser']);
let courses = ref([]);
let isLoading = ref(true);

if (user.value) {
  const { result, loading } = useQuery(
    GET_COURSE_REL_USER,
    {
      user: user.value['@id'],
    }
  );

  isLoading = computed(
    () => loading.value
  );

  courses = computed(
    () => result.value?.courseRelUsers.edges.map(({ node }) => node.course) ?? []
  );
}
</script>
