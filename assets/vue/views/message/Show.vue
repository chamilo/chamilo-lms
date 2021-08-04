<template>
  <div v-if="item">
    <Toolbar
      :handle-delete="del"
    >
      <template v-slot:right>
<!--        <v-toolbar-title v-if="item">-->
<!--          {{-->
<!--            `${$options.servicePrefix} ${item['@id']}`-->
<!--          }}-->
<!--        </v-toolbar-title>-->
        <v-btn
            :loading="isLoading"
            tile
            icon
            @click="reply"
        >
          <v-icon icon="mdi-reply" />
        </v-btn>

        <v-btn
            :loading="isLoading"
            tile
            icon
            @click="replyAll"
        >
          <v-icon icon="mdi-reply-all" />
        </v-btn>

        <v-btn
            tile
            icon
            @click="createEvent"
        >
          <v-icon icon="mdi-calendar-plus" />
        </v-btn>
      </template>
    </Toolbar>

    <VueMultiselect
        placeholder="Tags"
        v-model="myReceiver.tags"
        :loading="isLoadingSelect"
        tag-placeholder="Add this as new tag"
        :options="tags"
        :multiple="true"
        :searchable="true"
        :internal-search="false"
        @search-change="asyncFind"

        @select="addTagToMessage"
        @remove="removeTagFromMessage"

        :taggable="true"
        @tag="addTag"
        label="tag"
        track-by="id"
    />

    <v-card
        elevation="2"
    >
      <v-card-header>
        <v-card-header-text>
          <v-card-title>
            {{ item.title }}
          </v-card-title>
        </v-card-header-text>
      </v-card-header>

      <v-card-subtitle>
        <p class="text-base" v-if="item.sender">
          <q-avatar size="32px">
            <img :src="item.sender['illustrationUrl'] + '?w=80&h=80&fit=crop'" />
          </q-avatar>
          {{ item.sender['username'] }}
          {{ $luxonDateTime.fromISO(item['sendDate']).toRelative() }}
        </p>
      </v-card-subtitle>

      <v-card-text>
        <div v-if="item.receiversTo">
          {{ $t('To') }} :
          <v-chip v-for="receiver in item.receiversTo ">
            {{ receiver.receiver['username'] }}
          </v-chip>
        </div>

        <div v-if="item.receiversCc">
          {{ $t('Cc') }} :
          <v-chip v-for="receiver in item.receiversCc ">
            {{ receiver.receiver['username'] }}
          </v-chip>
        </div>

        <div class="flex flex-row">
          <div class="w-full">
            <p v-html="item.content" />
          </div>
        </div>

      </v-card-text>

    </v-card>
    <Loading :visible="isLoading" />
  </div>
</template>

<style src="vue-multiselect/dist/vue-multiselect.css"></style>

<script>
import {mapActions, mapGetters, useStore} from 'vuex';
import { mapFields } from 'vuex-map-fields';
import Loading from '../../components/Loading.vue';
import ShowMixin from '../../mixins/ShowMixin';
import Toolbar from '../../components/Toolbar.vue';
import VueMultiselect from 'vue-multiselect'
import {computed, onMounted, ref} from "vue";
import isEmpty from "lodash/isEmpty";
import axios from "axios";
import {ENTRYPOINT} from "../../config/entrypoint";
import useVuelidate from "@vuelidate/core";
import {useRoute, useRouter} from "vue-router";
import NotificationMixin from "../../mixins/NotificationMixin";
import useNotification from '../../components/Notification.js';

const servicePrefix = 'Message';

