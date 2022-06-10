<template>
  <StickyCourses/>
  <!--  {{ loading }}-->
  <div
      v-if="courses.length"
      class="grid gap-4 grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 mt-2">
    <CourseCardList
        :courses="courses"
    />
  </div>
  <div v-else>
    <div class="bg-gradient-to-r from-gray-100 to-gray-50 flex flex-col rounded-md text-center p-2">
      <div class="p-10 text-center">
        <div>
          <v-icon
              icon="mdi-book-open-page-variant"
              size="72px"
              class="font-extrabold text-transparent bg-clip-text bg-gradient-to-br from-primary to-primary-gradient"
          />
        </div>

        <div class="mt-2 font-bold">
          {{ $t("You don't have any course yet.") }}
        </div>
        <div>
          {{ $t('Go to "Explore" to find a topic of interest, or wait for someone to subscribe you.') }}
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import CourseCardList from '../../../components/course/CourseCardList.vue';
import {computed} from "vue";
import {useStore} from 'vuex';
import {useQuery, useResult} from '@vue/apollo-composable'
import {GET_COURSE_REL_USER} from "../../../graphql/queries/CourseRelUser.js";
import StickyCourses from '../../../views/user/courses/StickyCourses.vue';

export default {
  name: 'CourseList',
  components: {
    StickyCourses,
    CourseCardList,
  },
  setup() {
    const store = useStore();
    let user = computed(() => store.getters['security/getUser']);

    if (user.value) {
      let userId = user.value.id;

      const {result, loading, error} = useQuery(GET_COURSE_REL_USER, {
        user: "/api/users/" + userId
      });

      const courses = useResult(result, [], (data) => {
        return data.courseRelUsers.edges.map(function (edge) {
          return edge.node.course;
        });
      });

      return {
        courses,
        loading
      }
    }
  }
};
</script>
