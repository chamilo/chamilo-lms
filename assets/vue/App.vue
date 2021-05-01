<template>
  <q-layout view="hHh LpR lff" class="bg-grey-1">
    <q-header bordered class="bg-white text-grey-8" height-hint="64">
      <q-toolbar>
        <q-btn
            flat
            dense
            round
            @click="isSidebarOpen = !isSidebarOpen"
            aria-label="Menu"
            icon="menu"
            class="q-mr-sm"
        />
        <q-toolbar-title v-if="$q.screen.gt.xs" shrink class="row items-center no-wrap">
          <img style="width:200px" src="/build/css/themes/chamilo/images/header-logo.png" />
        </q-toolbar-title>

        <q-space />

        <div v-if="isAuthenticated"  class="GPLAY__toolbar-input-container row no-wrap">
          <q-tabs v-if="$q.screen.gt.xs" align="center" dense inline-label>
            <q-route-tab dense no-caps icon="home"  to="/" label="Home" />
            <q-route-tab no-caps icon="book" to="/courses" label="My courses" />
            <q-route-tab no-caps icon="event" to="/main/calendar/agenda_js.php?type=personal" label="Agenda" />
          </q-tabs>
          <q-tabs v-else align="center" dense inline-label>
            <q-route-tab dense no-caps icon="home"  to="/"  />
            <q-route-tab no-caps icon="book" to="/courses" />
            <q-route-tab no-caps icon="event" to="/main/calendar/agenda_js.php?type=personal" />
          </q-tabs>
        </div>

        <q-space />

        <div class="q-gutter-sm row items-center no-wrap">
          <!--          <q-btn v-if="$q.screen.gt.sm" round dense flat color="text-grey-7" icon="apps">-->
          <!--            <q-tooltip>Google Apps</q-tooltip>-->
          <!--          </q-btn>-->
          <q-btn v-if="isAuthenticated" round dense flat color="grey-8" icon="notifications">
            <q-badge color="red" text-color="white" floating>
              2
            </q-badge>
            <q-tooltip>Notifications</q-tooltip>
          </q-btn>

          <q-btn v-if="!isAuthenticated"
                 :to="{ name: 'Login'}"
                 color="primary"
                 icon="mail"
                 label="Login"
          />

          <!--          <Button v-if="!isAuthenticated" :to="{ name: 'Login' }" label="Login" class="p-button-sm"    />-->


          <!--          <q-btn v-if="isAuthenticated" round flat>-->
          <!--            <q-avatar size="26px">-->
          <!--              <img src="https://cdn.quasar.dev/img/boy-avatar.png">-->
          <!--            </q-avatar>-->
          <!--            <q-tooltip>Account</q-tooltip>-->
          <!--          </q-btn>-->

          <q-btn v-if="isAuthenticated" dense flat no-wrap>
            <q-avatar size="26px">
              <img :src="userAvatar + '?w=80&h=80&fit=crop'" />
              <!--              <q-icon name="person" ></q-icon>-->
            </q-avatar>

            <q-icon name="arrow_drop_down" size="16px" />
            <q-menu auto-close>
              <q-list dense>
                <q-item class="GL__menu-link-signed-in">
                  <q-item-section>
                    <div>Signed in as <strong>{{ currentUser.username }}</strong></div>
                  </q-item-section>
                </q-item>
                <!--                <q-separator />-->
                <!--                <q-item clickable class="GL__menu-link-status">-->
                <!--                  <q-item-section>-->
                <!--                    <div>-->
                <!--                      <q-icon name="tag_faces" color="blue-9" size="18px" />-->
                <!--                      Set your status-->
                <!--                    </div>-->
                <!--                  </q-item-section>-->
                <!--                </q-item>-->
                <q-separator />
                <q-item replace :to="'/main/messages/index.php'"  clickable class="">
                  <q-item-section>Inbox</q-item-section>
                </q-item>
                <q-item href="/account/home" tag="a" class="">
                  <q-item-section>
                    Your profile
                  </q-item-section>
                </q-item>
                <q-item href="/account/edit" tag="a"  class="">
                  <q-item-section>Settings</q-item-section>
                </q-item>
                <q-item href="/logout" tag="a" clickable class="">
                  <q-item-section>
                    Sign out
                  </q-item-section>
                </q-item>
              </q-list>
            </q-menu>
          </q-btn>
        </div>
      </q-toolbar>
    </q-header>

    <q-drawer
        v-model="isSidebarOpen"
        show-if-above
        bordered
        content-class="bg-white"
        :width="280"
        :breakpoint="500"
    >
      <q-scroll-area class="fit">
        <q-list v-if="isAuthenticated" padding class="text-grey-8">

          <q-item class="GNL__drawer-item" v-ripple v-for="link in links1" :key="link.text" :to="link.url" clickable>
            <q-item-section avatar>
