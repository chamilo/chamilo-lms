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
          icon
          tile
          @click="reply"
        >
          <v-icon icon="mdi-reply"/>
        </v-btn>

        <v-btn
          :loading="isLoading"
          icon
          tile
          @click="replyAll"
        >
          <v-icon icon="mdi-reply-all"/>
        </v-btn>

        <v-btn
          icon
          tile
          @click="createEvent"
        >
          <v-icon icon="mdi-calendar-plus"/>
        </v-btn>
      </template>
    </Toolbar>

    <VueMultiselect
      v-model="myReceiver.tags"
      :internal-search="false"
      :loading="isLoadingSelect"
      :multiple="true"
      :options="tags"
      :searchable="true"
      :taggable="true"
      label="tag"
      placeholder="Tags"

      tag-placeholder="Add this as new tag"
      track-by="id"

      @remove="removeTagFromMessage"
      @select="addTagToMessage"
      @tag="addTag"
      @search-change="asyncFind"
    />

    <q-card>
      <q-card-section>
        <div class="text-h6">
          {{ item.title }}
        </div>
        <div
          v-if="item.sender"
          class="text-subtitle2"
        >
          <q-avatar size="32px">
            <img :src="item.sender['illustrationUrl'] + '?w=80&h=80&fit=crop'"/>
          </q-avatar>
          {{ item.sender['username'] }}
          {{ $luxonDateTime.fromISO(item['sendDate']).toRelative() }}
        </div>
      </q-card-section>

      <q-card-section>
        <div
          v-if="item.receiversTo"
        >
          {{ $t('To') }} :
          <v-chip v-for="receiver in item.receiversTo">
            {{ receiver.receiver['username'] }}
          </v-chip>
        </div>

        <div v-if="item.receiversCc.length">
          {{ $t('Cc') }} :
          <v-chip v-for="receiver in item.receiversCc">
            {{ receiver.receiver['username'] }}
          </v-chip>
        </div>
      </q-card-section>

      <q-card-section>
        <div
          v-html="item.content"
        />
      </q-card-section>

      <q-card-section
        v-if="item.attachments && item.attachments.length > 0"
      >
        <q-separator/>

        <p class="my-3">
          {{ item.attachments.length }} {{ $t('Attachments') }}
        </p>

        <div class="q-gutter-y-sm q-gutter-x-sm row">
          <div
            v-for="(attachment, index) in item.attachments"
            :key="index"
          >
            <div
              v-if="attachment.resourceNode.resourceFile.audio"
            >
              <audio controls>
                <source :src="attachment.downloadUrl">
              </audio>
            </div>

            <q-btn
              v-else
              :href="attachment.downloadUrl"
              flat
              icon="attachment"
              type="a"
            >
              {{ attachment.resourceNode.resourceFile.originalName }}
            </q-btn>
          </div>
        </div>
      </q-card-section>
    </q-card>
    <Loading :visible="isLoading"/>
  </div>
</template>

<style src="vue-multiselect/dist/vue-multiselect.css"></style>

<script>
import {mapActions, mapGetters, useStore} from 'vuex';
import {mapFields} from 'vuex-map-fields';
import Loading from '../../components/Loading.vue';
import ShowMixin from '../../mixins/ShowMixin';
import Toolbar from '../../components/Toolbar.vue';
import VueMultiselect from 'vue-multiselect'
import {onMounted, ref} from "vue";
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
  setup() {
    const item = ref({});
    const tags = ref([]);
    const isLoadingSelect = ref(false);
    const store = useStore();
    const user = store.getters["security/getUser"];
    //const find = store.getters["message/find"];
    const route = useRoute();
    const router = useRouter();
    const {showNotification} = useNotification();
    const myReceiver = ref([]);

    let id = route.params.id;
    if (isEmpty(id)) {
      id = route.query.id;
    }

    // Set default empty.
    item.value.receiversCc = [];

    onMounted(async () => {
      item.value = await store.dispatch('message/load', id);

      myReceiver.value = item.value.receivers.find(({receiver}) => receiver['@id'] === user['@id']);

      // Change to read.
      if (false === myReceiver.value.read) {
        axios.put(myReceiver.value['@id'], {
          read: true,
        }).then(response => {
          console.log(response);
        }).catch(function (error) {
          console.log(error);
        });
      }
    });

    function addTag(newTag) {
      axios.post(ENTRYPOINT + 'message_tags', {
        user: user['@id'],
        tag: newTag,
      }).then(response => {
        addTagToMessage(response.data);
        myReceiver.value.tags.push(response.data);
        isLoadingSelect.value = false;
      }).catch(function (error) {
        isLoadingSelect.value = false;
        console.log(error);
      });
    }

    function addTagToMessage(newTag) {
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

    function asyncFind(query) {
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
