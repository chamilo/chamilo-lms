<template>
  <q-card
      v-if="announcement"
      elevation="4"
  >
    <q-card-section>
      <div class="text-h6">
        {{ announcement.title }}
      </div>
    </q-card-section>

    <q-card-section>
        <p v-html="announcement.content"/>
    </q-card-section>

    <q-card-actions v-if="isAdmin">
      <q-btn flat label="Edit" color="primary" v-close-popup @click="handleAnnouncementClick(announcement)"/>
    </q-card-actions>
  </q-card>
</template>

<script>

import {mapGetters, useStore} from "vuex";
import {useRouter} from "vue-router";
import {reactive, toRefs} from "vue";

export default {
  name: 'SystemAnnouncementCard',
  props: {
    announcement: Object,
  },
  setup() {
    const router = useRouter();
    const state = reactive({
      handleAnnouncementClick: function(announcement) {
        router
            .push({path: `/main/admin/system_announcements.php?`, query: {id: announcement['id'], action: 'edit'}})
            .catch(() => {
            });
      }
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
