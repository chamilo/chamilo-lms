<template>
  <b-row no-gutters>
    <b-col md="2">
      <b-card-img
        src="/img/icons/64/course.png"
        alt="Image"
        img-left
        class="rounded-0"
      />
    </b-col>
    <b-col md="10">
      <b-card-body :title="course.title">
        <!--              <b-card-text>-->
        <!--                Course description-->
        <!--              </b-card-text>-->
        <b-button
          :href=" '/course/' + course.id + '/home'"
          variant="primary"
        >
          Go
        </b-button>
      </b-card-body>
    </b-col>
  </b-row>
</template>

<script>
export default {
  name: 'CourseCard',
  props: {
    course: Object,
  },
  data() {
    return {
    };
  },
  methods: {
  }
};
</script>
