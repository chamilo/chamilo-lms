<template>
  <v-form>
    <v-container fluid>
      <v-row>
        <v-col cols="12" sm="6" md="6">
          <v-text-field
                  v-model="item.title"
                  :error-messages="titleErrors"
                  :label="$t('Title')"
                  required
                  @input="$v.item.title.$touch()"
                  @blur="$v.item.title.$touch()"
          />

          <editor
                  v-model="item.content"
                  :init="{
                    skin_url: '/build/libs/tinymce/skins/ui/oxide',
                    content_css: '/build/libs/tinymce/skins/content/default/content.css',
                  branding:false,
                  height: 500,
                  file_picker_callback: function(callback, value, meta) {
                    // Provide file and text for the link dialog
                    if (meta.filetype == 'file') {
                      callback('mypage.html', {text: 'My text'});
                    }

                    // Provide image and alt text for the image dialog
                    if (meta.filetype == 'image') {
                      callback('myimage.jpg', {alt: 'My alt text'});
                    }

                    // Provide alternative source and posted for the media dialog
                    if (meta.filetype == 'media') {
                      callback('movie.mp4', {source2: 'alt.ogg', poster: 'image.jpg'});
                    }
                  },
                  images_upload_handler: (blobInfo, success, failure) => {
                    console.log(blobInfo);
                    console.log(success);
                    console.log(failure);

                    const img = 'data:image/jpeg;base64,' + blobInfo.base64();
                    console.log(img);
                    success(img);
                  },
                   //menubar: true,
                   plugins: [
                     'advlist autolink lists link image charmap print preview anchor',
                     'searchreplace visualblocks code bbcode fullscreen',
                     'insertdatetime media table paste code help wordcount'
                   ],
                   toolbar: 'undo redo | bold italic underline strikethrough | fontselect fontsizeselect formatselect | alignleft aligncenter alignright alignjustify | outdent indent |  numlist bullist | forecolor backcolor removeformat | pagebreak | charmap emoticons | fullscreen  preview save print | insertfile image media template link anchor code codesample | ltr rtl',

                   /*toolbar:
                     'undo redo | formatselect | bold italic backcolor | \
                     alignleft aligncenter alignright alignjustify | \
                     bullist numlist outdent indent | removeformat'*/
                  }
              "
          />

        </v-col>
      </v-row>
    </v-container>
  </v-form>
</template>

<style>
  .ck-editor__editable {
    min-height: 400px;
   }
</style>

<script>
import has from 'lodash/has';
import { validationMixin } from 'vuelidate';
import { required } from 'vuelidate/lib/validators';
import { mapActions } from 'vuex';
import { mapFields } from 'vuex-map-fields';
//import ClassicEditor from '@ckeditor/ckeditor5-build-classic';
//import UploadAdapter from './UploadAdapter';
//import CKFinder from '@ckeditor/ckeditor5-ckfinder/src/ckfinder';
import Editor from '@tinymce/tinymce-vue'
import 'tinymce/tinymce'

import 'tinymce/icons/default'
import 'tinymce/themes/silver'

import 'tinymce/plugins/advlist'
import 'tinymce/plugins/anchor'
import 'tinymce/plugins/autolink'
import 'tinymce/plugins/autoresize'
import 'tinymce/plugins/autosave'
import 'tinymce/plugins/bbcode'
import 'tinymce/plugins/charmap'
import 'tinymce/plugins/code'
import 'tinymce/plugins/codesample'
import 'tinymce/plugins/colorpicker'
import 'tinymce/plugins/contextmenu'
import 'tinymce/plugins/directionality'
import 'tinymce/plugins/emoticons'
import 'tinymce/plugins/fullpage'
import 'tinymce/plugins/fullscreen'
import 'tinymce/plugins/help'
import 'tinymce/plugins/hr'
import 'tinymce/plugins/image'
import 'tinymce/plugins/imagetools'
import 'tinymce/plugins/importcss'
import 'tinymce/plugins/insertdatetime'
import 'tinymce/plugins/legacyoutput'
import 'tinymce/plugins/link'
import 'tinymce/plugins/lists'
import 'tinymce/plugins/media'
import 'tinymce/plugins/nonbreaking'
import 'tinymce/plugins/noneditable'
import 'tinymce/plugins/pagebreak'
import 'tinymce/plugins/paste'
import 'tinymce/plugins/preview'
import 'tinymce/plugins/print'
import 'tinymce/plugins/quickbars'
import 'tinymce/plugins/save'
import 'tinymce/plugins/searchreplace'
import 'tinymce/plugins/spellchecker'
import 'tinymce/plugins/tabfocus'
import 'tinymce/plugins/table'
import 'tinymce/plugins/template'
import 'tinymce/plugins/textcolor'
import 'tinymce/plugins/textpattern'
import 'tinymce/plugins/toc'
import 'tinymce/plugins/visualblocks'
import 'tinymce/plugins/visualchars'
import 'tinymce/plugins/wordcount'

export default {
  name: 'DocumentsForm',
   components: {
     'editor': Editor
   },
  mixins: [validationMixin],
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
  created () {
  },
  data() {
    return {
      title: null,
      content: null,
      parentResourceNodeId: null,
      // editor: ClassicEditor,
      // editorData: '',
      // editorConfig: {
      //   allowedContent: true,
      //   extraPlugins: [this.uploader],
      //   // ckfinder: {
      //   //   //uploadUrl: '/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Files&responseType=json',
      //   //   //openerMethod: 'popup'
      //   // }
      //   // The configuration of the rich-text editor.
      // }
    };
  },
  computed: {
    // eslint-disable-next-line
    item() {
      return this.initialValues || this.values;
    },
    titleErrors() {
      const errors = [];

      if (!this.$v.item.title.$dirty) return errors;
      has(this.violations, 'title') && errors.push(this.violations.title);
      !this.$v.item.title.required && errors.push(this.$t('Field is required'));

      return errors;
    },
    contentErrors() {
      const errors = [];

      if (!this.$v.item.content.$dirty) return errors;
      has(this.violations, 'content') && errors.push(this.violations.content);
      !this.$v.item.content.required && errors.push(this.$t('Field is required'));

      return errors;
    },
    violations() {
      return this.errors || {};
    }
  },
  methods: {
    uploader(editor)
    {
      editor.plugins.get( 'FileRepository' ).createUploadAdapter = ( loader ) => {
        return new UploadAdapter( loader );
      };
    },
  },
  validations: {
    item: {
      title: {
        required,
      },
      content: {
        required,
      },
      parentResourceNodeId: {
      },
    }
  }
};
</script>
