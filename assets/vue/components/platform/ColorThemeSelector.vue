<script setup>
import BaseSelect from "../basecomponents/BaseSelect.vue"
import { ref, computed, onMounted } from "vue"
import themeService from "../../services/colorThemeService"
import { useI18n } from "vue-i18n"
import { useNotification } from "../../composables/notification"
import { usePlatformConfig } from "../../store/platformConfig"

// v-model: Selected color theme IRI (string)
const modelValue = defineModel({ required: true, type: String })
const emit = defineEmits(["change", "loaded"])

const { t } = useI18n()
const { showErrorNotification } = useNotification()
const platformConfigStore = usePlatformConfig()

const serverThemes = ref([])         // ColorTheme objects
const platformCurrentIri = ref(null) // IRI of the active theme (if provided by relation)
const isServerThemesLoading = ref(true)

async function loadThemes() {
  isServerThemesLoading.value = true

  let items = []

  // 1) Try via Access URL relations
  try {
    const rel = await themeService.findAllByCurrentUrl()
    const raw = Array.isArray(rel?.items) ? rel.items : []
    if (raw.length) {
      serverThemes.value = raw.map(r => r.colorTheme).filter(Boolean)
      platformCurrentIri.value = raw.find(r => r.active)?.colorTheme?.["@id"] || null
    }
  } catch (e) {
    // ignore here; we'll fall back to list()
  }

  // 2) Fallback: list all color themes if no relations or empty
  if (!serverThemes.value.length) {
    try {
      const all = await themeService.list?.()
      // list() may return a Hydra collection or a plain array; normalize
      const arr = Array.isArray(all?.items) ? all.items : Array.isArray(all) ? all : []
      serverThemes.value = arr
      platformCurrentIri.value = platformConfigStore.getSetting?.("platform.color_theme") || null
    } catch (e) {
      // If both fail, show error and bail
      isServerThemesLoading.value = false
      showErrorNotification(t("We could not retrieve the themes"))
      return
    }
  }

  // 3) Initial selection
  let next = modelValue.value
  if (!next) next = platformCurrentIri.value
  if (!next && serverThemes.value.length) next = serverThemes.value[0]["@id"]

  if (next) {
    modelValue.value = next
    const selected = serverThemes.value.find(t => t["@id"] === next) || null
    if (selected) emit("change", selected)
  }

  emit("loaded", serverThemes.value)
  isServerThemesLoading.value = false
}

const options = computed(() => {
  const current = platformCurrentIri.value
  return serverThemes.value.map(theme => ({
    ...theme,
    displayTitle: current && theme["@id"] === current ? `${theme.title} (${t("Current")})` : theme.title,
  }))
})

function onChange({ value }) {
  const selected = serverThemes.value.find(t => t["@id"] === value) || null
  modelValue.value = value || ""
  emit("change", selected)
}

defineExpose({ loadThemes })
onMounted(loadThemes)
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
