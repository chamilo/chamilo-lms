<template>
  <div class="course-list">
    {{ status }}
    <SessionCardList :sessions="sessions"></SessionCardList>
  </div>
</template>

<script>
import SessionCardList from './SessionCardList';
import ListMixin from '../../../mixins/ListMixin';
import { ENTRYPOINT } from '../../../config/entrypoint';
import axios from "axios";

export default {
  name: 'SessionList',
  servicePrefix: 'Course',
  mixins: [ListMixin],
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
