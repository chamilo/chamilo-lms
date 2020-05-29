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
            <v-card-title v-text="card.course.title"></v-card-title>
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
