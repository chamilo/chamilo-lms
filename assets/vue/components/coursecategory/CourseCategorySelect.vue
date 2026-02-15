<script setup>
import { ref } from "vue"
import { useI18n } from "vue-i18n"
import BaseMultiSelect from "../basecomponents/BaseMultiSelect.vue"
import * as courseCategoryService from "../../services/coursecategory"

const { t } = useI18n()

const modelValue = defineModel({
  type: Array,
  required: true,
})

defineProps({
  id: {
    type: String,
    required: false,
    default: "category-multiselect",
  },
  optionLabel: {
    type: String,
    required: false,
    default: "title",
  },
  optionValue: {
    type: String,
    required: false,
    default: "@id",
  },
})

const isLoading = ref(true)

const categories = ref([])

courseCategoryService
  .findAll()
  .then((items) => (categories.value = items))
  .finally(() => (isLoading.value = false))
</script>

<template>
  <BaseMultiSelect
    :id="id"
    v-model="modelValue"
    :is-loading="isLoading"
    :label="t('Category')"
    :option-label="optionLabel"
    :option-value="optionValue"
    :options="categories"
    input-id="multiselect-category"
  />
</template>
