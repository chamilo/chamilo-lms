<template>
  <div class="mt-5 p-5">
    <b-container
      fluid
    >
      <b-row>
        <b-col cols="4" />
        <b-col cols="4">
          <form
            @submit="onSubmit"
          >
            <p class="h4 text-center mb-4">
              Sign in
            </p>
            <div class="grey-text">
              <b-form-input
                v-model="login"
                placeholder="Your login"
                icon="envelope"
                type="text"
                required
              />
              <b-form-input
                v-model="password"
                placeholder="Your password"
                icon="lock"
                type="password"
                required
              />
            </div>
            <div class="text-center">
              <b-button
                block
                type="submit"
                variant="primary"
              >
                Login
              </b-button>
            </div>
          </form>
        </b-col>
        <b-col cols="4" />
      </b-row>
    </b-container>
  </div>
</template>

<script>
    import { mapGetters } from 'vuex';
    import ErrorMessage from "../components/ErrorMessage";

    export default {
        name: "Login",
        components: {
            ErrorMessage,
        },
        data() {
            return {
                login: "",
                password: "",
            };
        },
        computed: {
            ...mapGetters({
                'isLoading': 'security/isLoading',
                'hasError': 'security/hasError',
                'error': 'security/error',
            }),
        },
        created() {
            let redirect = this.$route.query.redirect;
            if (this.$store.getters["security/isAuthenticated"]) {
                if (typeof redirect !== "undefined") {
                    this.$router.push({path: redirect});
                } else {
                    this.$router.push({path: "/home"});
                }
            }
        },
        methods: {
            onSubmit(evt) {
              evt.preventDefault()
              this.performLogin();
            },
            async performLogin() {
                let payload = {login: this.$data.login, password: this.$data.password},
                    redirect = this.$route.query.redirect;
                await this.$store.dispatch("security/login", payload);
                if (!this.$store.getters["security/hasError"]) {
                    if (typeof redirect !== "undefined") {
                        this.$router.push({path: redirect});
                    } else {
                        this.$router.push({path: "/home"});
                    }
                }
            }
        }
    }
</script>