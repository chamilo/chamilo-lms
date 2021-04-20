<template>
  <div class="grid gap-4 grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 mt-2">
      {{ status }}
      <CourseCardList
          :courses="courses"
      />
  </div>
</template>

<script>
import CourseCardList from './CourseCardList.vue';
import {ENTRYPOINT} from '../../../config/entrypoint';
import axios from "axios";

export default {
  name: 'CourseList',
  components: {
    CourseCardList,
  },
  data() {
    return {
      status: '',
      courses: [],
      layout: 'list',
      sortKey: null,
      sortOrder: null,
      sortField: null,
    };
  },
  created: function () {
    this.load();
  },
  mounted: function () {
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
    },
  }
};
</script>
