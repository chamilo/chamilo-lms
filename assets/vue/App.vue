<template>
  <div class="d-flex flex-column h-100">
    <transition
      name="fade"
      mode="out-in"
      appear
    >
      <Header />
    </transition>

    <Sidebar />

    <main
      role="main"
      class="flex-shrink-0"
    >
      <b-container fluid>
        <b-row>
          <b-col cols="12">
            <Breadcrumb :legacy="breadcrumb" />
            <snackbar />
            <router-view />
            <div
              id="legacy_content"
              v-html="legacy_content"
            />
          </b-col>
        </b-row>
      </b-container>
    </main>

    <Footer />
  </div>
</template>
<style>
</style>
<script>

import { mapGetters } from 'vuex';

import NotificationMixin from './mixins/NotificationMixin';
import Breadcrumb from './components/Breadcrumb';
import Snackbar from './components/Snackbar';
import axios from "axios";

import Header from "./components/layout/Header";
import Sidebar from "./components/layout/Sidebar";
import Footer from "./components/layout/Footer";

export default {
  name: "App",
  components: {
    Header,
    Sidebar,
    Footer,
    Breadcrumb,
    Snackbar
  },

  mixins: [NotificationMixin],
  data: () => ({
    drawer: true,
    breadcrumb: [],
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
    ...mapGetters({
      'isAuthenticated': 'security/isAuthenticated',
      'isAdmin': 'security/isAdmin',
    }),
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

    if (this.$parent.$el.attributes["data-flashes"]) {
      let flashes = JSON.parse(this.$parent.$el.attributes["data-flashes"].value);
      if (flashes) {
        for (const key in flashes) {
          for (const text in flashes[key]) {
            this.showMessage(flashes[key][text], key);
          }
        }
      }
    }

    if (this.$parent.$el.attributes["data-breadcrumb"]) {
      this.breadcrumb = JSON.parse(this.$parent.$el.attributes["data-breadcrumb"].value);
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