<template>

        <v-container
                class="fill-height"
                fluid
        >
            <v-row
                    align="center"
                    justify="center"
            >
                <v-col
                        cols="12"
                        sm="8"
                        md="4"
                >
                    <v-card class="elevation-12">
                        <v-toolbar
                                color="primary"
                                dark
                                flat
                        >
                            <v-toolbar-title>Login form</v-toolbar-title>
                        </v-toolbar>
                        <v-card-text>
                            <v-form>
                                <v-text-field

                                        v-model="login"
                                        label="Login"
                                        name="login"
                                        prepend-icon="mdi-account"
                                        type="text"
                                ></v-text-field>

                                <v-text-field

                                        v-model="password"
                                        id="password"
                                        label="Password"
                                        name="password"
                                        prepend-icon="mdi-key"
                                        type="password"
                                ></v-text-field>
                            </v-form>
                        </v-card-text>
                        <v-card-actions>
                            <div
                                    v-if="isLoading"
                                    class="row col"
                            >
                                <p>Loading...</p>
                            </div>

                            <div
                                    v-else-if="hasError"
                                    class="row col"
                            >
                                <error-message :error="error" />
                            </div>


                            <v-spacer></v-spacer>
                            <v-btn color="primary"
                                   :disabled="login.length === 0 || password.length === 0 || isLoading"
                                   @click="performLogin()"
                            >
                                Login
                            </v-btn>
                        </v-card-actions>
                    </v-card>
                </v-col>
            </v-row>
        </v-container>

</template>

<script>
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
            isLoading() {
                return this.$store.getters["security/isLoading"];
            },
            hasError() {
                return this.$store.getters["security/hasError"];
            },
            error() {
                return this.$store.getters["security/error"];
            }
        },
        created() {
            console.log('login CREATED');
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