<!--              <q-icon :name="link.icon" />-->
              <font-awesome-icon :icon="link.icon" size="lg" />
            </q-item-section>
            <q-item-section>
              <q-item-label>{{ link.text }}</q-item-label>
            </q-item-section>
          </q-item>

          <q-separator inset class="q-my-sm" />

          <q-item class="GNL__drawer-item" v-ripple v-for="link in links2" :key="link.text" :to="link.url"  clickable>
            <q-item-section avatar>
<!--              <q-icon :name="link.icon" />-->
              <font-awesome-icon :icon="link.icon" size="lg" />
            </q-item-section>
            <q-item-section>
              <q-item-label>{{ link.text }}</q-item-label>
            </q-item-section>
          </q-item>

          <q-separator inset class="q-my-sm" />

          <q-item class="GNL__drawer-item" v-ripple v-for="link in links3" :key="link.text" clickable>
            <q-item-section>
              <q-item-label>{{ link.text }}
<!--                <q-icon v-if="link.icon" :name="link.icon" />-->
                <font-awesome-icon :icon="link.icon" size="lg" />
              </q-item-label>
            </q-item-section>
          </q-item>

          <div class="q-mt-md">
            <div class="flex flex-center q-gutter-xs">
              <a class="GNL__drawer-footer-link" href="javascript:void(0)" aria-label="About">Chamilo</a>
            </div>
          </div>
        </q-list>

        <q-list v-else padding class="text-grey-8">
          <q-item class="GNL__drawer-item" v-ripple v-for="link in linksAnon" :key="link.text" :to="link.url" clickable>
            <q-item-section avatar>
              <q-icon :name="link.icon" />
            </q-item-section>
            <q-item-section>
              <q-item-label>{{ link.text }}</q-item-label>
            </q-item-section>
          </q-item>
        </q-list>
      </q-scroll-area>
    </q-drawer>
    <!--    <q-drawer show-if-above v-model="rightDrawerOpen" side="right" elevated>-->
    <!--      &lt;!&ndash; drawer content &ndash;&gt;-->
    <!--    </q-drawer>-->

    <q-page-container>
      <q-page
          class="q-layout-padding"
      >
        <router-view />
        <div  id="legacy_content"
              v-html="legacyContent"
        />
      </q-page>
    </q-page-container>
  </q-layout>
</template>

<script>
import {mapGetters} from 'vuex';
import NotificationMixin from './mixins/NotificationMixin';
import Breadcrumb from './components/Breadcrumb.vue';
import axios from "axios";
import { onMounted, onUnmounted, ref, computed } from 'vue';
import isEmpty from 'lodash/isEmpty';
import { fasGlobeAmericas, fasFlask } from '@quasar/extras/fontawesome-v5'
import { useRouter } from 'vue-router'

import useState from './hooks/useState'
/*import Sidebar from './components/sidebar/Sidebar.vue'
import Navbar from './components/navbar/Navbar.vue'
import SettingsPanel from './components/panels/SettingsPanel.vue'
import SearchPanel from './components/panels/SearchPanel.vue'
import NotificationsPanel from './components/panels/NotificationsPanel.vue'
import Button from './components/global/Button.vue'*/

