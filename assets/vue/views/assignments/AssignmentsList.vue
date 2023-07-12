<template>
  <AssignmentsListForTeacher
    v-if="isUserTeacher"
  />
</template>
<script setup>
import AssignmentsListForTeacher from "../../components/assignments/AssignmentsListForTeacher.vue"
import { computed } from "vue"
import { useStore } from "vuex"

const store = useStore();

const isUserTeacher = computed(() => store.getters["security/isCurrentTeacher"]);
</script>
