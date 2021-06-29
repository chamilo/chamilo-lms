<template>
  <div>
    <Toolbar
      v-if="item"
      :handle-delete="del"
    >
      <template slot="left">
<!--        <v-toolbar-title v-if="item">-->
<!--          {{-->
<!--            `${$options.servicePrefix} ${item['@id']}`-->
<!--          }}-->
<!--        </v-toolbar-title>-->
      </template>
    </Toolbar>

    <p class="text-lg" v-if="item">
      From:
      <q-avatar size="32px">
        <img :src="item['userSender']['illustrationUrl'] + '?w=80&h=80&fit=crop'" />
        <!--              <q-icon name="person" ></q-icon>-->
      </q-avatar>

      {{ item['userSender']['username'] }}
    </p>

    <p class="text-lg" v-if="item">
      {{$luxonDateTime.fromISO(item['sendDate']).toRelative() }}
    </p>

    <p class="text-lg" v-if="item">
      {{ item['title'] }}
    </p>

    <div v-if="item" class="flex flex-row">
      <div class="w-full">
        <h3>{{ item.title }} </h3>
        <p v-html="item.content" />
      </div>
    </div>



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
const servicePrefix = 'Message';

export default {
  name: 'MessageShow',
  components: {
      Loading,
      Toolbar,
  },
  mixins: [ShowMixin],
  computed: {
    ...mapFields('message', {
      isLoading: 'isLoading'
    }),
    ...mapGetters('message', ['find']),
    ...mapGetters({
      'isAuthenticated': 'security/isAuthenticated',
      'isAdmin': 'security/isAdmin',
    }),
  },
  methods: {
    ...mapActions('message', {
      deleteItem: 'del',
      reset: 'resetShow',
      retrieve: 'loadWithQuery'
    }),
  },
  servicePrefix
};
</script>
