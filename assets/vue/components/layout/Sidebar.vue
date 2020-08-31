<template>
  <b-list-group>
    <b-list-group-item :to="{ name: 'Index' }">
      Home
    </b-list-group-item>

    <b-list-group-item
      v-if="isAuthenticated"
      :to="{ name: 'MyCourses' }"
    >
      Courses
    </b-list-group-item>

    <b-list-group-item
      v-if="isAuthenticated"
      :to="{ name: 'MySessions' }"
    >
      Sessions
    </b-list-group-item>

    <b-list-group-item
      v-if="isAuthenticated"
    />

    <b-list-group-item
      v-if="isAuthenticated"
      :to="'/main/admin/user_list.php'"
    >
      Users
    </b-list-group-item>

    <b-list-group-item
      v-if="isAuthenticated"
      :to="'/main/admin/course_list.php'"
    >
      Courses
    </b-list-group-item>

    <b-list-group-item
      v-if="isAuthenticated"
      :to="'/main/session/session_list.php'"
    >
      Sessions
    </b-list-group-item>


    <b-list-group-item
      v-if="isAuthenticated"
      :to="'/main/admin/index.php'"
    >
      Settings
    </b-list-group-item>
  </b-list-group>
</template>

<script>
export default {
  computed: {
    isAuthenticated() {
      return this.$store.getters['security/isAuthenticated']
    },
  },
};

</script>
