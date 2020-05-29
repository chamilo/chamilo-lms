<template>
  <div class="course-list">
      {{ status }}
      <CourseCard :courses="courses"></CourseCard>
  </div>
</template>

<script>
import CourseCard from './CourseCard';
import ListMixin from '../../../mixins/ListMixin';
import { ENTRYPOINT } from '../../../config/entrypoint';
import axios from "axios";

export default {
  name: 'CourseList',
  servicePrefix: 'Course',
  mixins: [ListMixin],
  components: {
    CourseCard
  },
  data() {
    return {
      status: null,
      courses:null
    };
  },
  created: function () {
    this.load();
  },
  methods: {
    load: function() {
      this.status = 'Loading';
      let user = this.$store.getters['security/getUser'];

      axios.get(ENTRYPOINT + 'users/'+ user.id +'/courses.json').then(response => {
        this.status = '';
        this.courses = response.data;
      }).catch(function(error) {
        this.status = error;
      });
    }

  }
};
</script>
