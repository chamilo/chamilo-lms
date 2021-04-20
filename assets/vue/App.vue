<template>
  <component :is="layout">
    <router-view />
    <div  id="legacy_content"
          v-html="legacyContent"
    />
  </component>

  <div  v-if="false" class="antialiased text-gray-900 bg-white">
    <div class="flex h-screen overflow-y-hidden bg-white">
      <!-- Sidebar -->
      <Sidebar />

      <div class="flex flex-col flex-1 h-full overflow-hidden py-70">
        <!-- Navbar -->
        <header class="flex-shrink-0 border-b">
          <Navbar />
        </header>
        <!-- Main content -->
        <main class="flex-1 max-h-full p-5 overflow-hidden overflow-y-scroll">
          <router-view />
          <div id="legacy_content"
               v-html="legacyContent"
          />
        </main>
        <!-- Main footer -->
        <footer class="flex items-center justify-between flex-shrink-0 p-4 border-t max-h-14">
          <div>Chamilo 2020</div>
          <div class="text-sm">
            Made by
            <a
                class="text-blue-400 underline"
                href="https://github.com/Kamona-WD"
                target="_blank"
                rel="noopener noreferrer"
            >Ahmed Kamel</a
            >
          </div>
          <div>
            <!-- Github svg -->
            <a
                href="https://github.com/Kamona-WD/starter-dashboard-layout-vue"
                target="_blank"
                class="flex items-center space-x-1"
            >
              <svg class="w-6 h-6 text-gray-400" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                <path
                    fill-rule="evenodd"
                    d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.013 8.013 0 0016 8c0-4.42-3.58-8-8-8z"
                ></path>
              </svg>
              <span class="hidden text-sm md:block">View on Github</span>
            </a>
          </div>
        </footer>
      </div>
      <!-- Setting panel button -->
      <div class="fixed right-0 transform rotate-90 translate-x-8 top-1/2">
        <Button @click="isSettingsPanelOpen = true" class="text-sm font-medium text-white uppercase bg-gray-600">
          Settings
        </Button>
      </div>
      <SettingsPanel :show="isSettingsPanelOpen" @close="isSettingsPanelOpen = false" />
      <SearchPanel left :show="isSearchPanelOpen" @close="isSearchPanelOpen = false" />
      <NotificationsPanel left :show="isNotificationsPanelOpen" @close="isNotificationsPanelOpen = false" />
    </div>
  </div>
  <!-- Component End -->

<!--  <q-layout view="hHh LpR lff" class="bg-grey-1">-->
<!--    <q-header bordered class="bg-white text-grey-8" height-hint="64">-->
<!--      <q-toolbar class="GNL__toolbar">-->
<!--        <q-btn-->
<!--            flat-->
<!--            dense-->
<!--            round-->
<!--            @click="leftDrawerOpen = !leftDrawerOpen"-->
<!--            aria-label="Menu"-->
<!--            icon="menu"-->
<!--            class="q-mr-sm"-->
<!--        />-->

<!--        <q-toolbar-title v-if="$q.screen.gt.xs" shrink class="row items-center no-wrap">-->
<!--          <a href="/">-->
<!--          <img style="width:200px" src="/build/css/themes/chamilo/images/header-logo.png" />-->
<!--          </a>-->
<!--          &lt;!&ndash;          <span class="q-ml-sm">News</span>&ndash;&gt;-->
<!--        </q-toolbar-title>-->
<!--&lt;!&ndash;        <q-select&ndash;&gt;-->
<!--&lt;!&ndash;            v-if="isAuthenticated"&ndash;&gt;-->
<!--&lt;!&ndash;            ref="search" dense use-input hide-selected&ndash;&gt;-->
<!--&lt;!&ndash;            class="GL__toolbar-select"&ndash;&gt;-->
<!--&lt;!&ndash;            color="black"&ndash;&gt;-->
<!--&lt;!&ndash;            :stack-label="false"&ndash;&gt;-->
<!--&lt;!&ndash;            label="Search or jump to..."&ndash;&gt;-->
<!--&lt;!&ndash;            v-model="text"&ndash;&gt;-->
<!--&lt;!&ndash;            :options="filteredOptions"&ndash;&gt;-->
<!--&lt;!&ndash;            @filter="filter"&ndash;&gt;-->
<!--&lt;!&ndash;            style="width: 300px"&ndash;&gt;-->
<!--&lt;!&ndash;        >&ndash;&gt;-->
<!--&lt;!&ndash;          <template v-slot:append>&ndash;&gt;-->
<!--&lt;!&ndash;            <img src="https://cdn.quasar.dev/img/layout-gallery/img-github-search-key-slash.svg">&ndash;&gt;-->
<!--&lt;!&ndash;          </template>&ndash;&gt;-->

