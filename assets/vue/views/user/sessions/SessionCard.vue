<template>
  <v-container grid-list-xl fluid>
    <v-layout row wrap>
      <v-row dense>
        <v-col
              v-for="card in sessions"
              :key="card.course.title"
              :cols="12"
        >
          <v-card>
            <div class="d-flex flex-no-wrap">
              <v-avatar
                      class="ma-3"
                      tile
              >
                <v-img src="/img/icons/48/blackboard_blue.png"></v-img>
              </v-avatar>
              <div >
                <v-card-title v-text="card.course.title">
                </v-card-title>
              </div>
            </div>
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
  name: 'SessionCard',
  props: {
    sessions: Array
  },
};
</script>
