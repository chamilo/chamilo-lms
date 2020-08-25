<template>
  <v-app id="inspire">
    <snackbar />
    <v-navigation-drawer
      v-model="drawer"
      app
      dark
      :clipped="$vuetify.breakpoint.lgAndUp"
      disable-resize-watcher
      color="primary"
    >
      <v-list v-if="isAuthenticated">
        <v-list-item :to="{ name: 'Index' }">
          <v-list-item-action>
            <v-icon>mdi-home</v-icon>
          </v-list-item-action>
          <v-list-item-content>
            <v-list-item-title>
              Home
            </v-list-item-title>
          </v-list-item-content>
        </v-list-item>

        <v-list-item :to="{ name: 'MyCourses' }">
          <v-list-item-action>
            <v-icon>mdi-book</v-icon>
          </v-list-item-action>
          <v-list-item-content>
            <v-list-item-title>
              My courses
            </v-list-item-title>
          </v-list-item-content>
        </v-list-item>

        <v-list-item :to="{ name: 'MySessions' }">
          <v-list-item-action>
            <v-icon>mdi-book-multiple</v-icon>
          </v-list-item-action>
          <v-list-item-content>
            <v-list-item-title>
              My sessions
            </v-list-item-title>
          </v-list-item-content>
        </v-list-item>

        <v-list-group
          v-if="isAdmin"
          prepend-icon="mdi-plus"
          value="true"
        >
          <template v-slot:activator>
            <v-list-item-title>Admin</v-list-item-title>
          </template>

          <v-list-item
            :to="'/main/admin/user_list.php'"
          >
            <v-list-item-action>
              <v-icon>mdi-account</v-icon>
            </v-list-item-action>
            <v-list-item-content>
              <v-list-item-title>
                User list
              </v-list-item-title>
            </v-list-item-content>
          </v-list-item>

          <v-list-item
            :to="'/main/admin/course_list.php'"
          >
            <v-list-item-action>
              <v-icon>mdi-book</v-icon>
            </v-list-item-action>
            <v-list-item-content>
              <v-list-item-title>
                Courses
              </v-list-item-title>
            </v-list-item-content>
          </v-list-item>

          <v-list-item
            :to="'/main/session/session_list.php'"
          >
            <v-list-item-action>
              <v-icon>mdi-book-multiple</v-icon>
            </v-list-item-action>
            <v-list-item-content>
              <v-list-item-title>
                Sessions
              </v-list-item-title>
            </v-list-item-content>
          </v-list-item>

          <v-list-item
            :to="'/main/admin/index.php'"
          >
            <v-list-item-action>
              <v-icon>mdi-settings</v-icon>
            </v-list-item-action>
            <v-list-item-content>
              <v-list-item-title>
                Settings
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
        <v-list-item :to="{ name: 'Index' }">
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
      <v-app-bar-nav-icon @click.stop="drawer = !drawer" />

      <v-toolbar-title
        style="width: 160px"
        class="ml-0 pl-0"
      >
        <v-img
          class="mx-2"
          src="/build/css/themes/chamilo/images/header-logo.png"
          max-height="50"
          contain
        />
      </v-toolbar-title>
      <v-spacer />

      <v-menu
        v-if="isAuthenticated"
        offset-y
        :nudge-width="200"
      >
        <template v-slot:activator="{ on }">
          <v-btn
            icon
            v-on="on"
          >
            <v-avatar>
              <v-icon dark>
                mdi-bell
              </v-icon>
            </v-avatar>
          </v-btn>
        </template>

        <v-card>
          <v-card-text>
            <div>Notifications</div>
          </v-card-text>
          <v-list>
            <v-list-item>
              <v-list-item-title>
                Notification 1
              </v-list-item-title>
            </v-list-item>
            <v-list-item>
              <v-list-item-title>
                Notification 2
              </v-list-item-title>
            </v-list-item>
          </v-list>
        </v-card>
      </v-menu>

      <v-menu
        v-if="isAuthenticated"
        offset-y
      >
        <template v-slot:activator="{ on }">
          <v-btn
            icon
            v-on="on"
          >
            <v-avatar>
              <v-icon dark>
                mdi-account-circle
              </v-icon>
            </v-avatar>
          </v-btn>
        </template>
        <v-list>
          <v-list-item
            :to="'/account/home'"
          >
            <v-list-item-title>Profile</v-list-item-title>
          </v-list-item>
          <v-list-item
            :to="'/main/messages/inbox.php'"
          >
            <v-list-item-title>Inbox</v-list-item-title>
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
        <v-btn
          small
          color="primary"
          :to="'/login'"
        >
          <v-icon left>
            mdi-account
          </v-icon>
          Login
        </v-btn>

        <v-btn
          small
          color="primary"
          :to="'/register'"
        >
          <v-icon left>
            mdi-pencil
          </v-icon>
          Register
        </v-btn>
      </div>
    </v-app-bar>

    <v-main>
      <Breadcrumb layout-class="pl-3 py-3" />
      <router-view />
      <div
        id="legacy_content"
        v-html="legacy_content"
      />
    </v-main>

    <v-footer
      color="indigo"
      app
    >
      <span class="white--text">&copy; 2020</span>
    </v-footer>
  </v-app>
</template>

<script>
    import NotificationMixin from './mixins/NotificationMixin';
    import Breadcrumb from './components/Breadcrumb';
    import Snackbar from './components/Snackbar';
    import axios from "axios";

    export default {
        name: "App",
        components: {
            Breadcrumb,
            Snackbar
        },

        mixins: [NotificationMixin],
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
            isAdmin() {
                return this.$store.getters['security/isAdmin']
            },
        },
        watch: {
            $route(to, from) {
                this.$data.legacy_content = '';
                if (document.querySelector("#sectionMainContent")) {
                    document.querySelector("#sectionMainContent").remove();
                }
                let url = window.location.href;
                var n = url.indexOf("main/");

                if (n > 0) {
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
                },
                immediate: true
            },
        },
        mounted() {
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
                user = JSON.parse(this.$parent.$el.attributes["data-user-json"].value);

            let payload = {isAuthenticated: isAuthenticated, user: user};
            this.$store.dispatch("security/onRefresh", payload);

            let messages = JSON.parse(this.$parent.$el.attributes["data-messages"].value);
            if (messages) {
              Array.from(messages).forEach(element =>
                  this.showMessage(element)
              );
            }

            axios.interceptors.response.use(undefined, (err) => {
                return new Promise(() => {
                    if (err.response.status === 401) {
                        this.$router.push({path: "/login"})
                    } else if (err.response.status === 500) {
                        document.open();
                        document.write(err.response.data);
                        document.close();
                    }
                    throw err;
                });
            });
        },
        beforeMount() {
        }
    }
</script>