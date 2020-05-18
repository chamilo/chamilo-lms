<template>
        <v-app id="inspire">
            <snackbar></snackbar>

            <v-navigation-drawer
                    v-model="drawer"
                    :clipped="$vuetify.breakpoint.lgAndUp"
                    app
            >
                <v-list dense>
                    <v-list-item>
                        <v-list-item-action>
                            <v-icon>mdi-home</v-icon>
                        </v-list-item-action>
                        <v-list-item-content>
                            <v-list-item-title>Home</v-list-item-title>
                        </v-list-item-content>
                    </v-list-item>
                    <v-list-item>
                        <v-list-item-action>
                            <v-icon>mdi-book</v-icon>
                        </v-list-item-action>
                        <v-list-item-content>
                            <v-list-item-title>
                                <router-link :to="{ name: 'CourseList' }">Courses</router-link>
                            </v-list-item-title>
                        </v-list-item-content>
                    </v-list-item>
                    <v-list-item>
                        <v-list-item-action>
                            <v-icon>mdi-book</v-icon>
                        </v-list-item-action>
                        <v-list-item-content>
                            <v-list-item-title>
                                <router-link :to="{ name: 'CourseCategoryList' }">Courses category</router-link>
                            </v-list-item-title>
                        </v-list-item-content>
                    </v-list-item>

                    <v-list-item>
                        <v-list-item-action>
                            <v-icon>mdi-comment-quote</v-icon>
                        </v-list-item-action>
                        <v-list-item-content>
                            <v-list-item-title>
                                <router-link :to="{ name: 'DocumentsList' }">Documents</router-link>
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
                    color="blue darken-3"
                    dark
            >
<!--                <v-app-bar-nav-icon @click.stop="drawer = !drawer"></v-app-bar-nav-icon>-->
                <v-app-bar-nav-icon @click.stop="drawer = !drawer"></v-app-bar-nav-icon>

                <v-toolbar-title
                        style="width: 300px"
                        class="ml-0 pl-4"
                >
                    <span class="hidden-sm-and-down">Chamilo</span>
                </v-toolbar-title>
<!--                <v-text-field-->
<!--                        flat-->
<!--                        solo-inverted-->
<!--                        hide-details-->
<!--                        prepend-inner-icon="mdi-magnify"-->
<!--                        label="Search"-->
<!--                        class="hidden-sm-and-down"-->
<!--                ></v-text-field>-->
                <v-spacer></v-spacer>
                <v-btn icon>
                    <v-icon>mdi-apps</v-icon>
                </v-btn>
                <v-btn icon>
                    <v-icon>mdi-bell</v-icon>
                </v-btn>
                <v-btn
                        icon
                        large
                >
                    <v-avatar
                            size="32px"
                            item
                    >
                        <v-img
                                src="https://cdn.vuetifyjs.com/images/logos/logo.svg"
                                alt="Vuetify"
                        ></v-img></v-avatar>
                </v-btn>
            </v-app-bar>



            <v-content>
                <Breadcrumb layout-class="pl-3 py-3" />
                <router-view></router-view>
            </v-content>


            <v-footer color="indigo" app>
                <span class="white--text">&copy; 2019</span>
            </v-footer>

        </v-app>
</template>

<script>
    import Breadcrumb from './components/Breadcrumb';
    import Snackbar from './components/Snackbar';

    export default {
        name: "App",
        components: {
            Breadcrumb,
            Snackbar
        },
        data: () => ({
            drawer: null
        }),
        beforeMount() {
        }
    }
</script>