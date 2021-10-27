<template>
  <q-page class="q-layout-padding">
    <div class="flex justify-center">
        <div
            v-for="announcement in announcements"
            :key="announcement.id"
        >
          <h4>{{ announcement.title }}</h4>
          <p v-html="announcement.content" ></p>
        </div>
    </div>

    <div v-if="pages"
         class="grid gap-4 grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 mt-2"
    >
      <div
          v-for="page in pages"
          :key="page.id"
      >
        <v-card
            elevation="2"
        >
          <v-card-text>
            <p class="text-h5 text--primary">
              {{ page.title }}
            </p>
            <p v-html="page.content"/>

          </v-card-text>
        </v-card>
      </div>
    </div>

  </q-page>
</template>

<script>

import axios from "axios";
import {reactive, toRefs} from 'vue'
import {ENTRYPOINT} from "../config/entrypoint";

export default {
  name: "Home",
  setup() {
    const state = reactive({
      announcements: [],
      pages: [],
    });

    axios.get('/news/list').then(response => {
      if (Array.isArray(response.data)) {
        state.announcements = response.data;
      }
    }).catch(function (error) {
      console.log(error);
    });

    axios.get(ENTRYPOINT + 'pages.json?category.title=home&enabled=1').then(response => {
      if (Array.isArray(response.data)) {
        state.pages = response.data;
      }
    }).catch(function (error) {
      console.log(error);
    });

    return toRefs(state);
  }
}
</script>