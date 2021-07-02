<template>
  <div>

    <!--        :handle-submit="onSendMessageForm"-->
    <Toolbar
        :handle-send="onSendMessageForm"
    />

    <DocumentsForm
      ref="createForm"
      :values="item"
      :errors="violations"
    >
      <VueMultiselect
          placeholder="To"
          v-model="item.receivers"
          :loading="isLoadingSelect"
          :options="users"
          :multiple="true"
          :searchable="true"
          :internal-search="false"
          @search-change="asyncFind"
          limit-text="3"
          limit="3"
          label="username"
          track-by="id"
      />

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
    </DocumentsForm>
    <Loading :visible="isLoading" />
  </div>
</template>
<style src="vue-multiselect/dist/vue-multiselect.css"></style>
<script>
import {mapActions, mapGetters, useStore} from 'vuex';
import { createHelpers } from 'vuex-map-fields';
import DocumentsForm from '../../components/documents/Form.vue';
import Loading from '../../components/Loading.vue';
import Toolbar from '../../components/Toolbar.vue';
import CreateMixin from '../../mixins/CreateMixin';
import {ref} from "vue";
import isEmpty from "lodash/isEmpty";
import axios from "axios";
import {ENTRYPOINT} from "../../config/entrypoint";
import useVuelidate from "@vuelidate/core";
import VueMultiselect from 'vue-multiselect'
const servicePrefix = 'Message';

const { mapFields } = createHelpers({
  getterType: 'message/getField',
  mutationType: 'message/updateField'
});

export default {
  name: 'MessageCreate',
  servicePrefix,
  mixins: [CreateMixin],
  components: {
    Loading,
    Toolbar,
    DocumentsForm,
    VueMultiselect
  },
  setup () {
    const users = ref([]);
    const isLoadingSelect = ref(false);

    function asyncFind (query) {
      if (query.toString().length < 3) {
        return;
      }

      isLoadingSelect.value = true;
      axios.get(ENTRYPOINT + 'users', {
        params: {
          username: query
        }
      }).then(response => {
        isLoadingSelect.value = false;
        let data = response.data;
        users.value = data['hydra:member'];
      }).catch(function (error) {
        isLoadingSelect.value = false;
        console.log(error);
      });
    }

    return {v$: useVuelidate(), users, asyncFind, isLoadingSelect};
  },
  data() {
    return {
      item: {},
    };
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
