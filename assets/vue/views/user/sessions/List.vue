<template>
  <div class="grid">
<!--    {{ status }}-->
    <SessionCardList :sessions="sessions" />
  </div>
</template>

<script>
import SessionCardList from '../../../components/session/SessionCardList.vue';
import {ref, computed} from "vue";
import {useStore} from 'vuex';
import gql from "graphql-tag";
import {useQuery, useResult} from '@vue/apollo-composable'

export default {
  name: 'SessionList',
  components: {
    SessionCardList,
  },
  setup() {
    const loading = ref('Loading');
    const store = useStore();
    let user = computed(() => store.getters['security/getUser']);

    if (user.value) {
      let userId = user.value.id;

      /*axios.get(ENTRYPOINT + 'users/' + userId + '/sessions_rel_users.json').then(response => {
        if (Array.isArray(response.data)) {
          sessions.value = response.data;
        }
      }).catch(function (error) {
        status.value = error;
        console.log(error);
      }).finally(() =>
          status.value = ''
      );*/

      const GET_SESSION_REL_USER = gql`
          query getSessions($user: String!) {
            sessionRelUsers(user: $user) {
              edges {
                node {
                  session {
                    _id
                    name
                    displayStartDate
                    displayEndDate,
                    sessionRelCourseRelUsers(user: $user) {
                      edges {
                        node {
                          course {
                            _id
                            title
                            illustrationUrl
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

      const {result, loading, error} = useQuery(GET_SESSION_REL_USER, {
        user: "/api/users/" + userId
      }, );

      const sessions = useResult(result, null, (data) => {
        return data.sessionRelUsers.edges.map(function(edge) {
          return edge.node.session;
        });
      });

      return {
        sessions,
        loading
      }
    }
  }
}

</script>
