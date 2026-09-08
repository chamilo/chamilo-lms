<template>
  <BaseSelect
    v-if="shouldDisplaySelector"
    :id="id"
    v-model="modelValue"
    :allow-clear="false"
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
import { useResourceLanguageVisibility } from "../../composables/useResourceLanguageVisibility"

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
  options: {
    type: Array,
    default: null,
  },
  emptyValue: {
    type: String,
    default: "",
  },
})

const { t } = useI18n()
const { resourceLanguageEnabled } = useResourceLanguageVisibility()

// PrimeVue treats an empty-string option value as “no selection”, so it renders
// the field label/placeholder instead of the explicit “No specific language”
// option. Keep a non-empty value only inside this component and translate it
// back to the value expected by each resource form (usually "", LP uses
// "__none__"). This keeps the persisted API value unchanged.
const INTERNAL_EMPTY_VALUE = "__resource_language_no_specific__"

const modelValue = computed({
  get() {
    const value = null === selectedLanguage.value || undefined === selectedLanguage.value
      ? props.emptyValue
      : String(selectedLanguage.value)

    return value === props.emptyValue ? INTERNAL_EMPTY_VALUE : value
  },
  set(value) {
    if (null === value || undefined === value || INTERNAL_EMPTY_VALUE === value) {
      selectedLanguage.value = props.emptyValue
      return
    }

    selectedLanguage.value = String(value)
  },
})

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

function normalizeLanguageOption(language) {
  const value = String(language?.value ?? language?.isocode ?? language?.isoCode ?? "").trim()
  const label = String(
    language?.label ??
      language?.originalName ??
      language?.original_name ??
      language?.englishName ??
      language?.english_name ??
      value,
  ).trim()

  return { value, label }
}

const sourceLanguageOptions = computed(() => {
  if (Array.isArray(props.options)) {
    return props.options.map(normalizeLanguageOption).filter((language) => language.label)
  }

  const languages = Array.isArray(window.languages) ? window.languages : []

  return languages
    .filter(isLanguageActive)
    .map(normalizeLanguageOption)
    .filter((language) => language.value && language.label)
    .sort((firstLanguage, secondLanguage) => firstLanguage.label.localeCompare(secondLanguage.label))
})

const languageOptions = computed(() => {
  const seenValues = new Set()

  return sourceLanguageOptions.value.filter((language) => {
    if (!language.value || language.value === props.emptyValue || seenValues.has(language.value)) {
      return false
    }

    seenValues.add(language.value)

    return true
  })
})

const languageOptionsWithEmpty = computed(() => {
  const configuredEmptyOption = sourceLanguageOptions.value.find((language) => language.value === props.emptyValue)

  return [
    {
      value: INTERNAL_EMPTY_VALUE,
      label: configuredEmptyOption?.label || t("No specific language"),
    },
    ...languageOptions.value,
  ]
})

const shouldDisplaySelector = computed(() => {
  if (!resourceLanguageEnabled.value) {
    return false
  }

  if (!props.hideWhenSingleLanguage) {
    return languageOptions.value.length > 0
  }

  return languageOptions.value.length > 1
})
</script>
