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
import {GET_SESSION_REL_USER} from "../../../graphql/queries/SessionRelUser.js";

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

      const {result, loading} = useQuery(GET_SESSION_REL_USER, {
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
