<template>
    <v-app id="inspire">
        <snackbar></snackbar>
        <v-navigation-drawer
                app
                dark
                v-model="drawer"
                :clipped="$vuetify.breakpoint.lgAndUp"
                disable-resize-watcher

        >
            <v-list v-if="isAuthenticated">
                <v-list-item :to="{ name: '/' }">
                    <v-list-item-action>
                        <v-icon>mdi-home</v-icon>
                    </v-list-item-action>
                    <v-list-item-content>
                        <v-list-item-title>
                            Home
                        </v-list-item-title>
                    </v-list-item-content>
                </v-list-item>

                <v-list-group
                        prepend-icon="mdi-plus"
                        value="true"
                >
                    <template v-slot:activator>
                        <v-list-item-title>Admin</v-list-item-title>
                    </template>

                    <v-list-item
                            :to="'/main/admin/user_list.php'">
                        <v-list-item-action>
                            <v-icon>mdi-home</v-icon>
                        </v-list-item-action>
                        <v-list-item-content>
                            <v-list-item-title>User list
                            </v-list-item-title>
                        </v-list-item-content>
                    </v-list-item>

                    <v-list-item
                            :to="'/main/admin/course_list.php'">
                        <v-list-item-action>
                            <v-icon>mdi-home</v-icon>
                        </v-list-item-action>
                        <v-list-item-content>
                            <v-list-item-title>Courses
                            </v-list-item-title>
                        </v-list-item-content>
                    </v-list-item>

                    <v-list-item
                            :to="'/main/session/session_list.php'">
                        <v-list-item-action>
                            <v-icon>mdi-home</v-icon>
                        </v-list-item-action>
                        <v-list-item-content>
                            <v-list-item-title>Sessions
                            </v-list-item-title>
                        </v-list-item-content>
                    </v-list-item>

                    <v-list-item
                            :to="'/main/admin/index.php'"
                    >
                        <v-list-item-action>
                            <v-icon>mdi-home</v-icon>
                        </v-list-item-action>
                        <v-list-item-content>
                            <v-list-item-title>Settings
                            </v-list-item-title>
                        </v-list-item-content>
                    </v-list-item>


                    <!--                        <v-list-group-->
                    <!--                                no-action-->
                    <!--                                sub-group-->
                    <!--                                value="true"-->
                    <!--                        >-->
                    <!--                            <template v-slot:activator>-->
                    <!--                                <v-list-item-content>-->
                    <!--                                    <v-list-item-title>Courses</v-list-item-title>-->
                    <!--                                </v-list-item-content>-->
                    <!--                            </template>-->

                    <!--                            <v-list-item-->
                    <!--                                    :to="{ name: admin[2] }"-->
                    <!--                                    v-for="(admin, i) in courses"-->
                    <!--                                    :key="i"-->
                    <!--                                    link-->
                    <!--                            >-->
                    <!--                                <v-list-item-title>-->
                    <!--                                    {{ admin[0] }}-->
                    <!--                                </v-list-item-title>-->
                    <!--                                <v-list-item-icon>-->
                    <!--                                    <v-icon v-text="admin[1]"></v-icon>-->
                    <!--                                </v-list-item-icon>-->
                    <!--                            </v-list-item>-->
                    <!--                        </v-list-group>-->


                </v-list-group>

                <!--                    <v-list-item-->
                <!--                            :to="{ name: 'DocumentsList' }"-->
                <!--                    >-->
                <!--                        <v-list-item-action >-->
                <!--                            <v-icon>mdi-comment-quote</v-icon>-->
                <!--                        </v-list-item-action>-->
                <!--                        <v-list-item-content >-->
                <!--                            <v-list-item-title  >-->
                <!--                                Documents-->
                <!--                            </v-list-item-title>-->
                <!--                        </v-list-item-content>-->
                <!--                    </v-list-item>-->

            </v-list>

            <v-list v-if="!isAuthenticated">
                <v-list-item :to="{ name: '/' }">
                    <v-list-item-action>
                        <v-icon>mdi-home</v-icon>
                    </v-list-item-action>
                    <v-list-item-content>
                        <v-list-item-title>
                            Home
                        </v-list-item-title>
                    </v-list-item-content>
                </v-list-item>
            </v-list>
        </v-navigation-drawer>

        <!--            <v-navigation-drawer-->
        <!--                    v-model="drawerRight"-->
        <!--                    app-->
        <!--                    clipped-->
        <!--                    right-->
        <!--            >-->
        <!--                <v-list dense>-->
        <!--                    <v-list-item @click.stop="right = !right">-->
        <!--                        <v-list-item-action>-->
        <!--                            <v-icon>mdi-exit-to-app</v-icon>-->
        <!--                        </v-list-item-action>-->
        <!--                        <v-list-item-content>-->
        <!--                            <v-list-item-title>Open Temporary Drawer</v-list-item-title>-->
        <!--                        </v-list-item-content>-->
        <!--                    </v-list-item>-->
        <!--                </v-list>-->
        <!--            </v-navigation-drawer>-->

        <!--            <v-app-bar app color="indigo" dark>-->
        <!--                <v-app-bar-nav-icon @click.stop="drawer = !drawer"></v-app-bar-nav-icon>-->
        <!--                <v-toolbar-title>Chamilo</v-toolbar-title>-->
        <!--            </v-app-bar>-->

        <v-app-bar
                :clipped-left="$vuetify.breakpoint.lgAndUp"
                app
                color="white"
        >
            <v-app-bar-nav-icon @click.stop="drawer = !drawer"></v-app-bar-nav-icon>
            <v-toolbar-title
                    style="width: 300px"
                    class="ml-0 pl-4"
            >
                <span class="hidden-sm-and-down">Chamilo</span>
            </v-toolbar-title>
            <v-spacer></v-spacer>

            <v-btn icon v-if="isAuthenticated">
                <v-icon>mdi-bell</v-icon>
            </v-btn>

            <v-menu
                    v-if="isAuthenticated"
                    offset-y
            >
                <template v-slot:activator="{ on }">
                    <v-btn icon v-on="on">
                        <v-avatar>
                            <v-icon dark>mdi-account-circle</v-icon>
                        </v-avatar>
                    </v-btn>
                </template>
                <v-list>
                    <v-list-item>
                        <v-list-item-title>Profile</v-list-item-title>
                    </v-list-item>
                    <v-list-item>
                        <v-list-item-title>
                            <a href="/logout">Logout</a>
                        </v-list-item-title>
                    </v-list-item>
                </v-list>
            </v-menu>

            <div
                    v-else
                    offset-y
            >
                <v-btn small color="primary" :to="'/login'">
                    <v-icon left>mdi-account</v-icon>
                    Login
                </v-btn>

                <v-btn small color="primary" :to="'/register'">
                    <v-icon left>mdi-pencil</v-icon>
                    Register
                </v-btn>
            </div>
        </v-app-bar>

        <v-content>
            <Breadcrumb layout-class="pl-3 py-3"/>

            <router-view></router-view>

            <div id="legacy_content" v-html="legacy_content">
            </div>
        </v-content>

        <v-footer color="indigo" app>
            <span class="white--text">&copy; 2019</span>
        </v-footer>
    </v-app>
