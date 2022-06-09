<template>
    <h5>
      <v-icon icon="mdi-folder" />
      {{ $t('No category') }}
    </h5>
    <SessionListCategoryWrapper :sessions="sessions"/>
</template>
<script>

import SessionListCategoryWrapper from '../../components/session/SessionListCategoryWrapper';

export default {
  name: 'SessionListNoCategoryWrapper',
  components: {
    SessionListCategoryWrapper
  },
  props: {
    sessions: Array,
  },
  setup() {
  }
}

</script>
