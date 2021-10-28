<template>
  <q-page class="q-layout-padding">
    <div class="flex justify-center">
        <v-card
            elevation="2"
            v-for="announcement in announcements"
            :key="announcement.id"
        >
          <v-card-text>
            <h4>{{ announcement.title }}</h4>
            <p v-html="announcement.content" ></p>
          </v-card-text>

          <v-card-actions v-if="isAdmin">
            <q-btn flat label="Edit" color="primary" v-close-popup @click="handleAnnouncementClick(announcement)"/>
          </v-card-actions>

        </v-card>
    </div>

    <div v-if="pages"
         class="grid gap-4 grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 mt-2"
    >
      <div
          v-for="page in pages"
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

          <v-card-actions v-if="isAdmin">
            <q-btn flat label="Edit" color="primary" v-close-popup @click="handleClick(page)"/>
          </v-card-actions>
        </v-card>
      </div>
    </div>

  </q-page>
</template>

<script>

import axios from "axios";
import {reactive, toRefs} from 'vue'
import {ENTRYPOINT} from "../config/entrypoint";
import {mapGetters, useStore} from "vuex";
import {useRouter} from "vue-router";
import {useI18n} from "vue-i18n";

export default {
  name: "Home",
  setup() {
    const router = useRouter();
    const store = useStore();
    const state = reactive({
      announcements: [],
      pages: [],
      handleClick: function (page) {
        router
            .push({name: `PageUpdate`, params: {id: page['@id']}})
            .catch(() => {
            });
      },
      handleAnnouncementClick: function(announcement) {
        router
            .push({path: `/main/admin/system_announcements.php?`, query: {id: announcement['id'], action: 'edit'}})
            .catch(() => {
            });
      }
    });

    axios.get('/news/list').then(response => {
      if (Array.isArray(response.data)) {
        state.announcements = response.data;
      }
    }).catch(function (error) {
      console.log(error);
    });

    const { locale } = useI18n();

    let params = {
      'category.title' : 'home',
      'enabled' : '1',
      'locale':  locale.value
    }

    const pages = store.dispatch('page/findAll', params);
    pages.then((response) => {
      state.pages = response;
    });

    return toRefs(state);
  },
  computed: {
    ...mapGetters({
      'isAdmin': 'security/isAdmin',
    }),
  }
}
</script>