<!--&lt;!&ndash;          <template v-slot:no-option>&ndash;&gt;-->
<!--&lt;!&ndash;            <q-item>&ndash;&gt;-->
<!--&lt;!&ndash;              <q-item-section>&ndash;&gt;-->
<!--&lt;!&ndash;                <div class="text-center">&ndash;&gt;-->
<!--&lt;!&ndash;                  <q-spinner-pie&ndash;&gt;-->
<!--&lt;!&ndash;                      color="grey-5"&ndash;&gt;-->
<!--&lt;!&ndash;                      size="24px"&ndash;&gt;-->
<!--&lt;!&ndash;                  />&ndash;&gt;-->
<!--&lt;!&ndash;                </div>&ndash;&gt;-->
<!--&lt;!&ndash;              </q-item-section>&ndash;&gt;-->
<!--&lt;!&ndash;            </q-item>&ndash;&gt;-->
<!--&lt;!&ndash;          </template>&ndash;&gt;-->

<!--&lt;!&ndash;          <template v-slot:option="scope">&ndash;&gt;-->
<!--&lt;!&ndash;            <q-item&ndash;&gt;-->
<!--&lt;!&ndash;                v-bind="scope.itemProps"&ndash;&gt;-->
<!--&lt;!&ndash;                v-on="scope.itemEvents"&ndash;&gt;-->
<!--&lt;!&ndash;                class="GL__select-GL__menu-link"&ndash;&gt;-->
<!--&lt;!&ndash;            >&ndash;&gt;-->
<!--&lt;!&ndash;              <q-item-section side>&ndash;&gt;-->
<!--&lt;!&ndash;                <q-icon name="collections_bookmark" />&ndash;&gt;-->
<!--&lt;!&ndash;              </q-item-section>&ndash;&gt;-->
<!--&lt;!&ndash;              <q-item-section>&ndash;&gt;-->
<!--&lt;!&ndash;                <q-item-label v-html="scope.opt.label" />&ndash;&gt;-->
<!--&lt;!&ndash;              </q-item-section>&ndash;&gt;-->
<!--&lt;!&ndash;              <q-item-section side :class="{ 'default-type': !scope.opt.type }">&ndash;&gt;-->
<!--&lt;!&ndash;                <q-btn outline dense no-caps text-color="blue-grey-5" size="12px" class="bg-grey-1 q-px-sm">&ndash;&gt;-->
<!--&lt;!&ndash;                  {{ scope.opt.type || 'Jump to' }}&ndash;&gt;-->
<!--&lt;!&ndash;                  <q-icon name="subdirectory_arrow_left" size="14px" />&ndash;&gt;-->
<!--&lt;!&ndash;                </q-btn>&ndash;&gt;-->
<!--&lt;!&ndash;              </q-item-section>&ndash;&gt;-->
<!--&lt;!&ndash;            </q-item>&ndash;&gt;-->
<!--&lt;!&ndash;          </template>&ndash;&gt;-->
<!--&lt;!&ndash;        </q-select>&ndash;&gt;-->

<!--        <q-space />-->

<!--        <div v-if="isAuthenticated"  class="GPLAY__toolbar-input-container row no-wrap">-->
<!--          <q-tabs align="center" dense inline-label>-->
<!--            <q-route-tab icon="home"  to="/" label="Home" />-->
<!--            <q-route-tab to="/courses" label="My courses" />-->
<!--            <q-route-tab to="/main/calendar/agenda_js.php?type=personal" label="Agenda" />-->
<!--          </q-tabs>-->
<!--        </div>-->

<!--        <q-space />-->

<!--        <div class="q-gutter-sm row items-center no-wrap">-->
<!--          &lt;!&ndash;          <q-btn v-if="$q.screen.gt.sm" round dense flat color="text-grey-7" icon="apps">&ndash;&gt;-->
<!--          &lt;!&ndash;            <q-tooltip>Google Apps</q-tooltip>&ndash;&gt;-->
<!--          &lt;!&ndash;          </q-btn>&ndash;&gt;-->
<!--          <q-btn v-if="isAuthenticated" round dense flat color="grey-8" icon="notifications">-->
<!--            <q-badge color="red" text-color="white" floating>-->
<!--              2-->
<!--            </q-badge>-->
<!--            <q-tooltip>Notifications</q-tooltip>-->
<!--          </q-btn>-->

<!--          <q-btn v-if="!isAuthenticated"-->
<!--                 :to="{ name: 'Login'}"-->
<!--                 color="primary"-->
<!--                 icon="mail"-->
<!--                 label="Login"-->
<!--          />-->

