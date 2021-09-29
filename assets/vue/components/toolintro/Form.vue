<template>
  <q-form>
    <TinyEditor
        id="item_content"
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
            'insertdatetime media table paste wordcount emoticons ' + extraPlugins
          ],
          toolbar: 'undo redo | bold italic underline strikethrough | insertfile image media template link | fontselect fontsizeselect formatselect | alignleft aligncenter alignright alignjustify | outdent indent |  numlist bullist | forecolor backcolor removeformat | pagebreak | charmap emoticons | fullscreen  preview save print | code codesample | ltr rtl | ' + extraPlugins,
        }
        "
    />

  </q-form>
</template>

<script>
import useVuelidate from '@vuelidate/core';
import { required } from '@vuelidate/validators';
import {ref} from "vue";
import isEmpty from "lodash/isEmpty";

export default {
  name: 'ToolIntroForm',
  setup () {
    const config = ref([]);
    const extraPlugins = ref('');

    if (!isEmpty(window.config)) {
      config.value = window.config;
      if (config.value['editor.translate_html']) {
        extraPlugins.value = 'translatehtml';
      }
    }

    return { v$: useVuelidate(), extraPlugins }
  },
  props: {
    values: {
      type: Object,
      required: true
    },
    errors: {
      type: Object,
      default: () => {}
    },
    initialValues: {
      type: Object,
      default: () => {}
    },
  },
  data() {
    return {
      item: {
        content: null
      },
    };
  },
  computed: {
    item() {
      return this.initialValues || this.values;
    },
    violations() {
      return this.errors || {};
    }
  },
  methods: {
    browser (callback, value, meta) {
      //const route = useRoute();
      let nodeId = this.$route.params['node'];
      let folderParams = this.$route.query;
      let url = this.$router.resolve({ name: 'DocumentForHtmlEditor', params: { id: nodeId }, query: folderParams })
      url = url.fullPath;
      console.log(url);

      if (meta.filetype === 'image') {
        url = url + "&type=images";
      } else {
        url = url + "&type=files";
      }

      console.log(url);

      window.addEventListener('message', function (event) {
        var data = event.data;
        if (data.url) {
          url = data.url;
          console.log(meta); // {filetype: "image", fieldname: "src"}
          callback(url);
        }
      });


      tinymce.activeEditor.windowManager.openUrl({
        url: url,// use an absolute path!
        title: 'file manager',
        /*width: 900,
        height: 450,
        resizable: 'yes'*/
      }, {
        oninsert: function (file, fm) {
          var url, reg, info;

          // URL normalization
          url = fm.convAbsUrl(file.url);

          // Make file info
          info = file.name + ' (' + fm.formatSize(file.size) + ')';

          // Provide file and text for the link dialog
          if (meta.filetype === 'file') {
            callback(url, {text: info, title: info});
          }

          // Provide image and alt text for the image dialog
          if (meta.filetype === 'image') {
            callback(url, {alt: info});
          }

          // Provide alternative source and posted for the media dialog
          if (meta.filetype === 'media') {
            callback(url);
          }
        }
      });
      return false;
    },
  },
  validations: {
    item: {
      content: {
        //required,
      }
    }
  }
};
</script>
