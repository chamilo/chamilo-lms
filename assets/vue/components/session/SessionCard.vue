<template>
  <div class="text-h6 mt-4">{{ session.name }}</div>
  <div class="grid gap-4 grid-cols-1 md:grid-cols-2 lg:grid-cols-4">
      <div
          v-for="course in courses"
          no-body
          style="max-width: 540px;"
      >
        {{ node }}
        <CourseCard
            :course="course.node.course"
            :session-id="session._id"
        />
      </div>
  </div>
</template>
//:key="node.course.id"
<style scoped>
.my-card {
  width: 100%;
  max-width: 370px;
}
</style>
<script>

import CourseCard from '../course/CourseCard.vue';
import {computed, ref} from "vue";
import isEmpty from 'lodash/isEmpty';
import {useQuery, useResult} from "@vue/apollo-composable";
import {GET_SESSION_REL_USER} from "../../graphql/queries/SessionRelUser";
import {useStore} from 'vuex';

export default {
  name: 'SessionCard',
  components: {
    CourseCard,
  },
  props: {
    session: Object,
  },
  setup(props) {
    const store = useStore();

    console.log('card lis');
    console.log(props.session.users);
    const session = props.session;

    const courses = ref([]);

    let showAllCourses = false;

    console.log('session: ' + session._id);

    if (!isEmpty(session.users.edges)) {
      console.log('check relation')
      session.users.edges.forEach(({node}) => {
        // User is Session::SESSION_ADMIN
        if (4 === node.relationType) {
          showAllCourses = true;
          return;
        }
      });
    }

    if (showAllCourses) {
      courses.value = session.courses.edges;
      console.log('all');
      console.log(courses.value);
    } else {
      courses.value = session.sessionRelCourseRelUsers.edges;
      console.log('single');
      console.log(
          courses.value );
    }


    return {
      courses
    };
  },
};
</script>
