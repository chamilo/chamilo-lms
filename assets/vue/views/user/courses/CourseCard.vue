<template>
  <span>
    <b-card-group
      v-for="card in courses"
      :key="card.course.id"
      deck
      :cols="12"
    >
      <b-card
        no-body
        class="overflow-hidden"
        style="max-width: 540px;"
      >
        <b-row no-gutters>
          <b-col md="6">
            <b-card-img
              src="/img/icons/64/course.png"
              alt="Image"
              class="rounded-0"
            />
          </b-col>
          <b-col md="6">
            <b-card-body :title="card.course.title">
              <b-card-text>
                Course description
              </b-card-text>
              <b-button
                :href=" '/course/' + card.course.id + '/home'"
                variant="primary"
              >Go
              </b-button>
            </b-card-body>
          </b-col>
        </b-row>
      </b-card>
    </b-card-group>
  </span>
</template>

<script>
export default {
  name: 'CourseCard',
  props: {
    courses: Array
  },
};
</script>
