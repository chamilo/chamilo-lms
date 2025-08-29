<script setup>
import { ref, watch, onMounted } from "vue"
import { useI18n } from "vue-i18n"
import Color from "colorjs.io"
import SectionHeader from "../layout/SectionHeader.vue"
import ColorThemeSelector from "../platform/ColorThemeSelector.vue"
import BaseColorPicker from "../basecomponents/BaseColorPicker.vue"
import BaseButton from "../basecomponents/BaseButton.vue"
import BaseDialogConfirmCancel from "../basecomponents/BaseDialogConfirmCancel.vue"
import BaseInputText from "../basecomponents/BaseInputText.vue"
import colorThemeService from "../../services/colorThemeService"
import { useNotification } from "../../composables/notification"
import { useTheme } from "../../composables/theme"

const { showSuccessNotification, showErrorNotification } = useNotification()
const { getColorTheme, getColors, makeGradient, makeTextWithContrast, checkColorContrast } = useTheme()
const { t } = useI18n()

const emit = defineEmits(["selected"])

const isAdvancedMode = ref(false)
const themeSelectorEl = ref()

// v-model from selector: IRI (string)
const selectedThemeIri = ref("")
const newThemeSelected = ref(null)

// Reactive colors
let colorPrimary = getColorTheme("--color-primary-base")
let colorPrimaryGradient = getColorTheme("--color-primary-gradient")
let colorPrimaryButtonText = getColorTheme("--color-primary-button-text")
let colorPrimaryButtonAlternativeText = getColorTheme("--color-primary-button-alternative-text")

let colorSecondary = getColorTheme("--color-secondary-base")
let colorSecondaryGradient = getColorTheme("--color-secondary-gradient")
let colorSecondaryButtonText = getColorTheme("--color-secondary-button-text")

let colorTertiary = getColorTheme("--color-tertiary-base")
let colorTertiaryGradient = getColorTheme("--color-tertiary-gradient")
let colorTertiaryButtonText = getColorTheme("--color-tertiary-button-text")

let colorSuccess = getColorTheme("--color-success-base")
let colorSuccessGradient = getColorTheme("--color-success-gradient")
let colorSuccessButtonText = getColorTheme("--color-success-button-text")

let colorInfo = getColorTheme("--color-info-base")
let colorInfoGradient = getColorTheme("--color-info-gradient")
let colorInfoButtonText = getColorTheme("--color-info-button-text")

let colorWarning = getColorTheme("--color-warning-base")
let colorWarningGradient = getColorTheme("--color-warning-gradient")
let colorWarningButtonText = getColorTheme("--color-warning-button-text")

let colorDanger = getColorTheme("--color-danger-base")
let colorDangerGradient = getColorTheme("--color-danger-gradient")
let colorDangerButtonText = getColorTheme("--color-danger-button-text")

let formColor = getColorTheme("--color-form-base")

const dialogCreateVisible = ref(false)
const themeTitle = ref("")

// ------- Safe helpers (local, no setColors usage) -------
const ROOT = typeof document !== "undefined" ? document.documentElement : null

function toColorSafe(input, fallback = "#000") {
  try {
    if (input instanceof Color) return input
    if (input == null) return new Color(fallback)
    const s = String(input).trim()
    if (!s) return new Color(fallback)
    if (s.startsWith("#") || s.includes("(")) return new Color(s)
    const parts = s.split(/[,\s/]+/).filter(Boolean).map(Number)
    if (parts.length >= 3 && parts.every((n) => Number.isFinite(n))) {
      const [r, g, b, a] = parts
      const css = a != null ? `rgb(${r} ${g} ${b} / ${a})` : `rgb(${r} ${g} ${b})`
      return new Color(css)
    }
    return new Color(s)
  } catch {
    try {
      return new Color(fallback)
    } catch {
      return new Color("#000")
    }
  }
}

function colorToTriplet(color) {
  const c = toColorSafe(color).to("srgb")
  const clamp01 = (x) => Math.min(1, Math.max(0, x ?? 0))
  const r = Math.round(clamp01(c.r) * 255)
  const g = Math.round(clamp01(c.g) * 255)
  const b = Math.round(clamp01(c.b) * 255)
  return `${r} ${g} ${b}`
}