<!--          &lt;!&ndash;          <Button v-if="!isAuthenticated" :to="{ name: 'Login' }" label="Login" class="p-button-sm"    />&ndash;&gt;-->


<!--          &lt;!&ndash;          <q-btn v-if="isAuthenticated" round flat>&ndash;&gt;-->
<!--          &lt;!&ndash;            <q-avatar size="26px">&ndash;&gt;-->
<!--          &lt;!&ndash;              <img src="https://cdn.quasar.dev/img/boy-avatar.png">&ndash;&gt;-->
<!--          &lt;!&ndash;            </q-avatar>&ndash;&gt;-->
<!--          &lt;!&ndash;            <q-tooltip>Account</q-tooltip>&ndash;&gt;-->
<!--          &lt;!&ndash;          </q-btn>&ndash;&gt;-->

<!--          <q-btn v-if="isAuthenticated" dense flat no-wrap>-->
<!--            <q-avatar size="26px">-->
<!--              <img :src="userAvatar + '?w=80&h=80&fit=crop'" />-->
<!--              &lt;!&ndash;              <q-icon name="person" ></q-icon>&ndash;&gt;-->
<!--            </q-avatar>-->

<!--            <q-icon name="arrow_drop_down" size="16px" />-->
<!--            <q-menu auto-close>-->
<!--              <q-list dense>-->
<!--                <q-item class="GL__menu-link-signed-in">-->
<!--                  <q-item-section>-->
<!--                    <div>Signed in as <strong>{{ currentUser.username }}</strong></div>-->
<!--                  </q-item-section>-->
<!--                </q-item>-->
<!--                &lt;!&ndash;                <q-separator />&ndash;&gt;-->
<!--                &lt;!&ndash;                <q-item clickable class="GL__menu-link-status">&ndash;&gt;-->
<!--                &lt;!&ndash;                  <q-item-section>&ndash;&gt;-->
<!--                &lt;!&ndash;                    <div>&ndash;&gt;-->
<!--                &lt;!&ndash;                      <q-icon name="tag_faces" color="blue-9" size="18px" />&ndash;&gt;-->
<!--                &lt;!&ndash;                      Set your status&ndash;&gt;-->
<!--                &lt;!&ndash;                    </div>&ndash;&gt;-->
<!--                &lt;!&ndash;                  </q-item-section>&ndash;&gt;-->
<!--                &lt;!&ndash;                </q-item>&ndash;&gt;-->
<!--                <q-separator />-->
<!--                <q-item replace :to="'/main/messages/index.php'" clickable class="">-->
<!--                  <q-item-section>Inbox</q-item-section>-->
<!--                </q-item>-->
<!--                <q-item href="/account/home" tag="a" class="">-->
<!--                  <q-item-section>-->
<!--                    Your profile-->
<!--                  </q-item-section>-->
<!--                </q-item>-->
<!--                <q-item href="/account/edit" tag="a"  class="">-->
<!--                  <q-item-section>Settings</q-item-section>-->
<!--                </q-item>-->
<!--                <q-item href="/logout" tag="a" clickable class="">-->
<!--                  <q-item-section>-->
<!--                    Sign out-->
<!--                  </q-item-section>-->
<!--                </q-item>-->
<!--              </q-list>-->
<!--            </q-menu>-->
<!--          </q-btn>-->
<!--        </div>-->
<!--      </q-toolbar>-->
<!--    </q-header>-->
<!--    <q-drawer-->
<!--        v-model="leftDrawerOpen"-->
<!--        show-if-above-->
<!--        bordered-->
<!--        content-class="bg-white"-->
<!--        :width="250"-->
<!--    >-->
<!--      <q-scroll-area class="fit">-->
<!--        <q-list v-if="isAuthenticated" padding class="text-grey-8">-->

<!--          <q-item class="GNL__drawer-item" v-ripple v-for="link in links1" :key="link.text" :to="link.url" clickable>-->
<!--            <q-item-section avatar>-->
<!--              <q-icon :name="link.icon" />-->
<!--            </q-item-section>-->
<!--            <q-item-section>-->
<!--              <q-item-label>{{ link.text }}</q-item-label>-->
<!--            </q-item-section>-->
<!--          </q-item>-->

<!--          <q-separator inset class="q-my-sm" />-->
<!--          <span v-if="isAdmin">-->
<!--            <q-item class="GNL__drawer-item" v-ripple v-for="link in links2" :key="link.text" :to="link.url"  clickable>-->
<!--              <q-item-section avatar>-->
<!--                <q-icon :name="link.icon" />-->
<!--              </q-item-section>-->
<!--              <q-item-section>-->
<!--                <q-item-label>{{ link.text }}</q-item-label>-->
<!--              </q-item-section>-->
<!--            </q-item>-->
<!--          </span>-->

