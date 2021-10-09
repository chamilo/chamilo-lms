<template>
  <component :is="layout" :show-breadcrumb="showBreadcrumb">
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
import axios from "axios";
import { computed, watch, provide, ref } from 'vue';
import isEmpty from 'lodash/isEmpty';
import { useRouter, useRoute } from 'vue-router';

import useState from './hooks/useState'
/*import Sidebar from './components/sidebar/Sidebar.vue'
import Navbar from './components/navbar/Navbar.vue'
import SettingsPanel from './components/panels/SettingsPanel.vue'
import SearchPanel from './components/panels/SearchPanel.vue'
import NotificationsPanel from './components/panels/NotificationsPanel.vue'
import Button from './components/global/Button.vue'*/

const defaultLayout = 'Dashboard';
import { DefaultApolloClient } from '@vue/apollo-composable';
import { ApolloClient, createHttpLink, InMemoryCache } from '@apollo/client/core';

// HTTP connection to the API
const httpLink = createHttpLink({
  // You should use an absolute URL here
  uri: '/api/graphql',
})

// Cache implementation
const cache = new InMemoryCache();

// Create the apollo client
const apolloClient = new ApolloClient({
  link: httpLink,
  cache,
});

export default {
  name: "App",
  setup () {
    const { currentRoute } = useRouter();
    const route = useRoute();
    const showBreadcrumb = ref(true);

    const layout = computed(
        () => {
            let queryParams = new URLSearchParams(window.location.href);

            if (queryParams.has('lp')
                || (queryParams.has('origin') && queryParams.get('origin') === 'learnpath')
            ) {
                return 'EmptyLayout';
            } else {
                return `${currentRoute.value.meta.layout || defaultLayout}Layout`
            }
        }
    );

    provide(DefaultApolloClient, apolloClient)

    watch(
        () => route.meta,
        async meta => {
          try {
            const component = `${meta.layout}.vue`;
            layout.value = component?.default || defaultLayout;
            showBreadcrumb.value = meta.showBreadcrumb;
          } catch (e) {
            layout.value = defaultLayout
          }
        },
        {immediate: true}
    );

    return {
      showBreadcrumb,
      layout
    }
  },
  data: () => ({
    user: {},
    firstTime: false,
    legacyContent: '',
  }),
  watch: {
    $route() {
      //console.log('watch.$route');
      this.legacyContent = '';

      // This code below will handle the legacy content to be loaded.
      let url = window.location.href;
      var n = url.indexOf("main/");
      if (n > 0) {
        if (this.firstTime) {
          //console.log('App.vue: firstTime: 1.');
          let content = document.querySelector("#sectionMainContent");
          if (content) {
            //console.log('legacyContent updated');
            content.style.display = 'block';
            document.querySelector("#sectionMainContent").remove();
            this.legacyContent = content.outerHTML;
          }
        } else {
          if (document.querySelector("#sectionMainContent")) {
            document.querySelector("#sectionMainContent").remove();
            //console.log('remove');
          }

          //console.log('Replace URL', url);
          window.location.replace(url);
        }
      } else {
        if (this.firstTime) {
          //console.log('App.vue: firstTime 2');
          let content = document.querySelector("#sectionMainContent");
          if (content) {
            //console.log('legacyContent updated');
            content.style.display = 'block';
            document.querySelector("#sectionMainContent").remove();
            this.legacyContent = content.outerHTML;
          }
        } else {
          //console.log('legacyContent cleaned');
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
    //console.log('App.vue created');
    this.legacyContent = '';
    let app = document.getElementById('app');

    let isAuthenticated = false;
    if (!isEmpty(window.user)) {
      console.log('APP.vue: is logged in as ' + window.user.username);
      this.user = window.user;
      isAuthenticated = true;
    }

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

    axios.interceptors.response.use(undefined, (err) => {
      //console.log('interceptor');console.log(err.response.status);

      return new Promise(() => {
        // Unauthorized.
        if (401 === err.response.status) {
          // Redirect to the login if status 401.
          //this.$router.replace({path: "/login"}).catch(()=>{});
          // Real redirect to avoid loops with Login.vue page.
          //window.location.href = '/login';
            this.showMessage(err.response.data.error, 'warning');
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
    //console.log('App.vue mounted');
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
