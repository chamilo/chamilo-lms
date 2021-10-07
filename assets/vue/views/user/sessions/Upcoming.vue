<template>
  <!--  todo make a component-->
  <q-tabs align="left" dense inline-label no-caps>
    <q-route-tab :to="{name: 'MySessionsPast'}" label="Past" />
    <q-route-tab  :to="{name: 'MySessions'}" label="Current" />
    <q-route-tab  :to="{name: 'MySessionsUpcoming'}" label="Upcoming" />
  </q-tabs>

  <div v-if="sessions.length" class="grid">
<!--    {{ status }}-->
    <SessionCardList :sessions="sessions" />
  </div>

  <div v-else>
    <div class="bg-gradient-to-r from-gray-100 to-gray-50 flex flex-col rounded-md text-center p-2">
      <div class="p-10 text-center">
        <div>
          <v-icon
            icon="mdi-google-classroom"
            size="72px"
            class="font-extrabold text-transparent bg-clip-text bg-gradient-to-br from-ch-primary to-ch-primary-light"
          />
        </div>

        <div class="mt-2 font-bold">
          {{ $t("You don't have any session yet.") }}
        </div>
        <div>
          {{ $t('Go to "Explore" to find a topic of interest, or wait for someone to subscribe you.') }}
        </div>
      </div>
    </div>
  </div>

</template>

<script>
import SessionCardList from '../../../components/session/SessionCardList.vue';
import {computed, ref} from "vue";
import {useStore} from 'vuex';
import {useQuery, useResult} from '@vue/apollo-composable'
import {GET_SESSION_REL_USER} from "../../../graphql/queries/SessionRelUser.js";
import {DateTime} from "luxon";

export default {
  name: 'SessionListUpcoming',
  components: {
    SessionCardList,
  },
  setup() {
    const store = useStore();
    let user = computed(() => store.getters['security/getUser']);

    if (user.value) {
      let userId = user.value.id;

      let start = DateTime.local().toISO();

      const {result, loading} = useQuery(GET_SESSION_REL_USER, {
        user: "/api/users/" + userId,
        afterStartDate: start,
      });

     const sessions = useResult(result, [], ({sessionRelCourseRelUsers, sessionRelUsers})=> {
        let sessionList = [];
        sessionRelUsers.edges.map(({node}) => {
          const sessionExists = sessionList.findIndex(suSession => suSession._id === node.session._id) >= 0;
          if (!sessionExists) {
            sessionList.push(node.session);
          }

          return sessionExists ? null : node.session;
        });

       sessionRelCourseRelUsers.edges.map(({node}) => {
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
