<script setup>
import BaseSelect from "../basecomponents/BaseSelect.vue"
import { ref } from "vue"
import themeService from "../../services/colorThemeService"
import { useI18n } from "vue-i18n"
import { useNotification } from "../../composables/notification"

// v-model: Selected color theme IRI (string)
const modelValue = defineModel({ required: true, type: String })
const emit = defineEmits(["change"])

const { t } = useI18n()
const { showErrorNotification } = useNotification()

let serverThemes = [] // ColorTheme objects
const isServerThemesLoading = ref(true)

const options = ref([])

async function loadThemes() {
  // IRI of the active theme (if provided by relation)
  let currentColorThemeIri = null

  isServerThemesLoading.value = true

  // 1) Try via Access URL relations
  try {
    const themesInAccessUrl = await themeService.findAllByCurrentUrl()

    if (themesInAccessUrl.length) {
      serverThemes = themesInAccessUrl.map((r) => r.colorTheme)
      currentColorThemeIri = themesInAccessUrl.find((r) => r.active)?.colorTheme?.["@id"] || null
    }
  } catch {
    // Ignore here; we'll fallback to list()
  }

  // 2) Fallback: list all color themes if no relations or empty
  if (!serverThemes.length) {
    try {
      serverThemes = await themeService.list()

      if (serverThemes.length) {
        currentColorThemeIri = serverThemes[0]["@id"]
      }
    } catch {
      // If both fail, ignore here
    }
  }

  isServerThemesLoading.value = false

  if (!serverThemes.length) {
    showErrorNotification(t("We could not retrieve the themes"))

    return
  }

  options.value = serverThemes.map((colorTheme) => ({
    "@id": colorTheme["@id"],
    displayTitle: colorTheme["@id"] === currentColorThemeIri ? t("{0} (current)", [colorTheme.title]) : colorTheme.title,
  }))

  if (currentColorThemeIri) {
    onChange({ value: currentColorThemeIri })
  }
}

function onChange({ value }) {
  const selected = serverThemes.find((t) => t["@id"] === value) || null
  modelValue.value = value || ""
  emit("change", selected)
}

defineExpose({ loadThemes })
</script>

<template>
  <BaseSelect
    v-model="modelValue"
    :is-loading="isServerThemesLoading"
    :label="t('Theme title')"
    :options="options"
    option-label="displayTitle"
    option-value="@id"
    @change="onChange"
  />
</template>
