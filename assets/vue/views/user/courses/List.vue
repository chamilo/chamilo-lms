<template>
<!--  {{ loading }}-->
  <div class="grid gap-4 grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 mt-2">
      <CourseCardList
          :courses="courses"
      />
  </div>
</template>

<script>
import CourseCardList from '../../../components/course/CourseCardList.vue';
import {ENTRYPOINT} from '../../../config/entrypoint';
import axios from "axios";
import {ref, computed} from "vue";
import { useStore } from 'vuex';
import gql from "graphql-tag";
import { useQuery, useResult } from '@vue/apollo-composable'

export default {
  name: 'CourseList',
  components: {
    CourseCardList,
  },
  setup() {
    //const courses = ref([]);
    const status = ref('Loading');

    const store = useStore();
    let user = computed(() => store.getters['security/getUser']);

    if (user.value) {
      let userId = user.value.id;
      /*
      axios.get(ENTRYPOINT + 'users/' + userId + '/courses.json').then(response => {
        if (Array.isArray(response.data)) {
          courses.value = response.data;
        }
      }).catch(function (error) {
        console.log(error);
      }).finally(() =>
          status.value = ''
      );*/

      const GET_COURSE_REL_USER = gql`
          query getCourses($user: String!){
            courseRelUsers(user: $user) {
              edges {
                node {
                  course {
                    _id
                    title,
                     users(status: 1, first: 4) {
                      edges {
                        node {
                          id
                          status
                          user {
                            username
                          }
                        }
                      }
                    }
                  }
                }
              }
            }
        }
      `;

      const {result, loading, error} = useQuery(GET_COURSE_REL_USER, {
        user: "/api/users/" + userId
      }, );

      const courses = useResult(result, null, (data) => {
        return data.courseRelUsers.edges.map(function(edge) {
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
