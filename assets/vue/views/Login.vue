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
              {{ $t('Sign in') }}
            </p>
            <div class="grey-text">
              <b-form-input
                v-model="login"
                :placeholder="$t('Login')"
                icon="envelope"
                type="text"
                required
                name="login"
              />
              <b-form-input
                v-model="password"
                :placeholder=" $t('Password') "
                icon="lock"
                type="password"
                name="password"
                required
              />
            </div>

            <div class="text-center">
              <b-button
                block
                type="submit"
                variant="primary"
              >
                {{ $t('Login') }}
              </b-button>
            </div>

            <div
                v-if="isLoading"
                class="row col"
            >
              <p><font-awesome-icon icon="spinner" /></p>
            </div>

            <div
                v-else-if="hasError"
                class="row col"
            >
              <error-message :error="error" />
            </div>

            <a href="/main/auth/lostPassword.php" id="forgot">Forgot password?</a>
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
                    this.$router.push({path: "/courses"});
                }
            }
        },
        methods: {
            onSubmit(evt) {
              evt.preventDefault()
              this.performLogin();
            },
            async performLogin() {
                let payload = {login: this.$data.login, password: this.$data.password};
                let redirect = this.$route.query.redirect;
                await this.$store.dispatch("security/login", payload);
                if (!this.$store.getters["security/hasError"]) {
                    if (typeof redirect !== "undefined") {
                        this.$router.push({path: redirect});
                    } else {
                        this.$router.push({path: "/courses"});
                    }
                }
            }
        }
    }
</script>