const defaultLayout = "Dashboard";

export default {
  name: "App",
  components: {
    /*Navbar,
    Sidebar,
    SettingsPanel,
    SearchPanel,
    NotificationsPanel,
    Button,*/
    Breadcrumb,
  },
  setup () {
    const { isSidebarOpen, isSettingsPanelOpen, isSearchPanelOpen, isNotificationsPanelOpen } = useState();

    const { currentRoute } = useRouter();
    const layout = computed(
        () => `${currentRoute.value.meta.layout || defaultLayout}Layout`
    );
    const rightDrawerOpen = ref(false);
    //console.log('isSidebarOpen');console.log(isSidebarOpen.value);

    return {
      layout,
      isSettingsPanelOpen,
      isSearchPanelOpen,
      isNotificationsPanelOpen,
      isSidebarOpen,
      rightDrawerOpen,
      toggleRightDrawer () {
        rightDrawerOpen.value = !rightDrawerOpen.value
      }
    }
  },
  mixins: [NotificationMixin],
  data: () => ({
    user: {},
    userAvatar: '',
    firstTime: false,
    search: '',
    showAdvanced: false,
    showDateOptions: false,
    exactPhrase: '',
    hasWords: '',
    excludeWords: '',
    byWebsite: '',
    byDate: 'Any time',
    moved: true,
    links1: [
      // { icon: 'person', url: '/courses', text: 'My courses' },
      // { icon: 'star_border', url: '/sessions', text: 'Sessions' },
      //{ icon: 'star_border', url: '/calendar', text: 'My calendar' },
      { icon: 'compass', url: '/catalog', text: 'Explore' },
      // { icon: 'star_border', url: '/news', text: 'News' },
    ],
    links2: [
      { icon: 'users', url: '/main/admin/user_list.php', text: 'Users' },
      { icon: 'book', url: '/main/admin/course_list.php', text: 'Courses' },
      { icon: 'book-open',  url: '/main/session/session_list.php', text: 'Sessions' },
      //{ icon: fasFlask, url: '/main/admin/index.php', text: 'Administration' },
      { icon: 'cogs', url: '/main/admin/index.php', text: 'Administration' },
    ],
    links3: [
      //{ icon: '', text: 'Settings' },
      // { icon: 'open_in_new', text: 'open in new' },
    ],
    linksAnon: [
      { icon: 'home', url: '/', text: 'Home' },
    ],
    drawer: true,
    breadcrumb: [],
    languageArray: ['en', 'fr'],
    courses: [
      ['Courses', 'mdi-book', 'CourseList'],
      ['Courses category', 'mdi-book', 'CourseCategoryList'],
    ],
    /*cruds: [
      ['Create', 'add'],
      ['Read', 'insert_drive_file'],
      ['Update', 'update'],
      ['Delete', 'delete'],
    ],*/
    legacyContent: '',
  }),
  computed: {
    ...mapGetters({
      'isAuthenticated': 'security/isAuthenticated',
      'isAdmin': 'security/isAdmin',
      'currentUser': 'security/getUser',
    }),
  },
  watch: {
    $route() {
      console.log('App.vue watch $route');
      console.log(this.$route.name);
      //let content = document.getElementById("sectionMainContent");
      this.legacyContent = '';
      /*if (content && false === this.contentLoaded) {
        console.log('updated ok ');
        content.style.display = 'block';
        this.legacyContent = content.outerHTML;
        if (document.querySelector("#sectionMainContent")) {
          console.log('remove sectionMainContent ');
          document.querySelector("#sectionMainContent").remove();
        }
      }*/

      let url = window.location.href;
      var n = url.indexOf("main/");
      if (n > 0) {
        if (this.firstTime) {
          console.log('firstTime: 1.');
          let content = document.querySelector("#sectionMainContent");
          if (content) {
            console.log('legacyContent updated');
            content.style.display = 'block';
            document.querySelector("#sectionMainContent").remove();
            this.legacyContent = content.outerHTML;
          }
        } else {
          if (document.querySelector("#sectionMainContent")) {
            document.querySelector("#sectionMainContent").remove();
            //console.log('remove');
          }

          console.log('Replace URL', url);
          window.location.replace(url);

          /*axios.get(url, {
            params: {
              from_vue: 1
            },
          })
              .then((response) => {
                console.log('updated page using axios');
                this.legacyContent = response.data;
              }).catch(function (error) {
            if (error.response) {
              // Request made and server responded
              console.log(error.response.status);
              console.log(error.response.data);
            } else if (error.request) {
              // The request was made but no response was received
              console.log(error.request);
            } else {
              console.log('Error', error.message);
            }
          });*/
        }
      } else {
        if (this.firstTime) {
          console.log('firstTime 2.');
          let content = document.querySelector("#sectionMainContent");
          if (content) {
            console.log('legacyContent updated');
            content.style.display = 'block';
            document.querySelector("#sectionMainContent").remove();
            this.legacyContent = content.outerHTML;
          }
        } else {
          console.log('legacyContent cleaned');
          let content = document.querySelector("#sectionMainContent");
          if (content) {
            document.querySelector("#sectionMainContent").remove();
          }
          this.legacyContent = '';
        }
      }
      this.firstTime = false;
    },
    legacyContent: {
      handler: function () {
      },
      immediate: true
    },
  },
  created() {
    console.log('created');
    let app = document.getElementById('app');
    this.legacyContent = '';
    console.log('updated empty created');

    let isAuthenticated = false;
    if (!isEmpty(window.user)) {
      console.log('is logged in as ' + window.user.username);
      console.log('userAvatar ' + window.userAvatar);
      this.user = window.user;
      this.userAvatar = window.userAvatar;
      isAuthenticated = true;
    }

    /*if (app && app.attributes["data-user-json"].value) {
      this.user = JSON.parse(app.attributes["data-user-json"].value);
      this.userAvatar = app.attributes["data-user-avatar"].value;
    }*/
    console.log(this.user);
    let payload = {isAuthenticated: isAuthenticated, user: this.user};
    this.$store.dispatch("security/onRefresh", payload);

    if (isAuthenticated) {
      this.links1.unshift({icon: 'user-circle', url: '/account/profile', text: this.currentUser.username});
    }

    if (app && app.attributes["data-flashes"]) {
      let flashes = JSON.parse(app.attributes["data-flashes"].value);
      if (flashes) {
        for (const key in flashes) {
          for (const text in flashes[key]) {
            this.showMessage(flashes[key][text], key);
          }
        }
      }
    }

    if (app && app.attributes["data-breadcrumb"]) {
      this.breadcrumb = JSON.parse(app.attributes["data-breadcrumb"].value);
    }

    axios.interceptors.response.use(undefined, (err) => {
      console.log('interceptor');
      console.log(err.response.status);

      return new Promise(() => {
        // Unauthorized.
        if (401 === err.response.status) {
          // Redirect to the login if status 401.
          //this.$router.replace({path: "/login"}).catch(()=>{});
          // Real redirect to avoid loops with Login.vue page.
          window.location.href = '/login';
        } else if (500 === err.response.status) {
          if (err.response) {
            // Request made and server responded
            this.showMessage(err.response.data.detail, 'warning');
          }
        }
        throw err;
      });
    });
  },
  mounted() {
    console.log('app.vue mounted');
    this.firstTime = true;
  },
  methods: {
    dropdownHandler(event) {
      let single = event.currentTarget.getElementsByTagName("ul")[0];
      single.classList.toggle("hidden");
    },
    sidebarHandler() {
      var sideBar = document.getElementById("mobile-nav");
      sideBar.style.transform = "translateX(-260px)";
      if (this.$data.moved) {
        sideBar.style.transform = "translateX(0px)";
        this.$data.moved = false;
      } else {
        sideBar.style.transform = "translateX(-260px)";
        this.$data.moved = true;
      }
    },
  }
}
</script>