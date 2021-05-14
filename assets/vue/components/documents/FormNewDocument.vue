<template>
  <q-form>
    <q-input
        id="item_title"
        v-model="item.title"
        :error="v$.item.title.$error"
        :error-message="titleErrors"
        :placeholder="$t('Title')"
        @input="v$.item.title.$touch()"
        @blur="v$.item.title.$touch()"
    />

    <TinyEditor
        id="item_content"
        v-if="(item.resourceNode && item.resourceNode.resourceFile && item.resourceNode.resourceFile.text) || item.newDocument"
        v-model="item.contentFile"
        :error-message="contentFileErrors"
        required
        :init="{
          skin_url: '/build/libs/tinymce/skins/ui/oxide',
          content_css: '/build/libs/tinymce/skins/content/default/content.css',
          branding: false,
          relative_urls: false,
          height: 500,
          toolbar_mode: 'sliding',
          file_picker_callback : browser,
      /*images_upload_handler: (blobInfo, success, failure) => {
              const img = 'data:image/jpeg;base64,' + blobInfo.base64();
              //console.log(img);
              success(img);
            },*/
          //menubar: true,
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
    <!-- For extra content-->
    <slot></slot>
  </q-form>
</template>

<script>
import has from 'lodash/has';
import useVuelidate from '@vuelidate/core';
import { required } from '@vuelidate/validators';
import { useRouter, useRoute } from 'vue-router'

export default {
  name: 'DocumentsForm',
  setup () {
    return { v$: useVuelidate() }
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
      title: null,
      contentFile: null,
      parentResourceNodeId: null,
      resourceNode: null,
    };
  },
  computed: {
    item() {
      return this.initialValues || this.values;
    },
    titleErrors() {
      const errors = [];

      /*if (!this.$v.item.title.$dirty) return errors;
      has(this.violations, 'title') && errors.push(this.violations.title);
      !this.$v.item.title.required && errors.push(this.$t('Field is required'));*/

      if (this.v$.item.title.required) {
        return this.$t('Field is required')
      }

      return errors;
    },
    contentFileErrors() {
      const errors = [];

      /*if (this.item.resourceNode && this.item.resourceNode.resourceFile && this.item.resourceNode.resourceFile.text) {
        if (!this.$v.item.contentFile.$dirty) return errors;
        has(this.violations, 'contentFile') && errors.push(this.violations.contentFile);
        !this.$v.item.contentFile.required && errors.push(this.$t('Content is required'));
      }*/

      return errors;
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
      let url = this.$router.resolve({ name: 'DocumentManager', params: { id: nodeId }, query: folderParams })
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
      title: {
        required,
      },
      contentFile: {
        //required,
      },
      parentResourceNodeId: {
      },
      resourceNode:{
      }
    }
  }
};
</script>
