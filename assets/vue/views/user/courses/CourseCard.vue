<template>
<!--  <q-card class="my-card">-->
<!--    <q-img src="https://cdn.quasar.dev/img/parallax2.jpg">-->
<!--      <div class="absolute-bottom text-h6">-->
<!--        Title-->
<!--      </div>-->
<!--    </q-img>-->

<!--    <q-card-section>-->
<!--      {{ lorem }}-->
<!--    </q-card-section>-->
<!--  </q-card>-->

  <q-card class="my-card">
    <img src="/img/session_default.png" />
    <q-card-section>
      <div class="text-h7">
        <router-link :to="{ name: 'CourseHome', params: {id: course.id, course: course}}">
        {{ course.title }}
        </router-link>
      </div>

    </q-card-section>
    <q-card-section class="q-pt-none">
      {{ course.description }}
    </q-card-section>

<!--    <q-card-actions>-->
<!--        <q-btn-->
<!--            type="a"-->
<!--            :to="{ name: 'CourseHome', params: {id: course.id, course: course}}"-->
<!--            text-color="white"-->
<!--            color="primary"-->
<!--            label="Go"-->
<!--        />-->
<!--      </q-card-actions>-->
  </q-card>
</template>

<style scoped>
.my-card {
  width: 100%;
  max-width: 370px;
}
</style>
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
