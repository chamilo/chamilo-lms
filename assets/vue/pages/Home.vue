<template>
  <q-page class="q-layout-padding">
    <div
        v-if="announcements.length"
    >
      <SystemAnnouncementCardList
          :announcements="announcements"
      />
    </div>

    <div
        v-if="pages.length"
        class="grid gap-4 grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-2 mt-2">
      <PageCardList
          :pages="pages"
      />
    </div>
  </q-page>
</template>

<script>

import axios from "axios";
import {reactive, toRefs} from 'vue'
import {mapGetters, useStore} from "vuex";
import {useRouter} from "vue-router";
import {useI18n} from "vue-i18n";
import PageCardList from "../components/page/PageCardList";
import SystemAnnouncementCardList from "../components/systemannouncement/SystemAnnouncementCardList";

export default {
  name: 'Home',
  components: {
    SystemAnnouncementCardList,
    PageCardList
  },
  setup() {
    const store = useStore();
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