<template>
  <component :is="layout">
    <slot />
    <div
        id="legacy_content"
        v-html="legacyContent"
    />
  </component>
</template>

<script>
import {mapGetters} from 'vuex';
import NotificationMixin from './mixins/NotificationMixin';
import Breadcrumb from './components/Breadcrumb.vue';
import axios from "axios";
import { onMounted, onUnmounted, ref, computed, watch } from 'vue';
import isEmpty from 'lodash/isEmpty';
import { fasGlobeAmericas, fasFlask } from '@quasar/extras/fontawesome-v5'
import { useRouter, useRoute } from 'vue-router'

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
    const { currentRoute } = useRouter();
    const layout = computed(
        () => `${currentRoute.value.meta.layout || defaultLayout}Layout`
    );
    const route = useRoute();

    watch(
        () => route.meta,
        async meta => {
          try {
            const component = `${meta.layout}.vue`;
            layout.value = component?.default || defaultLayout
          } catch (e) {
            layout.value = defaultLayout
          }
        },
        {immediate: true}
    )

    return {
      layout
    }
  },

  data: () => ({
    user: {},
    userAvatar: '',
    firstTime: false,
    legacyContent: '',
  }),

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
    console.log('APP created');
    this.legacyContent = '';
    console.log('updated empty created');
    let app = document.getElementById('app');

    let isAuthenticated = false;
    if (!isEmpty(window.user)) {
      console.log('is logged in as ' + window.user.username);
      console.log('userAvatar ' + window.userAvatar);
      this.user = window.user;
      this.userAvatar = window.userAvatar;
      isAuthenticated = true;
    }
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
  mixins: [NotificationMixin],
  computed: {
    ...mapGetters({
      'isAuthenticated': 'security/isAuthenticated',
      'isAdmin': 'security/isAdmin',
      'currentUser': 'security/getUser',
    }),
  }
}
</script>