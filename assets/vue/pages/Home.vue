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
  </q-page>
</template>

<script>

import {useRouter} from "vue-router";
import {useStore} from "vuex";
import axios from "axios";
import {reactive, toRefs} from 'vue'
import {mapGetters} from "vuex";
import {ENTRYPOINT} from "../config/entrypoint";

export default {
  name: "Home",
  setup() {
    const router = useRouter();
    const store = useStore();
    const state = reactive({
      announcements: [],
    });

    axios.get(ENTRYPOINT+'news/list').then(response => {
      console.log(response.data);
      console.log(response);
      if (Array.isArray(response.data)) {
        state.announcements = response.data;
      }
    }).catch(function (error) {
      console.log(error);
    });

    return toRefs(state);
  }
}
</script>