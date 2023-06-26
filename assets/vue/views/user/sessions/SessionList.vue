<template>
  <StickyCourses/>
  <SessionTabs class="mb-4"/>

  <!-- All sessions -->
  <!--  <SessionListWrapper :sessions="sessionList"/>-->

  <SessionCategoryView :result-sessions="resultSessions"/>
</template>

<script>
import {computed} from "vue";
import {useStore} from 'vuex';
import {useQuery} from '@vue/apollo-composable'
import {GET_SESSION_REL_USER_CURRENT} from "../../../graphql/queries/SessionRelUser.js";
import SessionTabs from '../../../components/session/SessionTabs.vue';
import StickyCourses from '../../../views/user/courses/StickyCourses.vue';
import SessionCategoryView from "../../../components/session/SessionCategoryView";

export default {
  name: 'SessionList',
  components: {
    SessionCategoryView,
    StickyCourses,
    SessionTabs,
  },
  setup() {
    const store = useStore();
    let user = computed(() => store.getters['security/getUser']);

    if (user.value) {
      let userId = user.value.id;

      const {result: resultSessions, loading} = useQuery(GET_SESSION_REL_USER_CURRENT, {
        user: "/api/users/" + userId,
      });

      return {
        resultSessions,
        loading
      }
    }
  }
}

</script>
