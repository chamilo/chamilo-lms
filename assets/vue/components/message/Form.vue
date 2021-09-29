<template>
  <q-form>
    <v-container>
      <v-row>
        <v-col md="9">
          <q-input
            id="item_title"
            v-model="item.title"
            :error="v$.item.title.$error"
            :error-message="titleErrors"
            :placeholder="$t('Title')"
            @blur="v$.item.title.$touch()"
            @input="v$.item.title.$touch()"
          />
          <slot></slot>
        </v-col>
        <v-col md="3">
          <div v-if="item.attachments && item.attachments.length > 0">
            <div class="text-h6" v-text="$t('Atachments')"/>
            <ul>
              <li
                v-for="(attachment, index) in item.attachments"
                :key="index"
                class="my-2"
              >
                <audio
                  v-if="attachment.type.indexOf('audio') === 0"
                  class="max-w-full"
                  controls
                >
                  <source
                    :src="URL.createObjectURL(attachment)"
                  >
                </audio>
              </li>
            </ul>

            <hr class="my-2">
          </div>

          <AudioRecorder
            @attach-audio="attachAudios"
          />
        </v-col>
      </v-row>
    </v-container>
  </q-form>
</template>

<script>
import has from 'lodash/has';
import useVuelidate from '@vuelidate/core';
import {required} from '@vuelidate/validators';
import AudioRecorder from "../AudioRecorder";

export default {
  name: 'MessageForm',
  components: {AudioRecorder},
  setup() {
    return {v$: useVuelidate(), URL}
  },
  props: {
    values: {
      type: Object,
      required: true
    },
    errors: {
      type: Object,
      default: () => {
      }
    },
    initialValues: {
      type: Object,
      default: () => {
      }
    },
  },
  data() {
    return {
      title: null,
      parentResourceNodeId: null,
      receiversTo: [],
      receiversCc: [],
    };
  },
  computed: {
    item() {
      return this.initialValues || this.values;
    },
    receiversErrors() {
      const errors = [];
      if (!this.v$.item.receiversTo.$dirty) return errors;
      has(this.violations, 'receiversTo') && errors.push(this.violations.receiversTo);

      if (this.v$.item.receiversTo.required) {
        return this.$t('Field is required')
      }

      return errors;
    },
    titleErrors() {
      const errors = [];
      if (!this.v$.item.title.$dirty) return errors;
      has(this.violations, 'title') && errors.push(this.violations.title);

      if (this.v$.item.title.required) {
        return this.$t('Field is required')
      }

      return errors;
    },

    violations() {
      return this.errors || {};
    }
  },
  methods: {
    attachAudios(audio) {
      if (!this.item.attachments) {
        this.item.attachments = [];
      }

      this.item.attachments.push(audio);
    }
  },
  validations: {
    item: {
      title: {
        required,
      },
      receiversTo: {
        required,
      },
      /*receiversCc: {
        required,
      },*/
      content: {
        required,
      },
    }
  }
};
</script>