// map CSS var -> ref(Color) to keep pickers in sync
const refMap = {
  "--color-primary-base": colorPrimary,
  "--color-primary-gradient": colorPrimaryGradient,
  "--color-primary-button-text": colorPrimaryButtonText,
  "--color-primary-button-alternative-text": colorPrimaryButtonAlternativeText,

  "--color-secondary-base": colorSecondary,
  "--color-secondary-gradient": colorSecondaryGradient,
  "--color-secondary-button-text": colorSecondaryButtonText,

  "--color-tertiary-base": colorTertiary,
  "--color-tertiary-gradient": colorTertiaryGradient,
  "--color-tertiary-button-text": colorTertiaryButtonText,

  "--color-success-base": colorSuccess,
  "--color-success-gradient": colorSuccessGradient,
  "--color-success-button-text": colorSuccessButtonText,

  "--color-info-base": colorInfo,
  "--color-info-gradient": colorInfoGradient,
  "--color-info-button-text": colorInfoButtonText,

  "--color-warning-base": colorWarning,
  "--color-warning-gradient": colorWarningGradient,
  "--color-warning-button-text": colorWarningButtonText,

  "--color-danger-base": colorDanger,
  "--color-danger-gradient": colorDangerGradient,
  "--color-danger-button-text": colorDangerButtonText,

  "--color-form-base": formColor,
}

function applyOneColor(varName, valueLike) {
  if (!ROOT || !varName?.startsWith("--color-")) return
  const col = toColorSafe(valueLike)
  ROOT.style.setProperty(varName, colorToTriplet(col))
  if (refMap[varName]) {
    refMap[varName].value = col
  }
}

function safeApplyColors(varsFromTheme) {
  let incoming = varsFromTheme
  if (typeof incoming === "string") {
    try { incoming = JSON.parse(incoming) } catch { incoming = {} }
  }
  if (!incoming || typeof incoming !== "object") return
  for (const [k, v] of Object.entries(incoming)) {
    applyOneColor(k, v)
  }
}
// --------------------------------------------------------

function onChangeTheme(colorTheme) {
  newThemeSelected.value = colorTheme
  if (!colorTheme) {
    themeTitle.value = ""
    emit("selected", null)
    return
  }
  safeApplyColors(colorTheme.variables)
  themeTitle.value = colorTheme.title || ""
  emit("selected", colorTheme)
}

onMounted(() => {
  themeSelectorEl.value?.loadThemes()
})

async function onClickSelectColorTheme() {
  try {
    if (!selectedThemeIri.value) return
    await colorThemeService.changePlatformColorTheme(selectedThemeIri.value)
    showSuccessNotification(t("Theme selected as current"))
    await themeSelectorEl.value.loadThemes()
  } catch (error) {
    showErrorNotification(error)
  }
}

async function onClickUpdate() {
  try {
    const updatedTheme = await colorThemeService.update({
      iri: selectedThemeIri.value,
      title: themeTitle.value,
      colors: getColors(),
    })
    showSuccessNotification(t("Color updated"))
    await themeSelectorEl.value.loadThemes()
    selectedThemeIri.value = updatedTheme["@id"]
    emit("selected", updatedTheme)
  } catch (error) {
    showErrorNotification(error)
  }
}

async function onClickCreate() {
  try {
    if (!themeTitle.value?.trim()) return
    const created = await colorThemeService.create({
      title: themeTitle.value,
      colors: getColors(),
    })
    showSuccessNotification(t("Color theme created"))
    await themeSelectorEl.value.loadThemes()
    selectedThemeIri.value = created["@id"]
    emit("selected", created)
  } catch (error) {
    showErrorNotification(error)
  } finally {
    dialogCreateVisible.value = false
  }
}

