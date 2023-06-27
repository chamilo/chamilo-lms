<template>
  <div class="grid grid-cols-3 gap-4">
    <div class="col-span-2">
      <BaseInputText
        id="item_title"
        v-model:error-text="v$.item.title.required.$message"
        v-model:is-invalid="v$.item.title.$invalid"
        v-model="v$.item.title.$model"
        :label="t('Title')"
      />

      <slot></slot>
    </div>

    <div>
      <div v-if="attachments && attachments.length > 0">
        <div v-t="'Attachments'" class="text-h6" />

        <ul>
          <li v-for="(attachment, index) in attachments" :key="index" class="my-2">
            <audio v-if="attachment.type.indexOf('audio') === 0" class="max-w-full" controls>
              <source :src="URL.createObjectURL(attachment)" />
            </audio>
          </li>
        </ul>

        <hr />
      </div>

      <AudioRecorder @attach-audio="attachAudios" />
    </div>
  </div>
</template>

<script>
import has from "lodash/has";
import useVuelidate from "@vuelidate/core";
import { required } from "@vuelidate/validators";
import AudioRecorder from "../AudioRecorder";
import BaseInputText from "../basecomponents/BaseInputText.vue";
import { useI18n } from "vue-i18n";

export default {
  name: 'MessageForm',
  components: { AudioRecorder, BaseInputText },
  emits: ['update:attachments'],
  setup() {
    const { t } = useI18n();

    return { v$: useVuelidate(), URL, t };
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
    attachments: {
      type: Array,
      required: false,
      default: () => [],
    }
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
      this.$emit('update:attachments', [...this.attachments, audio]);
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
