<template>
  <div>
    <BaseInputText
      id="item_title"
      v-model="v$.item.title.$model"
      :error-text="v$.item.title.$errors.map((error) => error.$message).join('<br>')"
      :is-invalid="v$.item.title.$error"
      :label="t('Title')"
    />

    <BaseCheckbox
      id="enabled"
      v-model="v$.item.enabled.$model"
      :label="t('Enabled')"
      name="enabled"
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

    <BaseTinyEditor
      v-model="v$.item.content.$model"
      editor-id="item_content"
      required
      :title="t('Content')"
    />

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
import { computed, ref, watch } from "vue"
import BaseInputText from "../basecomponents/BaseInputText.vue"
import BaseCheckbox from "../basecomponents/BaseCheckbox.vue"
import BaseDropdown from "../basecomponents/BaseDropdown.vue"
import useVuelidate from "@vuelidate/core"
import { required } from "@vuelidate/validators"
import isEmpty from "lodash/isEmpty"
import { useI18n } from "vue-i18n"
import pageCategoryService from "../../services/pageCategoryService"
import BaseTinyEditor from "../basecomponents/BaseTinyEditor.vue"

const props = defineProps({
  modelValue: {
    type: Object,
    default: () => {},
  },
})

const emit = defineEmits(["update:modelValue", "submit"])

const { t } = useI18n()

let locales = ref(window.languages)

let categories = ref([])

const findAllPageCategories = async () => (categories.value = await pageCategoryService.findAll())

findAllPageCategories()

watch(
  () => props.modelValue,
  (newValue) => {
    if (!newValue) {
      return
    }

    if (!isEmpty(newValue.category) && !isEmpty(newValue.category["@id"])) {
      emit("update:modelValue", {
        ...newValue,
        category: newValue.category["@id"],
      })
    }
  },
)

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
  },
}

const v$ = useVuelidate(validations, { item: computed(() => props.modelValue) })

function btnSaveOnClick() {
  const item = { ...props.modelValue, ...v$.value.item.$model }

  emit("update:modelValue", item)

  emit("submit", item)
}
</script>
