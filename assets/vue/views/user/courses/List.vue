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
      status: null,
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
      axios.get(ENTRYPOINT + 'users/' + user.id + '/courses.json').then(response => {
        this.status = '';
        this.courses = response.data;
      }).catch(function (error) {
        console.log(error);
      });
    }
  }
};
</script>
