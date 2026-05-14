<template>
  <BaseSelect
    :id="id"
    v-model="selectedLanguage"
    :allow-clear="true"
    :hast-empty-value="true"
    :label="t('Language')"
    :name="name"
    :options="languageOptions"
    option-label="label"
    option-value="value"
  />
</template>

<script setup>
import { computed } from "vue"
import { useI18n } from "vue-i18n"
import BaseSelect from "../basecomponents/BaseSelect.vue"

const selectedLanguage = defineModel({
  type: String,
  default: "",
})

defineProps({
  id: {
    type: String,
    default: "resource_language",
  },
  name: {
    type: String,
    default: "language",
  },
})

const { t } = useI18n()

const languageOptions = computed(() => {
  const languages = Array.isArray(window.languages) ? window.languages : []

  return languages
    .map((language) => {
      const value = String(language?.isocode || "").trim()
      const label = String(
        language?.originalName ||
          language?.original_name ||
          language?.englishName ||
          language?.english_name ||
          value,
      ).trim()

      return {
        value,
        label,
      }
    })
    .filter((language) => language.value && language.label)
})
</script>
