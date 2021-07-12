<template>
  <router-view></router-view>
</template>

<script>
import {mapActions, mapGetters, useStore} from 'vuex';
import Loading from '../../components/Loading.vue';
import Toolbar from '../../components/Toolbar.vue';
import {computed} from "vue";
import {useRoute, useRouter} from "vue-router";
const servicePrefix = 'PersonalFile';

export default {
  name: 'PersonalFileHome',
  servicePrefix,
  components: {
      Loading,
      Toolbar
  },
  setup () {
    const store = useStore();
    const currentUser = computed(() => store.getters['security/getUser']);
    const route = useRoute();
    const router = useRouter();

    router
        .push({name: `PersonalFileList`, params: {node: currentUser.value.resourceNode['id']}})
        .catch(() => {});
  },
  computed: {
    // From crud.js list function
    ...mapGetters('resourcenode', {
      resourceNode: 'getResourceNode'
    }),
    ...mapGetters({
      'isAuthenticated': 'security/isAuthenticated',
      'isAdmin': 'security/isAdmin',
      'currentUser': 'security/getUser',
    }),
  }

};
</script>
