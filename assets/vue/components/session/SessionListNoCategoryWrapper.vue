<template>
    <div class="text-xl">
      <v-icon icon="mdi-folder"/>
      {{ $t('No category') }}
    </div>
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
