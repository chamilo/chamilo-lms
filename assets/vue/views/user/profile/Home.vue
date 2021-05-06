<template>
  <div class="card">
    <h6>Hello {{ currentUser.username }}</h6>
    <q-tabs align="left" dense inline-label no-caps>
      <q-route-tab to="/courses" label="Inbox" />
      <q-route-tab to="/courses" label="Posts" />
      <q-route-tab to="/courses" label="Friends" />
      <q-route-tab to="/" label="Posts" />
      <q-route-tab to="/sessions" label="My files" />
    </q-tabs>
  </div>
</template>

<script>
import { useRoute } from 'vue-router'
import axios from "axios";
import { ENTRYPOINT } from '../../../config/entrypoint';
import { reactive, toRefs} from 'vue'
import {mapGetters} from "vuex";

export default {
  name: 'Home',
  components: {
  },
  setup() {
    const state = reactive({user: []});
    const route = useRoute()
    //let id = route.params.id;

    // axios.get(ENTRYPOINT + '../user/' + id + '.json').then(response => {
    //   state.user = response.data.user;
    // }).catch(function (error) {
    //   console.log(error);
    // });

    return toRefs(state);
  },
  computed: {
    ...mapGetters({
      'isAuthenticated': 'security/isAuthenticated',
      'currentUser': 'security/getUser',
    }),
  },
};
</script>
