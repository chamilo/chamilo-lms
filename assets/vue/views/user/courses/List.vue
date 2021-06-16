<template>
<!--  {{ loading }}-->
  <div v-if="courses.length" class="grid gap-4 grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 mt-2">
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
              class="font-extrabold text-transparent bg-clip-text bg-gradient-to-br from-blue-400 to-green-600"
          />

        </div>

        <div class="mt-2 font-bold">
          You don't have any courses yet.
        </div>
        <div>
          Lorem ipsum
        </div>
      </div>
    </div>

  </div>
</template>

<script>
import CourseCardList from '../../../components/course/CourseCardList.vue';
import {ref, computed} from "vue";
import {useStore} from 'vuex';
import gql from "graphql-tag";
import {useQuery, useResult} from '@vue/apollo-composable'

export default {
  name: 'CourseList',
  components: {
    CourseCardList,
  },
  setup() {
    const store = useStore();
    let user = computed(() => store.getters['security/getUser']);

    if (user.value) {
      let userId = user.value.id;

      const GET_COURSE_REL_USER = gql`
          query getCourses($user: String!) {
            courseRelUsers(user: $user) {
              edges {
                node {
                  course {
                    _id,
                    title,
                    illustrationUrl
                     users(status: 1, first: 4) {
                      edges {
                        node {
                          id
                          status
                          user {
                            illustrationUrl,
                            username,
                            firstname,
                            lastname
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

      const courses = useResult(result, [], (data) => {
        return data.courseRelUsers.edges.map(function(edge) {
          return edge.node.course;
        });
      });

      console.log(courses);

      return {
        courses,
        loading
      }
    }
  }
};
</script>
