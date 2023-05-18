<template>
  <div>
    <div class="field">
      <div class="p-float-label">
        <InputText
          id="item_title"
          v-model="v$.item.title.$model"
          :class="{ 'p-invalid': v$.item.title.$invalid }"
          type="text"
        />
        <label
          v-t="'Title'"
          :class="{ 'p-error': v$.item.title.$invalid }"
          for="item_title"
        />
      </div>
      <small
        v-if="v$.item.title.$invalid || v$.item.title.$pending.$response"
        v-t="v$.item.title.required.$message"
        class="p-error"
      />
    </div>

    <div class="field-checkbox">
      <Checkbox
        v-model="v$.item.enabled.$model"
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
        <Dropdown
          v-model="v$.item.category.$model"
          :options="categories"
          input-id="category"
          option-label="title"
          option-value="@id"
          :class="{ 'p-invalid': v$.item.category.$invalid }"
        />
        <label
          v-t="'Category'"
          for="category"
        />
      </div>
      <small
        v-if="v$.item.category.$invalid || v$.item.category.$pending.$response"
        v-t="v$.item.category.required.$message"
        class="p-error"
      />
    </div>

    <div class="field">
      <div class="p-float-label">
        <Dropdown
          v-model="v$.item.locale.$model"
          :options="locales"
          input-id="locale"
          :class="{ 'p-invalid': v$.item.locale.$invalid }"
        />
        <label
          v-t="'Locale'"
          for="locale"
        />
      </div>
      <small
        v-if="v$.item.locale.$invalid || v$.item.locale.$pending.$response"
        v-t="v$.item.locale.required.$message"
        class="p-error"
      />
    </div>

    <div class="field">
      <TinyEditor
        id="item_content"
        v-model="v$.item.content.$model"
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
        required
      />
    </div>

    <div class="text-right">
      <Button
        :disabled="v$.item.$invalid"
        :label="t('Save')"
        icon="mdi mdi-content-save"
        type="button"
        @click="btnSaveOnClick"
      />
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { useStore } from 'vuex';
import InputText from 'primevue/inputtext';
import Checkbox from 'primevue/checkbox';
import Dropdown from 'primevue/dropdown';
import useVuelidate from '@vuelidate/core';
import { required } from '@vuelidate/validators';
import isEmpty from 'lodash/isEmpty';
import { useI18n } from 'vue-i18n';

const props = defineProps({
  modelValue: {
    type: Object,
    default: () => {},
  }
});

const emit = defineEmits([
  'update:modelValue',
  'submit',
]);

const store = useStore();
const { t } = useI18n();

let locales = ref([]);

let categories = ref([]);
locales.value = window.languages.map(locale => locale.isocode);

store.dispatch('pagecategory/findAll')
  .then((response) => {
    categories.value = response.map(data => data);
  });

const currentUser = computed(() => store.getters['security/getUser']);

watch(
  () => props.modelValue,
  (newValue) => {
    if (!newValue) {
      return;
    }

    emit('update:modelValue', {
      ...newValue,
      creator: currentUser.value['@id'],
      url: '/api/access_urls/' + window.access_url_id,
    });

    if (!isEmpty(newValue.category) && !isEmpty(newValue.category['@id'])) {
      emit('update:modelValue', {
        ...newValue,
        category: newValue.category['@id']
      });
    }
  }
);

const validations = {
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
    category: {
      required,
    },
  }
};

const v$ = useVuelidate(
  validations,
  { item: computed(() => props.modelValue) }
);

function btnSaveOnClick () {
  const item = { ...props.modelValue, ...v$.value.item.$model };

  emit('update:modelValue', item)

  emit('submit', item)
}
</script>
