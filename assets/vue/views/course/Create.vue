<template>
  <div>
    <CourseForm ref="createForm" :values="item" :errors="violations" />
    <Loading :visible="isLoading" />

    <Toolbar :handle-submit="onSendForm" :handle-reset="resetForm"></Toolbar>
  </div>
</template>

<script>
import { mapActions } from 'vuex';
import { createHelpers } from 'vuex-map-fields';
import CourseForm from '../../components/course/Form.vue';
import Loading from '../../components/Loading.vue';
import Toolbar from '../../components/Toolbar.vue';
import CreateMixin from '../../mixins/CreateMixin';

const servicePrefix = 'Course';

const { mapFields } = createHelpers({
  getterType: 'course/getField',
  mutationType: 'course/updateField'
});

export default {
  name: 'CourseCreate',
  servicePrefix,
  mixins: [CreateMixin],
  components: {
    Loading,
    Toolbar,
    CourseForm
  },
  data() {
    return {
      item: {}
    };
  },
  computed: {
    ...mapFields(['error', 'isLoading', 'created', 'violations'])
  },
  methods: {
    ...mapActions('course', ['create', 'reset'])
  }
};
</script>
