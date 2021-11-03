<template>
  <q-card
      v-if="page"
      elevation="4"
  >
    <q-card-section>
      <div class="text-h6">{{ page.title }}</div>
    </q-card-section>

    <q-card-section>
        <p v-html="page.content"/>
    </q-card-section>

    <q-card-actions v-if="isAdmin">
      <q-btn flat label="Edit" color="primary" v-close-popup @click="handleClick(page)"/>
    </q-card-actions>
  </q-card>
</template>

<script>

import {mapGetters, useStore} from "vuex";
import {useRouter} from "vue-router";
import {reactive, toRefs} from "vue";

export default {
  name: 'PageCard',
  props: {
    page: Object,
  },
  setup() {
    const router = useRouter();
    const state = reactive({
      handleClick: function (page) {
        router
            .push({name: `PageUpdate`, params: {id: page['@id']}})
            .catch(() => {
            });
      },
    });
    return toRefs(state);
  },
  computed: {
    ...mapGetters({
      'isAdmin': 'security/isAdmin',
    }),
  }
};
</script>
