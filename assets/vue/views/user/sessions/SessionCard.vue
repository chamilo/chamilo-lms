<template>
  <b-row no-gutters>
    <b-col md="2">
      <b-card-img
          src="/img/icons/64/session.png"
          alt="Image"
          class="mb-3"
          img-left
      />
    </b-col>
    <b-col md="10">
      <b-card-body :title="sessionRelUser.session.name">
        <b-card
            v-for="course in sessionRelUser.courses"
            :key="course.id"
            no-body
            class="overflow-hidden"
            style="max-width: 540px;"
        >
          <b-card-body :title="course.course.title">
            <!--              <b-card-text>-->
            <!--                Course description-->
            <!--              </b-card-text>-->
            <b-button
                :href=" '/course/' + course.course.id + '/home' + '?sid=' + sessionRelUser.session.id"
                variant="primary"
            >
              Go
            </b-button>
          </b-card-body>
        </b-card>
      </b-card-body>
    </b-col>
  </b-row>
</template>

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
