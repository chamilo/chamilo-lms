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
        v-model="item.tags"
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

    <p class="text-lg">
      From:
      <q-avatar size="32px">
        <img :src="item['sender']['illustrationUrl'] + '?w=80&h=80&fit=crop'" />
        <!--              <q-icon name="person" ></q-icon>-->
      </q-avatar>
      {{ item['sender']['username'] }}
    </p>

    <p class="text-lg">
      {{ $luxonDateTime.fromISO(item['sendDate']).toRelative() }}
    </p>

    <p class="text-lg">
      <h3>{{ item.title }}</h3>
    </p>

    <div class="flex flex-row">
      <div class="w-full">
        <p v-html="item.content" />
      </div>
    </div>
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
import {computed, ref} from "vue";
import isEmpty from "lodash/isEmpty";
import axios from "axios";
import {ENTRYPOINT} from "../../config/entrypoint";
import useVuelidate from "@vuelidate/core";
import {useRoute, useRouter} from "vue-router";
import NotificationMixin from "../../mixins/NotificationMixin";

const servicePrefix = 'Message';

export default {
  name: 'MessageShow',
  components: {
      Loading,
      Toolbar,
      VueMultiselect
  },
  setup () {
    const tags = ref([]);
    const isLoadingSelect = ref(false);
    const store = useStore();
    const user = store.getters["security/getUser"];
    const find = store.getters["message/find"];
    const route = useRoute();
    const router = useRouter();

    let id = route.params.id;
    if (isEmpty(id)) {
      id = route.query.id;
    }

    console.log(id);
    console.log(decodeURIComponent(id));

    let item = find(decodeURIComponent(id));

    // Change to read
    if (false === item.read) {
      axios.put(ENTRYPOINT + 'messages/' + item.id, {
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
        //this.showMessage('Added');
        item.tags.push(response.data);
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
      item.tags.forEach(tagItem => {
        tagsToUpdate.push(tagItem['@id']);
      });
      tagsToUpdate.push(newTag['@id']);
      console.log(tagsToUpdate);

      axios.put(ENTRYPOINT + 'messages/' + item.id, {
        tags: tagsToUpdate,
      }).then(response => {
        //this.showMessage('Added');
        console.log(response);
        isLoadingSelect.value = false;
      }).catch(function (error) {
        isLoadingSelect.value = false;
        console.log(error);
      });
    }

    function removeTagFromMessage() {
      let tagsToUpdate = [];
      item.tags.forEach(tagItem => {
        tagsToUpdate.push(tagItem['@id']);
      });

      axios.put(ENTRYPOINT + 'messages/' + item.id, {
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
      v$: useVuelidate(), tags, isLoadingSelect, item,
      addTag,
      addTagToMessage,
      removeTagFromMessage,
      asyncFind,
      reply,
      createEvent
    };
  },
  mixins: [ShowMixin, NotificationMixin],
  computed: {
    ...mapFields('message', {
      isLoading: 'isLoading'
    }),
    ...mapGetters('message', ['find']),
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
