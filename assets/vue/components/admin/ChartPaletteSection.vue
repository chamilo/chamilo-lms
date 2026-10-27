<script setup>
import { ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useNotification } from "../../composables/notification"
import { useConfirmation } from "../../composables/useConfirmation"
import BaseButton from "../basecomponents/BaseButton.vue"
import themePaletteService from "../../services/themePaletteService"

const props = defineProps({
  slug: { type: String, required: true },
})

const { t } = useI18n()
const { showErrorNotification, showSuccessNotification } = useNotification()
const { requireConfirmation } = useConfirmation()
const fileInput = ref(null)
const selectedFile = ref(null)
const hasCustomPalette = ref(false)
const themeDirectoryExists = ref(null)
const isLoading = ref(false)
const isSaving = ref(false)

async function refresh() {
  const slug = props.slug
  isLoading.value = true
  try {
    const result = await themePaletteService.status(slug)
    if (slug === props.slug) {
      hasCustomPalette.value = Boolean(result.uploaded)
      themeDirectoryExists.value = result.themeDirectoryExists === true
    }
  } catch (error) {
    if (slug === props.slug) showErrorNotification(error)
  } finally {
    if (slug === props.slug) isLoading.value = false
  }
}

watch(
  () => props.slug,
  () => {
    selectedFile.value = null
    hasCustomPalette.value = false
    themeDirectoryExists.value = null
    if (fileInput.value) fileInput.value.value = ""
    refresh()
  },
  { immediate: true },
)

function selectFile(event) {
  selectedFile.value = event.target.files?.[0] || null
}

async function upload() {
  if (!selectedFile.value || isSaving.value || themeDirectoryExists.value !== true) return
  if (!selectedFile.value.name.toLowerCase().endsWith(".color") || selectedFile.value.size > 16384) {
    showErrorNotification(t("Select a .color file smaller than 16 KB."))
    return
  }

  isSaving.value = true
  try {
    await themePaletteService.upload(props.slug, selectedFile.value)
    selectedFile.value = null
    if (fileInput.value) fileInput.value.value = ""
    await refresh()
    showSuccessNotification(t("Chart palette uploaded"))
  } catch (error) {
    showErrorNotification(error)
  } finally {
    isSaving.value = false
  }
}

function remove() {
  requireConfirmation({
    title: t("Remove chart palette"),
    message: t("Restore the default chart palette for this theme?"),
    accept: async () => {
      isSaving.value = true
      try {
        await themePaletteService.remove(props.slug)
        await refresh()
        showSuccessNotification(t("Default chart palette restored"))
      } catch (error) {
        showErrorNotification(error)
      } finally {
        isSaving.value = false
      }
    },
  })
}
</script>

<template>
  <div class="rounded-xl border border-gray-100 bg-white p-5 space-y-3">
    <p class="text-sm">
      {{ t("Upload a .color file to customize chart colors in the current visual theme.") }}
      <code>palettes/pchart/default.color</code>
    </p>
    <p class="text-sm opacity-70">
      {{ t("One color per line: R,G,B,Alpha (RGB 0-255, Alpha 0-100; maximum 256 colors).") }}
    </p>
    <p class="text-sm">
      {{ t("Visual theme (assets)") }}: <code>{{ slug }}</code> · <span v-if="isLoading">{{ t("Loading") }}</span>
      <span v-else-if="hasCustomPalette">{{ t("Custom chart palette") }}</span>
      <span v-else>{{ t("Default chart palette") }}</span>
    </p>
    <p
      v-if="!isLoading && themeDirectoryExists === false"
      role="alert"
      class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900"
    >
      {{
        t(
          "The active visual theme has no assets directory. Install its theme files or select another theme to upload a chart palette.",
        )
      }}
    </p>
    <input
      ref="fileInput"
      type="file"
      name="chart-palette-file"
      accept=".color"
      class="block w-full text-sm"
      :disabled="isSaving || themeDirectoryExists !== true"
      @change="selectFile"
    />
    <div class="flex flex-wrap gap-2">
      <BaseButton
        :label="t('Upload chart palette')"
        icon="save"
        type="success"
        :disabled="!selectedFile || isSaving || isLoading || themeDirectoryExists !== true"
        :is-loading="isSaving"
        @click="upload"
      />
      <BaseButton
        v-if="hasCustomPalette"
        :label="t('Restore default palette')"
        icon="delete"
        type="secondary"
        :disabled="isSaving || isLoading || themeDirectoryExists !== true"
        @click="remove"
      />
    </div>
  </div>
</template>
