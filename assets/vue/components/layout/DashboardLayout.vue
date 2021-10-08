<template>
  <q-layout view="hHh LpR lff" class="bg-grey-1">
    <q-header class="bg-white text-grey-8 header-border-bottom" height-hint="64">
      <q-toolbar>
        <q-toolbar-title v-if="$q.screen.gt.xs" shrink class="row items-center no-wrap">
          <img style="height:40px" src="/build/css/themes/chamilo/images/header-logo.svg" />
        </q-toolbar-title>

        <q-btn
            flat
            dense
            round
            @click="isSidebarOpen = !isSidebarOpen"
            aria-label="Menu"
            icon="menu"
            class="q-mr-sm"
        />

        <q-space />

<!--        <div v-if="isAuthenticated"  class="GPLAY__toolbar-input-container row no-wrap">-->
<!--          <q-tabs v-if="$q.screen.gt.xs" align="center" dense inline-label>-->
<!--            <q-route-tab dense no-caps icon="home"  to="/" label="Home" />-->
<!--            <q-route-tab no-caps icon="book" to="/courses" label="My courses" />-->
<!--            <q-route-tab no-caps icon="event" to="/main/calendar/agenda_js.php?type=personal" label="Agenda" />-->
<!--          </q-tabs>-->
<!--          <q-tabs v-else align="center" dense inline-label>-->
<!--            <q-route-tab dense no-caps icon="home"  to="/"  />-->
<!--            <q-route-tab no-caps icon="book" to="/courses" />-->
<!--            <q-route-tab no-caps icon="event" to="/main/calendar/agenda_js.php?type=personal" />-->
<!--          </q-tabs>-->
<!--        </div>-->
        <q-space />

        <div class="q-gutter-sm row items-center no-wrap">
          <q-btn
            v-if="isAuthenticated && 'true' === config['display.show_link_ticket_notification']"
            color="grey-8"
            dense
            flat
            icon="mdi-face-agent"
            round
            type="a"
            :href="generateTicketUrl()"
          >
            <q-tooltip>{{ $t('Ticket') }}</q-tooltip>
          </q-btn>

          <q-btn v-if="isAuthenticated" round dense flat color="grey-8"
                 icon="person"
                 :to="'/account/home'"
          >
            <q-tooltip>{{ $t('Profile') }}</q-tooltip>
          </q-btn>

          <q-btn v-if="isAuthenticated" round dense flat color="grey-8"
                 icon="inbox"
                 :to="'/resources/messages'"
          >
<!--            <q-badge color="red" text-color="white" floating>-->
<!--              2-->
<!--            </q-badge>-->
            <q-tooltip>{{ $t('Inbox') }}</q-tooltip>
          </q-btn>

<!--          <q-btn-->
<!--              v-if="isAuthenticated" round dense flat color="grey-8" icon="folder"-->
<!--               :to="'/resources/personal_files/' + currentUser.resourceNode.id"-->
<!--          >-->
<!--            <q-tooltip>Files</q-tooltip>-->
<!--          </q-btn>-->


