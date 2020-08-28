<template>
  <b-navbar
    toggleable="lg"
    type="dark"
    variant="info"
    sticky
  >
    <b-navbar-brand href="/">
      Chamilo
    </b-navbar-brand>

    <b-navbar-toggle target="nav-collapse" />

    <b-collapse
      id="nav-collapse"
      is-nav
    >
      <b-navbar-nav>
        <!--        <b-nav-item href="#">-->
        <!--          Link-->
        <!--        </b-nav-item>-->
        <!--        <b-nav-item-->
        <!--          href="#"-->
        <!--          disabled-->
        <!--        >-->
        <!--          Disabled-->
        <!--        </b-nav-item>-->
      </b-navbar-nav>

      <!-- Right aligned nav items -->
      <b-navbar-nav class="ml-auto">
        <b-navbar-nav v-if="!isAuthenticated">
          <b-nav-item :to="'/login'">
            Login
          </b-nav-item>
          <b-nav-item :to="'/register'">
            Register
          </b-nav-item>
        </b-navbar-nav>

        <!--        <b-nav-form>-->
        <!--          <b-form-input-->
        <!--            size="sm"-->
        <!--            class="mr-sm-2"-->
        <!--            placeholder="Search"-->
        <!--          />-->
        <!--          <b-button-->
        <!--            size="sm"-->
        <!--            class="my-2 my-sm-0"-->
        <!--            type="submit"-->
        <!--          >-->
        <!--            Search-->
        <!--          </b-button>-->
        <!--        </b-nav-form>-->

        <!--        <b-nav-item-dropdown-->
        <!--          text="Lang"-->
        <!--          right-->
        <!--        >-->
        <!--          <b-dropdown-item href="#">-->
        <!--            EN-->
        <!--          </b-dropdown-item>-->
        <!--          <b-dropdown-item href="#">-->
        <!--            ES-->
        <!--          </b-dropdown-item>-->
        <!--          <b-dropdown-item href="#">-->
        <!--            RU-->
        <!--          </b-dropdown-item>-->
        <!--          <b-dropdown-item href="#">-->
        <!--            FA-->
        <!--          </b-dropdown-item>-->
        <!--        </b-nav-item-dropdown>-->

        <b-nav-item-dropdown
          v-if="isAuthenticated"
          right
        >
          <!-- Using 'button-content' slot -->
          <template v-slot:button-content>
            User
          </template>
          <b-dropdown-item href="/main/messages/inbox.php">
            Inbox
          </b-dropdown-item>
          <b-dropdown-item href="/account/home">
            Profile
          </b-dropdown-item>
          <b-dropdown-item href="/logout">
            Logout
          </b-dropdown-item>
        </b-nav-item-dropdown>
      </b-navbar-nav>
    </b-collapse>
  </b-navbar>
</template>
<script>
export default {
  components: {
  },
  props: {
    type: {
      type: String,
      default: 'default', // default|light
      description: 'Look of the dashboard navbar. Default (Green) or light (gray)'
    }
  },
  data() {
    return {
      activeNotifications: false,
      showMenu: false,
      searchModalVisible: false,
      searchQuery: ''
    };
  },
  computed: {
    isAuthenticated() {
      return this.$store.getters['security/isAuthenticated']
    },
  },
  methods: {
  }
};
</script>
