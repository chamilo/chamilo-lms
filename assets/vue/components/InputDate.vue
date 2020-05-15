<template>
  <v-menu
    v-model="showMenu"
    :close-on-content-click="false"
    :nudge-right="40"
    transition="scale-transition"
    offset-y
    min-width="290px"
  >
    <template v-slot:activator="{ on }">
      <v-text-field
        v-model="date"
        :label="label"
        prepend-icon="mdi-calendar"
        readonly
        v-on="on"
      ></v-text-field>
    </template>
    <v-date-picker v-model="date" @input="handleInput"></v-date-picker>
  </v-menu>
</template>

<script>
import { formatDateTime } from '../utils/dates';

export default {
  props: {
    label: {
      type: String,
      required: false,
      default: () => ''
    },
    value: String
  },
  created() {
    this.date = this.value ? this.value : this.date;
  },
  data() {
    return {
      date: this.value ? this.value : new Date().toISOString().substr(0, 10),
      showMenu: false
    };
  },
  methods: {
    formatDateTime,
    handleInput() {
      this.showMenu = false;
      this.$emit('input', this.date);
    }
  }
};
</script>