<!--          <q-separator inset class="q-my-sm" />-->

<!--          <q-item class="GNL__drawer-item" v-ripple v-for="link in links3" :key="link.text" clickable>-->
<!--            <q-item-section>-->
<!--              <q-item-label>{{ link.text }} <q-icon v-if="link.icon" :name="link.icon" /></q-item-label>-->
<!--            </q-item-section>-->
<!--          </q-item>-->

<!--          <div class="q-mt-md">-->
<!--            <div class="flex flex-center q-gutter-xs">-->
<!--              <a class="GNL__drawer-footer-link" href="javascript:void(0)" aria-label="About">Chamilo</a>-->
<!--            </div>-->
<!--          </div>-->
<!--        </q-list>-->


<!--        <q-list v-else padding class="text-grey-8">-->
<!--          <q-item class="GNL__drawer-item" v-ripple v-for="link in linksAnon" :key="link.text" :to="link.url" clickable>-->
<!--            <q-item-section avatar>-->
<!--              <q-icon :name="link.icon" />-->
<!--            </q-item-section>-->
<!--            <q-item-section>-->
<!--              <q-item-label>{{ link.text }}</q-item-label>-->
<!--            </q-item-section>-->
<!--          </q-item>-->
<!--        </q-list>-->
<!--      </q-scroll-area>-->
<!--    </q-drawer>-->
<!--    <q-page-container>-->
<!--      <q-page-->
<!--          class="q-layout-padding"-->
<!--      >-->
<!--        <router-view />-->
<!--        <div  id="legacy_content"-->
<!--              v-html="legacyContent"-->
<!--        />-->
<!--      </q-page>-->
<!--    </q-page-container>-->
<!--  </q-layout>-->
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
    const { isSidebarOpen, isSettingsPanelOpen, isSearchPanelOpen, isNotificationsPanelOpen } = useState()

    isSidebarOpen.value = true;

    const { currentRoute } = useRouter();
    const layout = computed(
        () => `${currentRoute.value.meta.layout || defaultLayout}Layout`
    );

    /*const checkScreen = () => {
      if (window.innerWidth <= 1024) {
        isSidebarOpen.value = false
      }
    }*/

    onMounted(() => {
      //window.addEventListener('resize', checkScreen)
    })

    onUnmounted(() => {
      //window.removeEventListener('resize', checkScreen)
    })

    const leftDrawerOpen = ref(false)
    const rightDrawerOpen = ref(false)

    return {
      layout,
      isSidebarOpen,
      isSettingsPanelOpen,
      isSearchPanelOpen,
      isNotificationsPanelOpen,
      leftDrawerOpen,
      toggleLeftDrawer () {
        leftDrawerOpen.value = !leftDrawerOpen.value
      },

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
    showMenu() {
      return this.$route.path !== '/login';
    }
  },
  watch: {
    $route() {
      console.log('App.vue watch $route');
      if ('Login' === this.$route.name) {
        this.leftDrawerOpen = false;
      } else {
        this.leftDrawerOpen = true;
      }

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
          let content = document.querySelector("#sectionMainContent");
          if (content) {
            content.style.display = 'block';
            document.querySelector("#sectionMainContent").remove();
            this.legacyContent = content.outerHTML;
          }
        } else {
          if (document.querySelector("#sectionMainContent")) {
            document.querySelector("#sectionMainContent").remove();
            console.log('remove');
          }

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
          let content = document.querySelector("#sectionMainContent");
          if (content) {
            content.style.display = 'block';
            document.querySelector("#sectionMainContent").remove();
            this.legacyContent = content.outerHTML;
          }
        } else {
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
    // @todo
    if (this.isAuthenticated) {
      this.links1.unshift({icon: 'user-circle', url: '/account/profile', text: this.currentUser.username});
    }
    let app = document.getElementById('app');
    this.legacyContent = '';
    console.log('updated empty created');
    let isAuthenticated = false;
    /*if (app && app.attributes['data-is-authenticated'].value) {
      isAuthenticated = JSON.parse(app.attributes['data-is-authenticated'].value);
    }*/

    console.log('isAuthenticated');
    console.log(isAuthenticated);
    console.log(window.user);

    if (!isEmpty(window.user)) {
    //  console.log('is logged in as ' + window.user.username);
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
      return new Promise(() => {
        // Unauthorized.
        if (401 === err.response.status) {
          // Redirect to the login if status 401.
          this.$router.push({path: "/login"}).catch(()=>{});
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
    console.log('mounted');
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