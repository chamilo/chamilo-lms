<template>
  <!--        :handle-submit="onSendMessageForm"-->
  <Toolbar
      :handle-send="onReplyMessageForm"
  />

  <MessageForm
    ref="createForm"
    :values="item"
    :errors="violations"
  >
   <div v-if="item.originalSender">
     To: <v-chip>{{ item.originalSender.username }}</v-chip>
   </div>

    <div v-if="item.receiversCc">
<!--        <VueMultiselect-->
<!--            id="cc"-->
<!--            placeholder="Cc"-->
<!--            v-model="item.receiversCc"-->
<!--            :options="usersCc"-->
<!--            :multiple="true"-->
<!--            :searchable="false"-->
<!--            :internal-search="false"-->
<!--            limit-text="3"-->
<!--            limit="3"-->
<!--            label="username"-->
<!--            track-by="id"-->
<!--        />-->

        Cc:
      <span v-for="messageRelUser in item.receiversCc">
        <v-chip>
          {{ messageRelUser.receiver.username }}
        </v-chip>
      </span>
    </div>

  <TinyEditor
      v-model="item.content"
      required
      :init="{
        skin_url: '/build/libs/tinymce/skins/ui/oxide',
        content_css: '/build/libs/tinymce/skins/content/default/content.css',
        branding: false,
        relative_urls: false,
        height: 500,
        toolbar_mode: 'sliding',
        file_picker_callback : browser,
        autosave_ask_before_unload: true,
        plugins: [
          'fullpage advlist autolink lists link image charmap print preview anchor',
          'searchreplace visualblocks code fullscreen',
          'insertdatetime media table paste wordcount emoticons'
        ],
        toolbar: 'undo redo | bold italic underline strikethrough | insertfile image media template link | fontselect fontsizeselect formatselect | alignleft aligncenter alignright alignjustify | outdent indent |  numlist bullist | forecolor backcolor removeformat | pagebreak | charmap emoticons | fullscreen  preview save print | code codesample | ltr rtl',
      }
      "
  />
  </MessageForm>
  <Loading :visible="isLoading" />

</template>
<style src="vue-multiselect/dist/vue-multiselect.css"></style>
<script>
import {mapActions, mapGetters, useStore} from 'vuex';
import { createHelpers } from 'vuex-map-fields';
import {computed, onMounted, reactive} from "vue";
import MessageForm from '../../components/message/Form.vue';
import Loading from '../../components/Loading.vue';
import Toolbar from '../../components/Toolbar.vue';
import CreateMixin from '../../mixins/CreateMixin';
import {ref} from "vue";
import isEmpty from "lodash/isEmpty";
import axios from "axios";
import {ENTRYPOINT} from "../../config/entrypoint";
import useVuelidate from "@vuelidate/core";
import {useRoute, useRouter} from "vue-router";
const servicePrefix = 'Message';

import VueMultiselect from 'vue-multiselect'
const { mapFields } = createHelpers({
  getterType: 'message/getField',
  mutationType: 'message/updateField'
});

export default {
  name: 'MessageReply',
  servicePrefix,
  mixins: [CreateMixin],
  components: {
    VueMultiselect,
    Loading,
    Toolbar,
    MessageForm
  },
  setup () {
    const usersCc = ref([]);
    const item = ref({});
    const isLoadingSelect = ref(false);
    const store = useStore();
    const route = useRoute();
    const router = useRouter();

    let id = route.params.id;
    if (isEmpty(id)) {
      id = route.query.id;
    }

    let replyAll = '1' === route.query['all'];

    onMounted(async () => {
      const currentUser = computed(() => store.getters['security/getUser']);
      const response = await store.dispatch('message/load', id);

      item.value = await response;

      delete item.value['@id'];
      delete item.value['id'];
      delete item.value['firstReceiver'];
      //delete item.value['receivers'];
      delete item.value['sendDate'];

      item.value['originalSender'] = item.value['sender'];
      // New sender.
      item.value['sender'] = currentUser.value['@id'];

      // Set new receivers, will be loaded by onSendMessageForm()
      if (replyAll) {
        item.value.receiversTo.forEach(user => {
          // Dont' add original sender.
          if (item.value['originalSender']['@id'] === user.receiver['@id']) {
              return;
          }
          // Dont' add the current user.
          if (currentUser.value['@id'] === user.receiver['@id']) {
            return;
          }
          item.value.receiversCc.push(user);
        });

        // Check that the original sender is not already in the Cc.
        item.value.receiversCc.forEach(function (user, index, obj) {
          if (item.value['originalSender']['@id'] === user.receiver['@id']) {
            obj.splice(index, 1);
          }
        });

        /*item.value.receiversTo.forEach(function (user, index, obj) {
          if (currentUser.value['@id'] === user.receiver['@id']) {
            obj.splice(index, 1);
          }
        });*/

      } else {
        item.value['receivers'] = [];
        item.value['receiversTo'] = null;
        item.value['receiversCc'] = null;
        item.value['receivers'][0] = item.value['originalSender'];
      }

      /*console.log('-----------------------');
      console.log(item.value.receiversCc);
      if (item.value.receiversCc) {
        item.value.receiversCc.forEach(user => {
          console.log(user);
          // Send to inbox
          usersCc.value.push(user.receiver);
        });
      }*/

      // Set reply content.
      item.value.content = `<br /><blockquote>${item.value.content}</blockquote>`;
    });

    return {v$: useVuelidate(), isLoadingSelect, usersCc, item};
  },
  computed: {
    ...mapFields(['error', 'isLoading', 'created', 'violations']),
    ...mapGetters({
      'isAuthenticated': 'security/isAuthenticated',
      'currentUser': 'security/getUser',
    }),
  },
  methods: {
    ...mapActions('message', ['create', 'reset'])
  }
};
</script>