</template>

<script>
    import Breadcrumb from './components/Breadcrumb';
    import Snackbar from './components/Snackbar';
    import axios from "axios";

    export default {
        name: "App",
        components: {
            Breadcrumb,
            Snackbar
        },
        data: () => ({
            drawer: true,
            courses: [
                ['Courses', 'mdi-book', 'CourseList'],
                ['Courses category', 'mdi-book', 'CourseCategoryList'],
            ],
            cruds: [
                ['Create', 'add'],
                ['Read', 'insert_drive_file'],
                ['Update', 'update'],
                ['Delete', 'delete'],
            ],
            legacy_content: null,
        }),
        computed: {
            isAuthenticated() {
                return this.$store.getters['security/isAuthenticated']
            },
        },
        watch: {
            $route(to, from) {
                console.log('remove');
                this.$data.legacy_content = '';
                if (document.querySelector("#sectionMainContent")) {
                    console.log('removed sectionMainContent');
                    document.querySelector("#sectionMainContent").remove();
                }
                let url = window.location.href;
                console.log(url);
                var n = url.indexOf("main/");
                if (n > 0) {
                    console.log('ajax');
                    axios.get(url, {
                        params: {
                            from_vue: 1
                        }
                    })
                        .then((response) => {
                            // handle success
                            this.$data.legacy_content = response.data;
                        });
                }
            },
            legacy_content: {
                handler: function () {
                    console.log('watch');
                    if (document.querySelector("#sectionMainContent")) {
                        //console.log('removed sectionMainContent');
                        //document.querySelector("#sectionMainContent").remove();
                    }
                },
                immediate: true
            },
        },
        mounted() {
            console.log('mounted');
            let legacyContent = document.querySelector("#sectionMainContent");
            if (legacyContent) {
                document.querySelector("#sectionMainContent").remove();
                legacyContent.style.display = 'block';
                this.$data.legacy_content = legacyContent.outerHTML;
            }
        },
        created() {
            this.$data.legacy_content = '';
            // section-content
            let isAuthenticated = JSON.parse(this.$parent.$el.attributes["data-is-authenticated"].value),
                user = JSON.parse(this.$parent.$el.attributes["data-user"].value);

            let payload = {isAuthenticated: isAuthenticated, user: user};
            this.$store.dispatch("security/onRefresh", payload);

            axios.interceptors.response.use(undefined, (err) => {
                return new Promise(() => {
                    if (err.response.status === 401) {
                        this.$router.push({path: "/login"})
                    }
                    throw err;
                });
            });
        },
        beforeMount() {
        }
    }
</script>