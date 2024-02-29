<template>
  <div>
    <BaseInputText
      v-model="v$.item.title.$model"
      :error-text="v$.item.title.$errors.map((error) => error.$message).join('<br>')"
      :is-invalid="v$.item.title.$error"
      :label="t('Title')"
      id="item_title"
    />

    <BaseCheckbox
      id="enabled"
      :label="t('Enabled')"
      name="enabled"
      v-model="v$.item.enabled.$model"
    />

    <BaseDropdown
      v-model="v$.item.category.$model"
      :error-text="v$.item.category.$errors.map((error) => error.$message).join('<br>')"
      :is-invalid="v$.item.category.$error"
      :label="t('Category')"
      :options="categories"
      input-id="category"
      name="category"
      option-label="title"
      option-value="@id"
    />

    <BaseDropdown
      v-model="v$.item.locale.$model"
      :error-text="v$.item.locale.$errors.map((error) => error.$message).join('<br>')"
      :is-invalid="v$.item.locale.$error"
      :label="t('Locale')"
      :options="locales"
      input-id="locale"
      name="locale"
      option-label="originalName"
      option-value="isocode"
    />

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
          file_picker_callback: function(callback, value, meta) {
            if (meta.filetype === 'image') {
              var input = document.createElement('input');
              input.setAttribute('type', 'file');
              input.setAttribute('accept', 'image/*');
              input.onchange = function() {
                var file = this.files[0];
                var reader = new FileReader();
                reader.onload = function(e) {
                  // Esta es la URL de la imagen que se pasará al editor
                  callback(e.target.result, {
                    alt: file.name
                  });
                };
                reader.readAsDataURL(file);
              };
              input.click();
            }
          },
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
import BaseInputText from "../basecomponents/BaseInputText.vue";
import BaseCheckbox from "../basecomponents/BaseCheckbox.vue";
import BaseDropdown from "../basecomponents/BaseDropdown.vue";
import useVuelidate from '@vuelidate/core';
import { required } from '@vuelidate/validators';
import isEmpty from 'lodash/isEmpty';
import { useI18n } from 'vue-i18n';
import pageCategoryService from "../../services/pageCategoryService"

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

const { t } = useI18n();

let locales = ref(window.languages);

let categories = ref([]);

const findAllPageCategories = async () => categories.value = await pageCategoryService.findAll()

findAllPageCategories()

watch(
  () => props.modelValue,
  (newValue) => {
    if (!newValue) {
      return;
    }

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
