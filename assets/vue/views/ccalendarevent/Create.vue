<template>
    <Toolbar
        :handle-send="onSendForm"
    />
    <CCalendarEventForm
      ref="createForm"
      :values="item"
      :errors="violations"
    />
    <Loading :visible="isLoading" />
</template>

<style src="vue-multiselect/dist/vue-multiselect.css"></style>

<script>
import {mapActions, mapGetters, useStore} from 'vuex';
import { createHelpers } from 'vuex-map-fields';
import CCalendarEventForm from '../../components/ccalendarevent/Form.vue';
import Loading from '../../components/Loading.vue';
import Toolbar from '../../components/Toolbar.vue';
import CreateMixin from '../../mixins/CreateMixin';
import {computed, onMounted, ref} from "vue";
import useVuelidate from "@vuelidate/core";
import {useRoute, useRouter} from "vue-router";
import isEmpty from "lodash/isEmpty";
import {RESOURCE_LINK_PUBLISHED} from "../../components/resource_links/visibility.js";
const servicePrefix = 'Message';

const { mapFields } = createHelpers({
  getterType: 'ccalendarevent/getField',
  mutationType: 'ccalendarevent/updateField'
});
//const { DateTime } = require("luxon");
export default {
  name: 'CCalendarEventCreate',
  servicePrefix,
  mixins: [CreateMixin],
  components: {
    CCalendarEventForm,
    Loading,
    Toolbar,
  },
  setup () {
    const users = ref([]);
    const isLoadingSelect = ref(false);
    const item = ref({});
    const store = useStore();
    const route = useRoute();
    const router = useRouter();

    let id = route.params.id;
    if (isEmpty(id)) {
      id = route.query.id;
    }

    onMounted(async () => {
      const response = await store.dispatch('message/load', id);
      const currentUser = computed(() => store.getters['security/getUser']);
      item.value = await response;

      // Remove unused properties:
      delete item.value['status'];
      delete item.value['msgType'];
      delete item.value['@type'];
      delete item.value['@context'];
      delete item.value['@id'];
      delete item.value['id'];
      delete item.value['firstReceiver'];
      //delete item.value['receivers'];
      delete item.value['sendDate'];

      item.value['parentResourceNodeId'] = currentUser.value.resourceNode['id'];
      //item.value['startDate'] = date.now();
      //item.value['endDate'] = new Date();
      //item.value['originalSender'] = item.value['sender'];
      // New sender.
      //item.value['sender'] = currentUser.value['@id'];

      // Set new receivers, will be loaded by onSendMessageForm()
      item.value['resourceLinkListFromEntity'] = [];
      let itemsAdded = [];
      item.value['receivers'].forEach(receiver => {
        // Skip current user.
        if (currentUser.value['@id'] === receiver.receiver['@id']) {
          return;
        }
        item.value.resourceLinkListFromEntity.push(
            {
              uid: receiver.receiver['id'],
              user: { username: receiver.receiver['username']},
              visibility: RESOURCE_LINK_PUBLISHED
            }
        );
        itemsAdded.push(receiver.receiver['username']);
      });

      // Sender is not added to the list.
      if (!itemsAdded.includes(item.value['sender']['username'])) {
        // Set the sender too.
        item.value['resourceLinkListFromEntity'].push(
            {
              uid: item.value['sender']['id'],
              user: {username: item.value['sender']['username']},
              visibility: RESOURCE_LINK_PUBLISHED
            }
        );
      }

      delete item.value['sender'];
    });

    return {v$: useVuelidate(), users, isLoadingSelect, item};
  },
  computed: {
    ...mapFields(['error', 'isLoading', 'created', 'violations']),
    ...mapGetters({
      'isAuthenticated': 'security/isAuthenticated',
      'currentUser': 'security/getUser',
    }),
  },
  methods: {
    ...mapActions('ccalendarevent', ['create', 'createWithFormData', 'reset'])
  }
};
</script>