// ---- contrast watchers ----
const colorPrimaryButtonTextError = ref("")
watch(colorPrimaryButtonText, (nv) => (colorPrimaryButtonTextError.value = checkColorContrast(new Color("white"), nv)))
const colorPrimaryButtonAlternativeTextError = ref("")
watch(colorPrimaryButtonAlternativeText, (nv) => (colorPrimaryButtonAlternativeTextError.value = checkColorContrast(colorPrimary.value, nv)))
const colorSecondaryButtonTextError = ref("")
watch(colorSecondaryButtonText, (nv) => (colorSecondaryButtonTextError.value = checkColorContrast(colorSecondary.value, nv)))
const colorTertiaryButtonTextError = ref("")
watch(colorTertiaryButtonText, (nv) => (colorTertiaryButtonTextError.value = checkColorContrast(colorTertiary.value, nv)))
const colorSuccessButtonTextError = ref("")
watch(colorSuccessButtonText, (nv) => (colorSuccessButtonTextError.value = checkColorContrast(colorSuccess.value, nv)))
const colorInfoButtonTextError = ref("")
watch(colorInfoButtonText, (nv) => (colorInfoButtonTextError.value = checkColorContrast(colorInfo.value, nv)))
const colorWarningButtonTextError = ref("")
watch(colorWarningButtonText, (nv) => (colorWarningButtonTextError.value = checkColorContrast(colorWarning.value, nv)))
const colorDangerButtonTextError = ref("")
watch(colorDangerButtonText, (nv) => (colorDangerButtonTextError.value = checkColorContrast(new Color("white"), nv)))

watch(colorPrimary, (nv) => {
  colorPrimaryGradient.value = makeGradient(nv)
  colorPrimaryButtonText.value = nv
  colorPrimaryButtonAlternativeText.value = makeTextWithContrast(nv)
  colorPrimaryButtonAlternativeTextError.value = checkColorContrast(nv, colorPrimaryButtonText.value)
})
watch(colorSecondary, (nv) => {
  colorSecondaryGradient.value = makeGradient(nv)
  colorSecondaryButtonText.value = makeTextWithContrast(nv)
  colorSecondaryButtonTextError.value = checkColorContrast(nv, colorSecondaryButtonText.value)
})
watch(colorTertiary, (nv) => {
  colorTertiaryButtonText.value = nv
  colorTertiaryGradient.value = makeGradient(nv)
})
watch(colorSuccess, (nv) => {
  colorSuccessGradient.value = makeGradient(nv)
  colorSuccessButtonText.value = makeTextWithContrast(nv)
  colorSuccessButtonTextError.value = checkColorContrast(nv, colorSuccessButtonText.value)
})
watch(colorInfo, (nv) => {
  colorInfoGradient.value = makeGradient(nv)
  colorInfoButtonText.value = makeTextWithContrast(nv)
  colorInfoButtonTextError.value = checkColorContrast(nv, colorInfoButtonText.value)
})
watch(colorWarning, (nv) => {
  colorWarningGradient.value = makeGradient(nv)
  colorWarningButtonText.value = makeTextWithContrast(nv)
  colorWarningButtonTextError.value = checkColorContrast(nv, colorWarningButtonText.value)
})
watch(colorDanger, (nv) => {
  colorDangerGradient.value = makeGradient(nv)
  colorDangerButtonText.value = makeTextWithContrast(nv)
})
</script>

