<template>
  <div class="course-list">
    {{ status }}
    <CourseCardList
        :courses="courses"
    />
  </div>
</template>

<script>
import CourseCardList from './CourseCardList';
import ListMixin from '../../../mixins/ListMixin';
import {ENTRYPOINT} from '../../../config/entrypoint';
import axios from "axios";

export default {
  name: 'CourseList',
  servicePrefix: 'Course',
  components: {
    CourseCardList
  },
  mixins: [ListMixin],
  data() {
    return {
      status: '',
      courses: []
    };
  },
  created: function () {
    this.load();
  },
  methods: {
    load: function () {
      this.status = 'Loading';
      let user = this.$store.getters['security/getUser'];
      if (user) {
        axios.get(ENTRYPOINT + 'users/' + user.id + '/courses.json').then(response => {
          this.status = '';
          if (Array.isArray(response.data)) {
            this.courses = response.data;
          }
        }).catch(function (error) {
          console.log(error);
        });
      } else {
        this.status = '';
      }
    }
  }
};
</script>
