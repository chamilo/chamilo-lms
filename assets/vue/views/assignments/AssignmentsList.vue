<template>
  <TeacherAssignmentList v-if="isUserTeacher" />
</template>
<script setup>
import TeacherAssignmentList from "../../components/assignments/TeacherAssignmentList.vue"
import { computed } from "vue"
import { useStore } from "vuex"

const store = useStore()

const isUserTeacher = computed(() => store.getters["security/isCurrentTeacher"])
</script>