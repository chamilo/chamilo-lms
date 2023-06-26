<template>
    <h5 class="mb-2">
      <BaseIcon icon="folder-generic"/>
      {{ $t('No category') }}
    </h5>
    <SessionListCategoryWrapper :sessions="sessions"/>
</template>
<script>

import SessionListCategoryWrapper from '../../components/session/SessionListCategoryWrapper';
import BaseIcon from "../basecomponents/BaseIcon.vue";

export default {
  name: 'SessionListNoCategoryWrapper',
  components: {
    BaseIcon,
    SessionListCategoryWrapper
  },
  props: {
    sessions: {
      type: Array,
      required: true,
    },
  },
  setup() {
  }
}

</script>
