<template>
  <!--        :handle-submit="onSendMessageForm"-->
  <Toolbar
      :handle-send="onSendMessageForm"
  />

  <MessageForm
    ref="createForm"
    :values="item"
    :errors="violations"
  >
   <div v-if="item.originalSender">
     To: {{ item.originalSender.username }}
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

const { mapFields } = createHelpers({
  getterType: 'message/getField',
  mutationType: 'message/updateField'
});

export default {
  name: 'MessageReply',
  servicePrefix,
  mixins: [CreateMixin],
  components: {
    Loading,
    Toolbar,
    MessageForm
  },
  setup () {
    const item = ref({});
    const isLoadingSelect = ref(false);
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

      delete item.value['@id'];
      delete item.value['id'];
      delete item.value['firstReceiver'];
      //delete item.value['receivers'];
      delete item.value['sendDate'];

      item.value['originalSender'] = item.value['sender'];
      // New sender.
      item.value['sender'] = currentUser.value['@id'];

      // Set new receivers, will be loaded by onSendMessageForm()
      item.value['receivers'] = [];
      item.value['receivers'][0] = item.value['originalSender'];

      // Set reply content.
      item.value.content = `<br /><blockquote>${item.value.content}</blockquote>`;
    });

    return {v$: useVuelidate(), isLoadingSelect, item};
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
