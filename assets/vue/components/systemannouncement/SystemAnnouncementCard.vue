<template>
  <BaseCard plain>
    <template #header>
      <div class="-mb-2 flex items-center justify-between gap-2 bg-gray-15 px-4 py-2">
        <h6 v-text="announcement.title" />

        <BaseButton
          v-if="isAdmin"
          icon="edit"
          label="Edit"
          type="black"
          @click="handleAnnouncementClick(announcement)"
        />
      </div>
    </template>

    <div v-html="announcement.content" />
  </BaseCard>
</template>

<script>

import {mapGetters, useStore} from "vuex";
import {useRouter} from "vue-router";
import {reactive, toRefs} from "vue";
import BaseButton from "../basecomponents/BaseButton.vue";
import BaseCard from "../basecomponents/BaseCard.vue"

export default {
  name: 'SystemAnnouncementCard',
  components: { BaseCard, BaseButton },
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
