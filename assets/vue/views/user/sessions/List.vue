<template>
  <div class="grid">
    {{ status }}
    <SessionCardList :sessions="sessions"/>
  </div>
</template>

<script>
import SessionCardList from './SessionCardList.vue';
import { ENTRYPOINT } from '../../../config/entrypoint';
import axios from "axios";

export default {
  name: 'SessionList',
  components: {
      SessionCardList
  },
  data() {
    return {
        status: '',
        sessions: []
    };
  },
  created: function () {
    this.load();
  },
  methods: {
    load: function() {
        this.status = 'Loading';
        let user = this.$store.getters['security/getUser'];
        if (user) {
          axios.get(ENTRYPOINT + 'users/' + user.id + '/sessions_rel_users.json').then(response => {
            this.status = '';
            if (Array.isArray(response.data)) {
              this.sessions = response.data;
            }
          }).catch(function (error) {
            this.status = error;
          });
        } else {
          this.status = '';
        }
    }
  }
};
</script>