<!--          <q-btn v-if="isAuthenticated" round dense flat color="grey-8" icon="notifications">-->
<!--            <q-badge color="red" text-color="white" floating>-->
<!--              2-->
<!--            </q-badge>-->
<!--            <q-tooltip>Notifications</q-tooltip>-->
<!--          </q-btn>-->

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
              <img :src="currentUser.illustrationUrl + '?w=80&h=80&fit=crop'" />
              <!--              <q-icon name="person" ></q-icon>-->
            </q-avatar>

            <q-icon name="arrow_drop_down" size="16px" />
            <q-menu auto-close>
              <q-list dense>
                <q-item class="GL__menu-link-signed-in">
                  <q-item-section>
                    <div>{{ $t('Signed in as') }} <strong>{{ currentUser.username }}</strong></div>
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
<!--                <q-item replace :to="'/main/messages/index.php'"  clickable class="">-->
<!--                  <q-item-section>Inbox</q-item-section>-->
<!--                </q-item>-->
<!--                <q-item href="/account/home" tag="a" class="">-->
<!--                  <q-item-section>-->
<!--                    Your profile-->
<!--                  </q-item-section>-->
<!--                </q-item>-->
                <q-item href="/account/edit" tag="a"  class="">
                  <q-item-section>{{ $t('Settings') }}</q-item-section>
                </q-item>
                <q-item href="/logout" tag="a" clickable class="">
                  <q-item-section>
                    {{ $t('Logout') }}
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
        :breakpoint="850"
        class="q-mt-sm"
    >
      <q-scroll-area class="fit">

        <q-list class="text-grey-8">
          <q-item class="GNL__drawer-item" v-ripple v-for="link in linksAnon" :key="link.text" :to="link.url" clickable>
            <q-item-section avatar>
              <v-icon :icon="link.icon" medium />
            </q-item-section>
            <q-item-section>
              <q-item-label>{{ link.text }}</q-item-label>
            </q-item-section>
          </q-item>
        </q-list>

        <q-separator inset class="q-my-sm" />

        <q-list v-if="isAuthenticated" padding class="text-grey-8">
          <q-item class="GNL__drawer-item" v-ripple v-for="link in linksUser" :key="link.text" :to="link.url" clickable>
            <q-item-section avatar>
              <!--              <q-icon :name="link.icon" />-->
              <v-icon :icon="link.icon" medium />
            </q-item-section>
            <q-item-section>
              <q-item-label>{{ link.text }}</q-item-label>
            </q-item-section>
          </q-item>

          <q-separator v-if="isAdmin" inset class="q-my-sm" />

          <q-item v-if="isAdmin" class="GNL__drawer-item" v-ripple v-for="link in linksAdmin" :key="link.text" :to="link.url"  clickable>
            <q-item-section avatar>
              <!--              <q-icon :name="link.icon" />-->
              <v-icon :icon="link.icon" medium />
            </q-item-section>
            <q-item-section>
              <q-item-label>{{ link.text }}</q-item-label>
            </q-item-section>
          </q-item>

          <q-separator inset class="q-my-sm" />

          <div class="q-mt-md">
            <div class="flex flex-center q-gutter-xs">
              <a
                  class="GNL__drawer-footer-link"
                  href="javascript:void(0)"
              >
                {{ config['platform.site_name'] }}
                {{ config['platform.institution'] }}
              </a>
            </div>
          </div>
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
        <Breadcrumb v-if="showBreadcrumb" :legacy="this.breadcrumb"/>
        <router-view />
        <slot></slot>
      </q-page>
    </q-page-container>
  </q-layout>
</template>

<script>
import {mapGetters, useStore} from "vuex";
import isEmpty from "lodash/isEmpty";
import useState from "../../hooks/useState";
import {computed, ref, toRefs} from "vue";
import Breadcrumb from '../../components/Breadcrumb.vue';
import {useRoute} from 'vue-router'

import { useI18n } from 'vue-i18n'

