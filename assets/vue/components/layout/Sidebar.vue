<template>
  <b-sidebar
    id="sidebar-1"
    title="Chamilo"
    backdrop
  >
    <template
      v-slot:footer
    >
      <b-nav
        v-if="!isAuthenticated"
        class="d-sm-none bg-dark text-light"
      >
        <b-nav-item to="/login">
          Login
        </b-nav-item>
        <b-nav-item to="/register">
          Register
        </b-nav-item>
      </b-nav>

      <p
        v-if="isAuthenticated"
        class="d-sm-none px-3 py-2 mb-0 bg-dark text-light"
      >
        {{ username }}
      </p>
      <b-nav
        v-if="isAuthenticated"
        class="d-sm-none bg-dark text-light"
      >
        <b-nav-item href="/main/messages/inbox.php">
          Inbox
        </b-nav-item>
        <b-nav-item href="/account/home">
          Profile
        </b-nav-item>
        <b-nav-item href="/logout">
          Logout
        </b-nav-item>
      </b-nav>
    </template>

    <b-nav vertical>
      <b-nav-item :to="{ name: 'Index' }">
        Home
      </b-nav-item>
      <b-nav-item
        v-if="isAuthenticated"
        :to="{ name: 'MyCourses' }"
      >
        Courses
      </b-nav-item>
      <b-nav-item
        v-if="isAuthenticated"
        :to="{ name: 'MySessions' }"
      >
        Sessions
      </b-nav-item>
      <b-nav-item
        v-if="isAuthenticated"
        :to="'/main/admin/user_list.php'"
      >
        Users
      </b-nav-item>
      <b-nav-item
        v-if="isAuthenticated"
        :to="'/main/admin/course_list.php'"
      >
        Courses
      </b-nav-item>
      <b-nav-item
        v-if="isAuthenticated"
        :to="'/main/session/session_list.php'"
      >
        Sessions
      </b-nav-item>
      <b-nav-item
        v-if="isAuthenticated"
        :to="'/main/admin/index.php'"
      >
        Settings
      </b-nav-item>
    </b-nav>
  </b-sidebar>
</template>

<script>
export default {
  computed: {
    isAuthenticated() {
      return this.$store.getters['security/isAuthenticated']
    },
    username() {
      return this.$store.getters['security/getUser'].username
    }
  },
};

</script>
