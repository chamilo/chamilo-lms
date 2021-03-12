<template>
  <b-form>
    <b-row>
      <b-col
        cols="12"
        sm="6"
        md="6"
      >
        <!--          v-model="item.uploadFile"-->
<!--        <b-form-file-->
<!--          ref="fileList"-->
<!--          multiple-->
<!--          @change="selectFile"-->
<!--        />-->
        <div class="input-group mb-3">
          <div class="custom-file">

            <input
                id="file_upload"
                type="file"
                class="custom-file-input"
                ref="fileList"
                multiple
                placeholder="File upload"
                @change="selectFile"
            />

            <label
                class="custom-file-label"
                for="file_upload"
                aria-describedby="File upload">
              Choose file
            </label>
          </div>
        </div>

        <div class="field">
          <div
              v-for="(file, index) in files"
              :key="index"
              :class="{ error : file.invalidMessage}"
          >
            <div>
              {{ file.name }}
              <span v-if="file.invalidMessage">
                - {{ file.invalidMessage }}
              </span>
              <span>
              <a @click.prevent="files.splice(index, 1)"
                 class="delete"
              >
                <font-awesome-icon icon="trash" />
              </a>
            </span>
            </div>
          </div>
        </div>
      </b-col>
    </b-row>
  </b-form>
</template>

<script>
import has from 'lodash/has';
import map from 'lodash/map';
import { validationMixin } from 'vuelidate';
import { required } from 'vuelidate/lib/validators';
import { mapActions } from 'vuex';
import { mapFields } from 'vuex-map-fields';

export default {
  name: 'DocumentsFormUpload',
  mixins: [validationMixin],
  props: {
    values: {
      type: Array,
      required: true
    },
    parentResourceNodeId: {
      type: Number
    },
    resourceLinkList: {
      type: String,
    },
    errors: {
      type: Object,
      default: () => {}
    }
  },
  data() {
    return {
      fileList:[],
      files: [],
    };
  },
  computed: {
    titleErrors() {
      const errors = [];
      if (!this.$v.item.title.$dirty) return errors;
      has(this.violations, 'title') && errors.push(this.violations.title);
      !this.$v.item.title.required && errors.push(this.$t('Field is required'));

      return errors;
    },
    violations() {
      return this.errors || {};
    }
  },
  methods: {
    selectFile() {
      const files = this.$refs.fileList.files;

      this.files = [
        ...this.files,
        ...map(files, file => ({
          name: file.name,
          size: file.size,
          type: file.type,
          filetype: 'file',
          parentResourceNodeId: this.parentResourceNodeId,
          resourceLinkList: this.resourceLinkList,
          uploadFile: file,
          invalidMessage: this.validate(file),
        }))
      ]
    },
    validate(file) {
      if (file) {
        return '';
      }

      return 'error';
    }
  },
  validations: {
    files: {}
  }
};
</script>
