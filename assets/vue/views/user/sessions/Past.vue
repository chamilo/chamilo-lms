<template>
  <SessionTabs/>
  <SessionListWrapper :sessions="sessions"/>
</template>

<script>
import {computed, ref} from "vue";
import {useStore} from 'vuex';
import {useQuery, useResult} from '@vue/apollo-composable'
import {GET_SESSION_REL_USER} from "../../../graphql/queries/SessionRelUser.js";
import {DateTime} from 'luxon'
import SessionListWrapper from './SessionListWrapper';
import SessionTabs from './Tabs';

export default {
  name: 'SessionListPast',
  components: {
    SessionListWrapper,
    SessionTabs
  },
  setup() {
    console.log('past');

    const store = useStore();
    let user = computed(() => store.getters['security/getUser']);

    if (user.value) {
      let userId = user.value.id;
      let start = DateTime.local().minus({days: 360}).toISO();
      let end = DateTime.local().toISO();

      const {result, loading} = useQuery(GET_SESSION_REL_USER, {
        user: "/api/users/" + userId,
        afterStartDate: start,
        beforeEndDate: end,
      });

      const sessions = useResult(result, [], ({sessionRelUsers}) => {
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

      console.log(sessions);
      return {
        sessions,
        loading
      }
    }
  }
}

</script>
