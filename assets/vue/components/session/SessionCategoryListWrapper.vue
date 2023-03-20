<template>
  <div v-if="categories.length" class="grid">
    <div v-for="category in categories">
      <h5>
        <v-icon icon="mdi-folder" />
        {{ category.name }}
      </h5>
      <SessionListCategoryWrapper :sessions="getSessionsFromCategory(category)"/>
    </div>
  </div>
</template>
<script>

import SessionListCategoryWrapper from '../../components/session/SessionListCategoryWrapper';
import {toRefs} from "vue";

export default {
  name: 'SessionCategoryListWrapper',
  components: {
    SessionListCategoryWrapper
  },
  props: {
    categories: Array,
    categoryWithSessions: Array
  },
  setup(props) {
    const {categoryWithSessions} = toRefs(props);
    function getSessionsFromCategory(category) {
      return categoryWithSessions.value[category._id]['sessions'];
    }

    return {
      getSessionsFromCategory
    }
  }
}

</script>