export default {
  name: 'MessageShow',
  components: {
      Loading,
      Toolbar,
      VueMultiselect
  },
  mixins: [ShowMixin, NotificationMixin],
  setup () {
    const item = ref({});
    const tags = ref([]);
    const isLoadingSelect = ref(false);
    const store = useStore();
    const user = store.getters["security/getUser"];
    const find = store.getters["message/find"];
    const route = useRoute();
    const router = useRouter();
    const {showNotification} = useNotification();
    const myReceiver = ref([]);

    let id = route.params.id;
    if (isEmpty(id)) {
      id = route.query.id;
    }

    onMounted(async () => {
      const response = await store.dispatch('message/load', id);

      item.value = await response;

      item.value.receivers.forEach(receiver => {
        if (receiver.receiver['@id'] === user['@id']) {
          myReceiver.value = receiver;
        }
      });
    });

    // Change to read
    if (false === myReceiver.value.read) {
      axios.put(myReceiver.value['@id'], {
        read: true,
      }).then(response => {
        console.log(response);
      }).catch(function (error) {
        console.log(error);
      });
    }

    function addTag(newTag) {
      axios.post(ENTRYPOINT + 'message_tags', {
        user: user['@id'],
        tag: newTag,
      }).then(response => {
        addTagToMessage(response.data);
        myReceiver.value.tags.push(response.data);
        console.log(response);
        isLoadingSelect.value = false;
      }).catch(function (error) {
        isLoadingSelect.value = false;
        console.log(error);
      });
    }

    function addTagToMessage(newTag) {
      console.log('addTagToMessage');
      let tagsToUpdate = [];
      myReceiver.value.tags.forEach(tagItem => {
        tagsToUpdate.push(tagItem['@id']);
      });
      tagsToUpdate.push(newTag['@id']);
      console.log(tagsToUpdate);

      axios.put(myReceiver.value['@id'], {
        tags: tagsToUpdate,
      }).then(response => {
        showNotification('Tag added');
        console.log(response);
        isLoadingSelect.value = false;
      }).catch(function (error) {
        isLoadingSelect.value = false;
        console.log(error);
      });
    }

    function removeTagFromMessage() {
      let tagsToUpdate = [];
      myReceiver.value.tags.forEach(tagItem => {
        tagsToUpdate.push(tagItem['@id']);
      });

      axios.put(myReceiver.value['@id'], {
        tags: tagsToUpdate,
      }).then(response => {
        console.log(response);
        isLoadingSelect.value = false;
      }).catch(function (error) {
        isLoadingSelect.value = false;
        console.log(error);
      });
    }

    axios.get(ENTRYPOINT + 'message_tags', {
      params: {
        user: user['@id']
      }
    }).then(response => {
      isLoadingSelect.value = false;
      let data = response.data;
      tags.value = data['hydra:member'];
    });

    function reply() {
      let params = route.query;
      router.push({name: `${servicePrefix}Reply`, query: params});
    }

    function replyAll() {
      let params = route.query;
      params['all'] = 1;
      router.push({name: `${servicePrefix}Reply`, query: params});
    }

    function createEvent() {
      let params = route.query;
      router.push({name: `CCalendarEventCreate`, query: params});
    }

    function asyncFind (query) {
      if (query.toString().length < 3) {
        return;
      }

      isLoadingSelect.value = true;
      axios.get(ENTRYPOINT + 'message_tags', {
        params: {
          user: user['@id']
        }
      }).then(response => {
        isLoadingSelect.value = false;
        let data = response.data;
        tags.value = data['hydra:member'];

      }).catch(function (error) {
        isLoadingSelect.value = false;
        console.log(error);
      });
    }

    return {
      v$: useVuelidate(),
      tags,
      isLoadingSelect,
      item,
      addTag,
      addTagToMessage,
      removeTagFromMessage,
      asyncFind,
      reply,
      replyAll,
      createEvent,
      myReceiver
    };
  },
  computed: {
    ...mapFields('message', {
      isLoading: 'isLoading'
    }),
    //...mapGetters('message', ['find']),
    ...mapGetters({
      'isAuthenticated': 'security/isAuthenticated',
      'isAdmin': 'security/isAdmin',
      'currentUser': 'security/getUser',
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
