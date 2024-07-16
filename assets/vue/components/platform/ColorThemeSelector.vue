<script setup>
import BaseSelect from "../basecomponents/BaseSelect.vue"
import { computed, ref } from "vue"
import themeService from "../../services/colorThemeService"
import { useI18n } from "vue-i18n"
import { useNotification } from "../../composables/notification"

const modelValue = defineModel({
  required: true,
  type: Object,
})

const { t } = useI18n()
const { showErrorNotification } = useNotification()

const serverThemes = ref([])
const isServerThemesLoading = ref(true)

const loadThemes = async () => {
  try {
    const { items } = await themeService.findAllByCurrentUrl()

    serverThemes.value = items.map((accessUrlRelColorTheme) => accessUrlRelColorTheme.colorTheme)

    modelValue.value = items.find((accessUrlRelColorTheme) => accessUrlRelColorTheme.active)?.colorTheme["@id"]
  } catch (e) {
    showErrorNotification(t("We could not retrieve the themes"))
  } finally {
    isServerThemesLoading.value = false
  }
}

defineExpose({
  loadThemes,
})

const emit = defineEmits(["change"])

loadThemes()

function onChange({ value }) {
  const themeSelected = serverThemes.value.find((accessUrlRelColorTheme) => accessUrlRelColorTheme["@id"] === value)

  emit("change", themeSelected)
}
</script>

<template>
  <BaseSelect
    v-model="modelValue"
    :is-loading="isServerThemesLoading"
    :label="t('Theme title')"
    :options="serverThemes"
    allow-clear
    option-label="title"
    option-value="@id"
    @change="onChange"
  />
</template>
