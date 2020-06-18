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

          <ckeditor :editor="editor" v-model="item.content" :config="editorConfig">
          </ckeditor>

<!--          <v-textarea-->
<!--                  v-model="item.content"-->
<!--                  :label="$t('Text')"-->
<!--                  value=""-->
<!--          ></v-textarea>-->
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
import ClassicEditor from '@ckeditor/ckeditor5-build-classic';
import UploadAdapter from './UploadAdapter';

export default {
  name: 'DocumentsForm',
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
      editor: ClassicEditor,
      //editor:decoupleEditor,
      editorData: '',
      editorConfig: {
        allowedContent: true,
        extraPlugins: [this.uploader],
        // The configuration of the rich-text editor.
      }
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
