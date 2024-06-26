<script setup>
import BaseSelect from "../basecomponents/BaseSelect.vue"
import { ref } from "vue"
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
    const { items } = await themeService.findAll()

    serverThemes.value = items
  } catch (e) {
    showErrorNotification(t("We could not retrieve the themes"))
  } finally {
    isServerThemesLoading.value = false
  }
}

defineExpose({
  loadThemes,
})

loadThemes()
</script>

<template>
  <BaseSelect
    v-model="modelValue"
    :is-loading="isServerThemesLoading"
    :label="t('Color theme selected')"
    :options="serverThemes"
    allow-clear
    option-label="title"
    option-value="@id"
  />
</template>
