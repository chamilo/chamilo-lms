<template>
  <form action="#">
    <div class="field">
      <div class="p-float-label">
        <PrimeInputText
          id="item_title"
          v-model="v$.item.title.$model"
          :class="{ 'p-invalid': v$.item.title.$invalid }"
        />
        <label
          v-t="'Title'"
          for="item_title"
          :class="{ 'p-error': v$.item.title.$invalid }"
        />
      </div>
      <small
        v-if="v$.item.title.$invalid || v$.item.title.$pending.$response"
        v-t="v$.item.title.required.$message"
        class="p-error"
      />
    </div>

    <div class="field-checkbox">
      <PrimeCheckbox
        v-model="item.enabled"
        :binary="true"
        input-id="enabled"
      />
      <label
        v-t="'Enabled'"
        for="enabled"
      />
    </div>

    <div class="field">
      <div class="p-float-label">
        <PrimeDropdown
          v-model="item.category"
          :options="categories"
          input-id="category"
          option-value="@id"
          option-label="title"
        />
        <label
          v-t="'Category'"
          for="category"
        />
      </div>
    </div>

    <div class="field">
      <div class="p-float-label">
        <PrimeDropdown
          v-model="item.locale"
          :options="locales"
          input-id="locale"
        />
        <label
          v-t="'Locale'"
          for="locale"
        />
      </div>
    </div>

    <div class="field">
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
            'insertdatetime media table paste wordcount '
          ],
          toolbar: 'undo redo | bold italic underline strikethrough | insertfile image media template link | fontselect fontsizeselect formatselect | alignleft aligncenter alignright alignjustify | outdent indent |  numlist bullist | forecolor backcolor removeformat | pagebreak | charmap emoticons | fullscreen  preview save print | code codesample | ltr rtl | ' + extraPlugins,
        }
        "
      />
    </div>
  </form>
</template>

<script>
import PrimeInputText from 'primevue/inputtext';
import PrimeCheckbox from 'primevue/checkbox';
import PrimeDropdown from 'primevue/dropdown';
import has from 'lodash/has';
import useVuelidate from '@vuelidate/core';
import { required } from '@vuelidate/validators';
import { ref } from "vue";
import {mapGetters, useStore} from "vuex";
import isEmpty from 'lodash/isEmpty';

export default {
  name: 'PageForm',
  servicePrefix: 'pages',
  components: {
    PrimeInputText,
    PrimeCheckbox,
    PrimeDropdown,
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
  setup () {
    let locales = ref([]);
    const store = useStore();

    let categories = ref([]);
    locales.value = window.languages.map(locale => locale.isocode);
    let allCategories = store.dispatch('pagecategory/findAll');

    allCategories.then((response) => {
      categories.value = response.map(function(data) {
        return data;
      })
    });

    return { v$: useVuelidate(), locales, categories, }
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
        if (!isEmpty(this.values.category) && !isEmpty(this.values.category['@id'])) {
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
