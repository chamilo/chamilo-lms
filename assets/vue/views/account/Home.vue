<template>
  <div class="card">
    <h6>{{ user.firstname }} {{ user.lastname }} </h6>
    <q-tabs align="left" dense inline-label no-caps>
      <q-route-tab to="/courses" label="Inbox" />
      <q-route-tab to="/courses" label="Posts" />
      <q-route-tab to="/courses" label="Friends" />
      <q-route-tab to="/" label="Posts" />
      <q-route-tab to="/resources/personal_files" label="My files" />
    </q-tabs>

    <a href="/account/edit" class="btn btn-primary">
      Edit profile
    </a>
  </div>
</template>

<script>
import { useRoute } from 'vue-router'
import axios from "axios";
import { ENTRYPOINT } from '../../config/entrypoint';
import {computed, reactive, ref, toRefs} from 'vue'
import {mapGetters, useStore} from "vuex";

export default {
  name: 'Home',
  components: {
  },
  setup() {
    const state = reactive({user: []});
    const route = useRoute()
    const store = useStore();
    state.user = computed(() => store.getters['security/getUser']);

    state.isAuthenticated = computed(() => store.getters['security/isAuthenticated']);
    //console.log('userAvatar ' + window.userAvatar);
    /*axios.get(ENTRYPOINT + '../user/' + user.id + '.json').then(response => {
      state.user = response.data.user;

      console.log(state.user);
    }).catch(function (error) {
      console.log(error);
    });*/

    return toRefs(state);
  },
};
</script>
