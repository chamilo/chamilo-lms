<template>
  <q-form>
    <q-input
        id="item_title"
        v-model="item.title"
        :placeholder="$t('Title')"
        :error="v$.item.title.$error"
        @input="v$.item.title.$touch()"
        @blur="v$.item.title.$touch()"
        :error-message="titleErrors"
    />

    <div class="q-gutter-sm">
      <q-checkbox v-model="item.enabled" :label="$t('Enabled')"/>
    </div>

    <q-select
        v-model="item.category"
        :options="categories" :label="$t('Category')"
        option-value="id"
        option-label="title"
    />

    <q-select v-model="item.locale" :options="locales" :label="$t('Locale')"/>

    <TinyEditor
        id="item_content"
        v-model="item.content"
        required
        :init="{
          skin_url: '/build/libs/tinymce/skins/ui/oxide',
          content_css: '/build/css/editor.css',
          branding: false,
          relative_urls: false,
          height: 500,
          toolbar_mode: 'sliding',
          file_picker_callback : browser,
          autosave_ask_before_unload: true,
          plugins: [
            'fullpage advlist autolink lists link image charmap print preview anchor',
            'searchreplace visualblocks code fullscreen',
            'insertdatetime media table paste wordcount '
          ],
          toolbar: 'undo redo | bold italic underline strikethrough | insertfile image media template link | fontselect fontsizeselect formatselect | alignleft aligncenter alignright alignjustify | outdent indent |  numlist bullist | forecolor backcolor removeformat | pagebreak | charmap emoticons | fullscreen  preview save print | code codesample | ltr rtl | ' + extraPlugins,
        }
        "
    />
    <slot></slot>
  </q-form>
</template>

<script>
import has from 'lodash/has';
import useVuelidate from '@vuelidate/core';
import { required } from '@vuelidate/validators';
import {computed, ref} from "vue";
import {mapGetters, useStore} from "vuex";
import isEmpty from 'lodash/isEmpty';

export default {
  name: 'PageForm',
  setup () {
    let locales = ref([]);
    const store = useStore();

    let categories = ref([]);
    locales = window.languages.map(locale => locale.isocode);
    let allCategories = store.dispatch('pagecategory/findAll');

    allCategories.then((response) => {
      categories.value = response.map(function(data) {
        return data;
      })
    });

    return { v$: useVuelidate(), locales, categories}
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
      content: null,
      locale: null,
      enabled: true,
    };
  },
  computed: {
    ...mapGetters({
      'isAuthenticated': 'security/isAuthenticated',
      'currentUser': 'security/getUser',
    }),
    item() {
      if (this.values) {
        this.values.creator = this.currentUser['@id'];
        this.values.url = '/api/access_urls/' + window.access_url_id;
        if (!isEmpty(this.values.category)) {
          this.values.category = this.values.category['@id'];
        }
      }

      return this.initialValues || this.values;
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
  validations: {
    item: {
      title: {
        required,
      },
      enabled: {
        required,
      },
      content: {
        required,
      },
      locale: {
        required,
      },
    }
  }
};
</script>
