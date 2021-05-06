<template>
  <div class="grid">
    {{ status }}
    <SessionCardList :sessions="sessions" />
  </div>
</template>

<script>
import SessionCardList from '../../../components/session/SessionCardList.vue';
import { ENTRYPOINT } from '../../../config/entrypoint';
import axios from "axios";
import {computed, ref} from "vue";
import {useStore} from "vuex";

export default {
  name: 'SessionList',
  components: {
    SessionCardList,
  },
  setup() {
    const sessions = ref([]);
    const status = ref('Loading');

    const store = useStore();
    let user = computed(() => store.getters['security/getUser']);

    if (user.value) {
      let userId = user.value.id;
      axios.get(ENTRYPOINT + 'users/' + userId + '/sessions_rel_users.json').then(response => {
        if (Array.isArray(response.data)) {
          sessions.value = response.data;
        }
      }).catch(function (error) {
        status.value = error;
        console.log(error);
      }).finally(() =>
          status.value = ''
      );
    }

    return {
      sessions,
      status
    }
  }
}

</script>
