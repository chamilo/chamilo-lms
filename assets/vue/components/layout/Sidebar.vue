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
          {{ $t('Login') }}
        </b-nav-item>
        <b-nav-item to="/register">
          {{ $t('Register') }}
        </b-nav-item>
      </b-nav>

      <p
        v-if="isAuthenticated"
        class="d-sm-none px-3 py-2 mb-0 bg-dark text-light"
      >
        {{ currentUser.username }}
      </p>
      <b-nav
        v-if="isAuthenticated"
        class="d-sm-none bg-dark text-light"
      >
        <b-nav-item href="/main/messages/inbox.php">
          {{ $t('Inbox') }}
        </b-nav-item>
        <b-nav-item href="/account/home">
          {{ $t('Profile') }}
        </b-nav-item>
        <b-nav-item href="/logout">
          {{ $t('Logout') }}
        </b-nav-item>
      </b-nav>
    </template>

    <b-nav vertical>
      <b-nav-item :to="{ name: 'Index' }">
        {{ $t('Home') }}
      </b-nav-item>
    </b-nav>

    <template v-if="isAuthenticated">
      <b-nav vertical>
        <b-nav-item
          :to="{ name: 'MyCourses' }"
        >
          {{ $t('Courses') }}
        </b-nav-item>
        <b-nav-item
          :to="{ name: 'MySessions' }"
        >
          {{ $t('Sessions') }}
        </b-nav-item>
      </b-nav>
    </template>

    <template v-if="isAuthenticated && isAdmin">
      <b-nav vertical>
        <h4 class="pt-3 px-3 mb-0">
          {{ $t('Administration') }}
        </h4>
        <b-nav-item
          :to="'/main/admin/user_list.php'"
        >
          {{ $t('Users') }}
        </b-nav-item>
        <b-nav-item
          :to="'/main/admin/course_list.php'"
        >
          {{ $t('Courses') }}
        </b-nav-item>
        <b-nav-item
          :to="'/main/session/session_list.php'"
        >
          {{ $t('Sessions') }}
        </b-nav-item>
        <b-nav-item
          :to="'/main/admin/index.php'"
        >
          {{ $t('Administration') }}
        </b-nav-item>
      </b-nav>
    </template>
  </b-sidebar>
</template>

<script>
import { mapGetters } from 'vuex';

export default {
  computed: {
    ...mapGetters({
      'isAuthenticated': 'security/isAuthenticated',
      'currentUser': 'security/getUser',
      'isAdmin': 'security/isAdmin',
    }),
  },
};

</script>
