<template>
  <StickyCourses/>
  <SessionTabs/>
  <SessionListWrapper :sessions="sessions"/>
</template>

<script>

import {computed, ref} from "vue";
import {useStore} from 'vuex';
import {useQuery, useResult} from '@vue/apollo-composable'
import {GET_SESSION_REL_USER} from "../../../graphql/queries/SessionRelUser.js";
import {DateTime} from "luxon";
import SessionTabs from '../../../components/session/Tabs';
import SessionListWrapper from '../../../components/session/SessionListWrapper';
import StickyCourses from '../../../views/user/courses/StickyCourses.vue';

export default {
  name: 'SessionList',
  components: {
    StickyCourses,
    SessionTabs,
    SessionListWrapper
  },
  setup() {
    const store = useStore();
    let user = computed(() => store.getters['security/getUser']);

    if (user.value) {
      let userId = user.value.id;
      let start = DateTime.local().toISO();
      let end = DateTime.local().toISO();

      const {result: resultSessions, loading: loadingSessions} = useQuery(GET_SESSION_REL_USER, {
        user: "/api/users/" + userId,
        beforeStartDate: start,
        afterEndDate: end,
      });

      const sessions = useResult(resultSessions, [], ({sessionRelUsers}) => {
        let sessionList = [];
        sessionRelUsers.edges.map(({node}) => {
          const sessionExists = sessionList.findIndex(suSession => suSession._id === node.session._id) >= 0;
          if (!sessionExists) {
            sessionList.push(node.session);
          }

          return sessionExists ? null : node.session;
        });

        return sessionList;
      });

      return {
        sessions,
        loadingSessions
      }
    }
  }
}

</script>
