<template>
  <v-card>

    <q-item>
      <q-item-section side>
        <q-avatar size="64px">
          <img :src="user.illustrationUrl + '?w=80&h=80&fit=crop'" />
        </q-avatar>
      </q-item-section>
      <q-item-section>
        <q-item-label>{{ user.fullName }}</q-item-label>
        <q-item-label caption>{{ user.username }}</q-item-label>
      </q-item-section>
    </q-item>

    <q-tabs align="left" dense inline-label no-caps>
      <q-route-tab to="/resources/friends" label="My friends" />
      <q-route-tab to="/resources/personal_files" label="My files" />
    </q-tabs>

    <a href="/account/edit" class="btn btn--primary">
      Edit profile
    </a>
  </v-card>
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
    const store = useStore();
    state.user = computed(() => store.getters['security/getUser']);
    state.isAuthenticated = computed(() => store.getters['security/isAuthenticated']);

    return toRefs(state);
  },
};
</script>
