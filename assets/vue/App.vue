<template>
  <div>
    <transition
      name="fade"
      mode="out-in"
      appear
    >
      <Header />
    </transition>

    <Sidebar />

    <main role="main">
      <b-container fluid>
        <b-row>
          <b-col cols="12">
            <Breadcrumb />
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
  </div>
</template>
<style>
</style>
<script>

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

    if (this.$parent.$el.attributes["data-messages"]) {
      let messages = JSON.parse(this.$parent.$el.attributes["data-messages"].value);
      if (messages) {
        Array.from(messages).forEach(element =>
          this.showMessage(element)
        );
      }
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