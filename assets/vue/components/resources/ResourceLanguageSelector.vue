<template>
  <BaseSelect
    v-if="shouldDisplaySelector"
    :id="id"
    v-model="selectedLanguage"
    :allow-clear="true"
    :hast-empty-value="true"
    :label="t('Language')"
    :name="name"
    :options="languageOptionsWithEmpty"
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

const props = defineProps({
  id: {
    type: String,
    default: "resource_language",
  },
  name: {
    type: String,
    default: "language",
  },
  hideWhenSingleLanguage: {
    type: Boolean,
    default: true,
  },
})

const { t } = useI18n()

function isLanguageActive(language) {
  if (!language || "object" !== typeof language) {
    return false
  }

  if ("available" in language) {
    return true === language.available || 1 === language.available || "1" === language.available
  }

  if ("isAvailable" in language) {
    return true === language.isAvailable || 1 === language.isAvailable || "1" === language.isAvailable
  }

  if ("enabled" in language) {
    return true === language.enabled || 1 === language.enabled || "1" === language.enabled
  }

  return true
}

const languageOptions = computed(() => {
  const languages = Array.isArray(window.languages) ? window.languages : []

  return languages
    .filter(isLanguageActive)
    .map((language) => {
      const value = String(language?.isocode || language?.isoCode || "").trim()
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
    .sort((firstLanguage, secondLanguage) => firstLanguage.label.localeCompare(secondLanguage.label))
})

const languageOptionsWithEmpty = computed(() => [
  {
    value: "",
    label: t("No specific language"),
  },
  ...languageOptions.value,
])

const shouldDisplaySelector = computed(() => {
  if (!props.hideWhenSingleLanguage) {
    return languageOptions.value.length > 0
  }

  return languageOptions.value.length > 1
})
</script>
