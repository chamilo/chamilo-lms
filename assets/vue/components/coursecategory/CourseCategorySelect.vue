<script setup>
import { watch } from "vue"
import { useI18n } from "vue-i18n"
import BaseMultiSelect from "../basecomponents/BaseMultiSelect.vue"
import { useCourseCategories } from "../../composables/courseCategory"
import { useNotification } from "../../composables/notification"

const { t } = useI18n()
const { showErrorNotification } = useNotification()

const modelValue = defineModel({
  type: Array,
  required: true,
})

const props = defineProps({
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
  action: {
    type: String,
    required: true,
    validator: (value) => ["catalogue", "course-creation"].includes(value),
  },
})

const { isLoading, categories, error } = useCourseCategories(props.action)

watch(error, (value) => {
  if (value) {
    showErrorNotification(value)
  }
})
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
