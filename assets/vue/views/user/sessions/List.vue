<template>
  <StickyCourses/>
  <SessionTabs/>
  <SessionListWrapper :sessions="sessionList"/>

  <div v-if="categories.length" class="grid">
    <div v-for="category in categories" >
      <div class="text-xl">
        <v-icon icon="mdi-folder" /> {{ category.name }} {{category._id}}
      </div>

      <SessionListCategoryWrapper :sessions="getSessionsFromCategory(category)" />

    </div>
  </div>
</template>

<script>

import {computed, ref} from "vue";
import {useStore} from 'vuex';
import {useQuery, useResult} from '@vue/apollo-composable'
import {GET_SESSION_REL_USER} from "../../../graphql/queries/SessionRelUser.js";
import {DateTime} from "luxon";
import SessionTabs from '../../../components/session/Tabs';
import SessionListWrapper from '../../../components/session/SessionListWrapper';
import SessionListCategoryWrapper from '../../../components/session/SessionListCategoryWrapper';
import StickyCourses from '../../../views/user/courses/StickyCourses.vue';

import isEmpty from "lodash/isEmpty";

export default {
  name: 'SessionList',
  components: {
    StickyCourses,
    SessionTabs,
    SessionListWrapper,
    SessionListCategoryWrapper,
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

      let sessions = useResult(resultSessions, [], ({sessionRelUsers}) => {
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

      let categories = useResult(resultSessions, [], ({sessionRelUsers}) => {
        let categoryList = [];
        sessionRelUsers.edges.map(({node}) => {
          if (isEmpty(node.session.category)) {
            return;
          }
          const categoryExists = categoryList.findIndex(cat => cat._id === node.session.category._id) >= 0;
          if (!categoryExists) {
            if (!isEmpty(node.session.category)) {
              categoryList.push(node.session.category);
            }
          }
        });

        return categoryList;
      });


      const sessionsInCategory = ref([]);

      let sessionList = computed(() => sessions.value.filter(function(session) {
        if (isEmpty(session.category)) {
          //session.category.sessions.push(session.category);
          return session;
        }
      }));

      let categoryWithSessions = computed(() => {
        let categoriesIn = [];
        sessions.value.forEach(function(session) {
          if (!isEmpty(session.category)) {
            if (categoriesIn[session.category._id] === undefined) {
              categoriesIn[session.category._id] = [];
              categoriesIn[session.category._id]['sessions'] = [];
            }
            categoriesIn[session.category._id]['sessions'].push(session);
          }
        });

        return categoriesIn;
      });

      function getSessionsFromCategory(category) {
          return categoryWithSessions.value[category._id]['sessions'];
      }

      return {
        getSessionsFromCategory,
        sessionList,
        sessions,
        sessionsInCategory,categoryWithSessions,
        categories,
        loadingSessions
      }
    }
  }
}

</script>
