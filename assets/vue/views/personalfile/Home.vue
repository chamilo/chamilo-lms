<template>
  <router-view></router-view>
</template>

<script>
import { mapActions, mapGetters } from 'vuex';
import Loading from '../../components/Loading.vue';
import ShowMixin from '../../mixins/ShowMixin';
import Toolbar from '../../components/Toolbar.vue';
import isEmpty from "lodash/isEmpty";
const servicePrefix = 'PersonalFile';

export default {
  name: 'PersonalFileHome',
  servicePrefix,
  components: {
      Loading,
      Toolbar
  },
  created() {
    console.log('CREATED HOME');
    let resourceNodeId = this.currentUser.resourceNode['id'];

    console.log(resourceNodeId);
    this.$router
        .push({ name: `${this.$options.servicePrefix}List`,    params: { node: resourceNodeId },})
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
