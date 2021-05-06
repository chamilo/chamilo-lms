<template>
  <div class="text-h6 mt-4">{{ sessionRelUser.session.name }}</div>
  <div class="grid gap-4 grid-cols-1 md:grid-cols-2 lg:grid-cols-4">
      <div
          v-for="course in sessionRelUser.courses"
          :key="course.id"
          no-body
          style="max-width: 540px;"
      >
        <q-card class="my-card">
          <img src="/img/session_default.png" />
          <q-card-section>
            <div class="text-h7">
<!--                  <router-link :to="'/course/' + course.course.id + '/home' + '?sid=' + sessionRelUser.session.id">-->
              <a :href="'/course/' + course.course.id + '/home' + '?sid=' + sessionRelUser.session.id">
                {{ course.course.title }}
              </a>
            </div>
          </q-card-section>
        </q-card>
      </div>
  </div>
</template>
<style scoped>
.my-card {
  width: 100%;
  max-width: 370px;
}
</style>
<script>
export default {
  name: 'SessionCard',
  props: {
    sessionRelUser: Object,
  },
  data() {
    return {
    };
  },
  methods: {
  }
};
</script>
