<template>
  <div v-if="courses.length" class="mb-6">
    <h2> {{ $t('Sticky courses')}}</h2>
    <div
        class="grid gap-4 grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 mt-2">
      <CourseCardList
          :courses="courses"
      />
    </div>
    <hr />
  </div>
</template>

<script>
import CourseCardList from '../../../components/course/CourseCardList.vue';
import {computed} from "vue";
import {useStore} from 'vuex';
import {useQuery, useResult} from '@vue/apollo-composable'
import {GET_STICKY_COURSES} from "../../../graphql/queries/Course";

export default {
  name: 'StickyCourses',
  components: {
    CourseCardList,
  },
  setup() {
    const store = useStore();
    let user = computed(() => store.getters['security/getUser']);

    if (user.value) {
      const {result: resultStickyCourses, loading: loadingCourses} = useQuery(GET_STICKY_COURSES);
      const courses = useResult(resultStickyCourses, [], (data) => {
        return data.courses.edges.map(function (edge) {
          return edge.node;
        });
      });

      return {
        courses,
        loadingCourses
      }
    }
  }
};
</script>