export default {
  name: "DashboardLayout",
  components: {
    Breadcrumb
  },
  props: {
    showBreadcrumb: {
      type: Boolean,
      default: true,
    },
  },
  setup (props) {
    const { isSidebarOpen, isSettingsPanelOpen, isSearchPanelOpen, isNotificationsPanelOpen } = useState();
    const rightDrawerOpen = ref(false);
    const linksUser = ref([]);
    const linksAdmin = ref([]);
    const linksAnon = ref([]);
    const { showBreadcrumb } = toRefs(props);
    const config = ref([]);
    const route = useRoute();

    if (!isEmpty(window.config)) {
      config.value = window.config;
    }
    const { t } = useI18n();

    linksUser.value = [
      //{ icon: 'home', url: '/', text: 'Home' },
      //{ icon: 'star_border', url: '/', text: 'News' },
      { icon: 'mdi-book-open-page-variant', url: '/courses', text: t('My courses') },
      { icon: 'mdi-google-classroom', url: '/sessions', text: t('My sessions') },
      { icon: 'mdi-calendar-text', url: '/resources/ccalendarevent', text: t('Events') },
      { icon: 'mdi-chart-box', url: '/main/auth/my_progress.php', text: t('My progress') },
      //{ icon: 'star_border', url: '/calendar', text: 'My calendar' },
      //{ icon: 'compass', url: '/catalog', text: 'Explore' },
      // { icon: 'star_border', url: '/news', text: 'News' },
    ];

    linksAdmin.value = [
      { icon: 'mdi-account-multiple', url: '/main/admin/user_list.php', text: t('Users') },
      { icon: 'mdi-book-open-page-variant', url: '/main/admin/course_list.php', text: t('Courses') },
      { icon: 'mdi-google-classroom',  url: '/main/session/session_list.php', text: t('Sessions') },
      { icon: 'mdi-cogs', url: '/main/admin/index.php', text: t('Administration') },
      { icon: 'mdi-chart-box', url: '/main/mySpace/index.php', text: t('Reporting') },
    ];

    linksAnon.value = [
      { icon: 'mdi-home', url: '/home', text: t('Home') },
      //{ icon: 'mdi-compass', url: '/catalog', text: 'Explore' },
    ];

    function generateTicketUrl() {
      const queryParams = new URLSearchParams(window.location.href);

      const cid = route.query.cid || route.params.id || queryParams.get('cid') || 0;
      const sid = route.query.sid || queryParams.get('sid') || 0;
      const gid = route.query.gid || queryParams.get('gid') || 0;

      return `/main/ticket/tickets.php?project_id=1&cid=${cid}&sid=${sid}&gid=${gid}`;
    }

    return {
      linksAnon,
      linksUser,
      linksAdmin,
      config,
      showBreadcrumb,
      isSettingsPanelOpen,
      isSearchPanelOpen,
      isNotificationsPanelOpen,
      isSidebarOpen,
      rightDrawerOpen,
      toggleRightDrawer () {
        rightDrawerOpen.value = !rightDrawerOpen.value
      },
      generateTicketUrl,
    }
  },

  data: () => ({
    user: {},
    moved: true,
    drawer: true,
    breadcrumb: [],
    languageArray: ['en', 'fr'],
    courses: [
      ['Courses', 'mdi-book', 'CourseList'],
      ['Courses category', 'mdi-book', 'CourseCategoryList'],
    ],
  }),
  updated() {
    if (this.isAuthenticated) {
      if (this.isBoss) {
        if(!this.linksUser.some(data => data.id === 'load_search')) {
          this.linksUser.push({
            icon: 'mdi-format-list-checks',
            url: '/main/search/load_search.php',
            text: this.$i18n.t('Diagnosis Management'),
            id: 'load_search'
          });
        }
        if(!this.linksUser.some(data => data.id === 'search')) {
          this.linksUser.push({
            icon: 'mdi-account-search',
            url: '/main/search/search.php',
            text: this.$i18n.t('Diagnostic Form'),
            id: 'search'
          });
        }
      }
      if (this.isStudent) {
        if(!this.linksUser.some(data => data.id === 'search')) {
          this.linksUser.push({
            icon: 'mdi-account-search',
            url: '/main/search/search.php',
            text: this.$i18n.t('Diagnostic Form'),
            id: 'search'
          });
        }
      }
    }
  },
  created() {
    this.legacyContent = '';
    let isAuthenticated = false;
    if (!isEmpty(window.user)) {
      this.user = window.user;
      isAuthenticated = true;
    }

    try {
      if (window.breadcrumb) {
        this.breadcrumb = window.breadcrumb;
      }
    } catch (e) {
      console.log(e.message);
    }

    //let payload = {isAuthenticated: isAuthenticated, user: this.user};
    //this.$store.dispatch("security/onRefresh", payload);
    /*if (isAuthenticated) {
      this.linksUser.unshift({icon: 'mdi-account', url: '/account/home', text: this.currentUser.username});
    }*/
  },
  computed: {
    ...mapGetters({
      'isAuthenticated': 'security/isAuthenticated',
      'isAdmin': 'security/isAdmin',
      'isBoss': 'security/isBoss',
      'isStudent': 'security/isStudent',
      'currentUser': 'security/getUser',
    }),
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
  },
}
</script>