<template>
  <ColorThemeSelector
    ref="themeSelectorEl"
    v-model="selectedThemeIri"
    @change="onChangeTheme"
  />

  <SectionHeader :title="t('Modify color theme')" size="6">
    <BaseButton
      :label="isAdvancedMode ? t('Hide advanced mode') : t('Show advanced mode')"
      icon="cog"
      type="black"
      @click="isAdvancedMode = !isAdvancedMode"
    />
  </SectionHeader>

  <form class="admin-colors__form-fields">
    <!-- Advanced mode -->
    <div v-show="isAdvancedMode">
      <div class="field-group">
        <BaseColorPicker v-model="colorPrimary" :label="t('Primary color')" />
        <BaseColorPicker v-model="colorPrimaryGradient" :label="t('Primary color hover/background')" />
        <BaseColorPicker v-model="colorPrimaryButtonText" :error="colorPrimaryButtonTextError" :label="t('Primary color button text')" />
        <BaseColorPicker v-model="colorPrimaryButtonAlternativeText" :error="colorPrimaryButtonAlternativeTextError" :label="t('Primary color button alternative text')" />
      </div>

      <div class="field-group">
        <BaseColorPicker v-model="colorSecondary" :label="t('Secondary color')" />
        <BaseColorPicker v-model="colorSecondaryGradient" :label="t('Secondary color hover/background')" />
        <BaseColorPicker v-model="colorSecondaryButtonText" :error="colorSecondaryButtonTextError" :label="t('Secondary color button text')" />
      </div>

      <div class="field-group">
        <BaseColorPicker v-model="colorTertiary" :label="t('Tertiary color')" />
        <BaseColorPicker v-model="colorTertiaryGradient" :label="t('Tertiary color hover/background')" />
      </div>

      <div class="field-group">
        <BaseColorPicker v-model="colorSuccess" :label="t('Success color')" />
        <BaseColorPicker v-model="colorSuccessGradient" :label="t('Success color hover/background')" />
        <BaseColorPicker v-model="colorSuccessButtonText" :error="colorSuccessButtonTextError" :label="t('Success color button text')" />
      </div>

      <div class="field-group">
        <BaseColorPicker v-model="colorInfo" :label="t('Info color')" />
        <BaseColorPicker v-model="colorInfoGradient" :label="t('Info color hover/background')" />
        <BaseColorPicker v-model="colorInfoButtonText" :error="colorInfoButtonTextError" :label="t('Info color button text')" />
      </div>

      <div class="field-group">
        <BaseColorPicker v-model="colorWarning" :label="t('Warning color')" />
        <BaseColorPicker v-model="colorWarningGradient" :label="t('Warning color hover/background')" />
        <BaseColorPicker v-model="colorWarningButtonText" :error="colorWarningButtonTextError" :label="t('Warning color button text')" />
      </div>

      <div class="field-group">
        <BaseColorPicker v-model="colorDanger" :label="t('Danger color')" />
        <BaseColorPicker v-model="colorDangerGradient" :label="t('Danger color hover/background')" />
      </div>

      <div class="field-group">
        <BaseColorPicker v-model="formColor" :label="t('Form outline color')" />
      </div>
    </div>

    <!-- Simple mode -->
    <div v-show="!isAdvancedMode">
      <div class="field-group">
        <BaseColorPicker v-model="colorPrimary" :label="t('Primary color')" />
        <BaseColorPicker v-model="colorSecondary" :label="t('Secondary color')" />
        <BaseColorPicker v-model="colorTertiary" :label="t('Tertiary color')" />
      </div>

      <div class="field-group">
        <BaseColorPicker v-model="colorSuccess" :label="t('Success color')" />
        <BaseColorPicker v-model="colorInfo" :label="t('Info color')" />
        <BaseColorPicker v-model="colorWarning" :label="t('Warning color')" />
        <BaseColorPicker v-model="colorDanger" :label="t('Danger color')" />
      </div>

      <div class="field-group">
        <BaseColorPicker v-model="formColor" :label="t('Form outline color')" />
      </div>
    </div>

    <div class="field-group">
      <BaseButton
        :disabled="!selectedThemeIri"
        :label="t('Select as current theme')"
        icon="save"
        type="primary"
        @click="onClickSelectColorTheme"
      />
      <BaseButton
        :disabled="!selectedThemeIri"
        :label="t('Save')"
        icon="send"
        type="primary"
        @click="onClickUpdate"
      />
      <BaseButton
        :label="t('Save as new theme')"
        icon="send"
        type="primary"
        @click="dialogCreateVisible = true"
      />
    </div>
  </form>

  <BaseDialogConfirmCancel
    v-model:is-visible="dialogCreateVisible"
    :cancel-label="t('Cancel')"
    :confirm-label="t('Save')"
    :title="t('New color theme')"
    @confirm-clicked="onClickCreate"
    @cancel-clicked="() => (dialogCreateVisible.value = false)"
  >
    <BaseInputText v-model="themeTitle" :label="t('Title')" />
  </BaseDialogConfirmCancel>
</template>
