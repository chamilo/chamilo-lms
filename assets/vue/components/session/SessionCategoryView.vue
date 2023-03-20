<template>
  <SessionListNoCategoryWrapper :sessions="sessionList"/>
  <SessionCategoryListWrapper :categories="categories" :categoryWithSessions="categoryWithSessions"/>
</template>
<script>

import SessionCategoryListWrapper from '../../components/session/SessionCategoryListWrapper';
import SessionListNoCategoryWrapper from "../../components/session/SessionListNoCategoryWrapper";

import isEmpty from "lodash/isEmpty";
import {computed, ref, toRefs} from "vue";
import {useResult} from "@vue/apollo-composable";

export default {
  name: 'SessionCategoryView',
  components: {
    SessionCategoryListWrapper,
    SessionListNoCategoryWrapper
  },
  props: {
    resultSessions: Array,
  },
  setup(props) {
    const {resultSessions} = toRefs(props);

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

    console.log('sessions');
    console.log(sessions);

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

    console.log(sessionList);

    return {
      sessionList,
      sessions,
      sessionsInCategory,
      categoryWithSessions,
      categories
    }
  }
}

</script>
