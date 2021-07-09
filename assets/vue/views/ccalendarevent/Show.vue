<template>
  <div>
    <Toolbar
      v-if="item"
      :handle-edit="editHandler"
      :handle-delete="del"
    >
    </Toolbar>

    <p class="text-lg" v-if="item">
      {{ item['title'] }}
    </p>

    <Loading :visible="isLoading" />
  </div>
</template>

<script>
import { mapActions, mapGetters } from 'vuex';
import { mapFields } from 'vuex-map-fields';
import Loading from '../../components/Loading.vue';
import ShowMixin from '../../mixins/ShowMixin';
import Toolbar from '../../components/Toolbar.vue';

import ShowLinks from "../../components/resource_links/ShowLinks.vue";
const servicePrefix = 'CCalendarEvent';

export default {
  name: 'CCalendarEventShow',
  components: {
      Loading,
      Toolbar,
      ShowLinks
  },
  mixins: [ShowMixin],
  computed: {
    ...mapFields('ccalendarevent', {
      isLoading: 'isLoading'
    }),
    ...mapGetters('ccalendarevent', ['find']),
    ...mapGetters({
      'isAuthenticated': 'security/isAuthenticated',
      'isAdmin': 'security/isAdmin',
      'isCurrentTeacher': 'security/isCurrentTeacher',
    }),
  },
  methods: {
    ...mapActions('ccalendarevent', {
      deleteItem: 'del',
      reset: 'resetShow',
      retrieve: 'loadWithQuery'
    }),
  },
  servicePrefix
};
</script>
