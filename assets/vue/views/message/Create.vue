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

      <!--          @input="v$.item.receiversTo.$touch()"-->

      <VueMultiselect
          id="to"
          placeholder="To"
          v-model="item.receiversTo"
          :loading="isLoadingSelect"
          :options="usersTo"
          :multiple="true"
          :searchable="true"
          :internal-search="false"
          @search-change="asyncFindTo"
          limit-text="3"
          limit="3"
          label="username"
          track-by="id"
          :allow-empty="false"

      />

      <VueMultiselect
          id="cc"
          placeholder="Cc"
          v-model="item.receiversCc"
          :loading="isLoadingSelect"
          :options="usersCc"
          :multiple="true"
          :searchable="true"
          :internal-search="false"
          @search-change="asyncFindCc"
          limit-text="3"
          limit="3"
          label="username"
          track-by="id"
          :allow-empty="true"
      />

      <!--          @filter-abort="abortFilterFn"-->
<!--      <q-select-->
<!--          filled-->
<!--          v-model="item.receivers"-->
<!--          use-input-->
<!--          use-chips-->
<!--          :options="users"-->
<!--          input-debounce="0"-->
<!--          label="Lazy filter"-->
<!--          @filter="asyncFind"-->
<!--          style="width: 250px"-->
<!--          hint="With use-chips"-->
<!--          :error-message="receiversErrors"-->
<!--      />-->
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
            'advlist autolink lists link image charmap print preview anchor',
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
import MessageForm from '../../components/message/Form.vue';
import Loading from '../../components/Loading.vue';
import Toolbar from '../../components/Toolbar.vue';
import CreateMixin from '../../mixins/CreateMixin';
import {ref} from "vue";
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
    MessageForm,
    VueMultiselect
  },
  setup () {
    const usersTo = ref([]);
    const usersCc = ref([]);
    const isLoadingSelect = ref(false);

    function asyncFind (query) {
      if (query.toString().length < 3) {
        throw new Error('error');
      }

      isLoadingSelect.value = true;
      return axios.get(ENTRYPOINT + 'users', {
        params: {
          username: query
        }
      }).then(response => {
        isLoadingSelect.value = false;
        let data = response.data;

        return data['hydra:member'];
      }).catch(function (error) {
        isLoadingSelect.value = false;
        console.log(error);
      });
    }

    function asyncFindTo(query) {
      try {
        asyncFind(query).then(users => {
          usersTo.value = users;
        });
      } catch (e) {
      }
    }

    function asyncFindCc(query) {
      try {
        asyncFind(query).then(users => {
          usersCc.value = users;
        });
      } catch (e) {
      }
    }

    return {v$: useVuelidate(), usersTo, usersCc, asyncFindTo, asyncFindCc, isLoadingSelect};
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
