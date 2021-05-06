<template>
  <div class="grid gap-4 grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 mt-2">
      {{ status }}
      <CourseCardList
          :courses="courses"
      />
  </div>
</template>

<script>
import CourseCardList from '../../../components/course/CourseCardList.vue';
import {ENTRYPOINT} from '../../../config/entrypoint';
import axios from "axios";
import {ref, computed} from "vue";
import { useStore } from 'vuex';

export default {
  name: 'CourseList',
  components: {
    CourseCardList,
  },
  setup() {
    const courses = ref([]);
    const status = ref('Loading');

    const store = useStore();
    let user = computed(() => store.getters['security/getUser']);

    if (user.value) {
      let userId = user.value.id;
      axios.get(ENTRYPOINT + 'users/' + userId + '/courses.json').then(response => {
        if (Array.isArray(response.data)) {
          courses.value = response.data;
        }
      }).catch(function (error) {
        console.log(error);
      }).finally(() =>
          status.value = ''
      );
    }

    return {
      courses,
      status
    }
  }
};
</script>
