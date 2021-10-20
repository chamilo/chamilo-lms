<template>
  <SessionTabs/>

<!--  <SessionListWrapper :sessions="sessions"/>-->

  <SessionCategoryView :result-sessions="resultSessions"/>
</template>

<script>
import {computed, ref} from "vue";
import {useStore} from 'vuex';
import {useQuery, useResult} from '@vue/apollo-composable'
import {GET_SESSION_REL_USER} from "../../../graphql/queries/SessionRelUser.js";
import {DateTime} from 'luxon'
import SessionCategoryView from '../../../components/session/SessionCategoryView';
import SessionTabs from '../../../components/session/Tabs';

export default {
  name: 'SessionListPast',
  components: {
    SessionCategoryView,
    SessionTabs
  },
  setup() {
    const store = useStore();
    let user = computed(() => store.getters['security/getUser']);

    if (user.value) {
      let userId = user.value.id;
      let start = DateTime.local().minus({days: 360}).toISO();
      let end = DateTime.local().toISO();

      const {result: resultSessions, loading} = useQuery(GET_SESSION_REL_USER, {
        user: "/api/users/" + userId,
        afterStartDate: start,
        beforeEndDate: end,
      });

      return {
        resultSessions,
        loading
      }
    }
  }
}

</script>
