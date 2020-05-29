<template>
  <v-container grid-list-xl fluid>
    <v-layout row wrap>
      <v-row dense>
        <v-col
              v-for="card in courses"
              :key="card.course.id"
              :cols="12"
        >
          <v-card>
            <v-card-title v-text="card.course.title">
            </v-card-title>

            <v-card-actions>
              <v-btn
                      :href=" '/course/' + card.course.id + '/home'"
                      text
                      color="deep-purple accent-4"
              >
                Go
              </v-btn>
            </v-card-actions>

          </v-card>
        </v-col>
      </v-row>
    </v-layout>
  </v-container>
</template>

<script>
export default {
  name: 'CourseCard',
  props: {
    courses: Array
  },
};
</script>
