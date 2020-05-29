<template>
  <div class="course-list">
      {{ status }}
      <SessionCard :sessions="sessions"></SessionCard>
  </div>
</template>

<script>
import SessionCard from './SessionCard';
import ListMixin from '../../../mixins/ListMixin';
import { ENTRYPOINT } from '../../../config/entrypoint';
import axios from "axios";

export default {
  name: 'SessionList',
  servicePrefix: 'Course',
  mixins: [ListMixin],
  components: {
      SessionCard
  },
  data() {
    return {
      status: null,
        sessions:null
    };
  },
  created: function () {
    this.load();
  },
  methods: {
    load: function() {
        this.status = 'Loading';
        let user = this.$store.getters['security/getUser'];
        axios.get(ENTRYPOINT + 'users/' + user.id + '/session_course_subscriptions.json').then(response => {
            this.status = '';
            this.sessions = response.data;
        }).catch(function (error) {
            this.status = error;
        });
    }
  }
};
</script>
