<script setup>
import { ref, watch } from "vue"
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

const { getColorTheme, getColors, setColors, makeGradient, makeTextWithContrast, checkColorContrast } = useTheme()

const { t } = useI18n()

const isAdvancedMode = ref(false)

const themeSelectorEl = ref()

const selectedThemeIri = ref()
const newThemeSelected = ref()

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

function onChangeTheme(colorTheme) {
  newThemeSelected.value = colorTheme

  if (colorTheme) {
    setColors(colorTheme.variables)
  }
}

const dialogCreateVisible = ref(false)
const themeTitle = ref()

async function onClickSelectColorTheme() {
  if (selectedThemeIri.value) {
    await colorThemeService.changePlatformColorTheme(selectedThemeIri.value)
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
  } catch (error) {
    showErrorNotification(error)
  }
}

async function onClickCreate() {
  try {
    if (!themeTitle.value.trim()) {
      return
    }

    const updatedTheme = await colorThemeService.create({
      title: themeTitle.value,
      colors: getColors(),
    })

    showSuccessNotification(t("Color updated"))

    await themeSelectorEl.value.loadThemes()

    selectedThemeIri.value = updatedTheme["@id"]
  } catch (error) {
    showErrorNotification(error)
  }

  dialogCreateVisible.value = false
}

watch(colorPrimary, (newValue) => {
  colorPrimaryGradient.value = makeGradient(newValue)
  colorPrimaryButtonText.value = newValue
  colorPrimaryButtonAlternativeText.value = makeTextWithContrast(newValue)

  colorPrimaryButtonAlternativeTextError.value = checkColorContrast(newValue, colorPrimaryButtonText.value)
})

watch(colorSecondary, (newValue) => {
  colorSecondaryGradient.value = makeGradient(newValue)
  colorSecondaryButtonText.value = makeTextWithContrast(newValue)

  colorSecondaryButtonTextError.value = checkColorContrast(newValue, colorSecondaryButtonText.value)
})

watch(colorTertiary, (newValue) => {
  colorTertiaryButtonText.value = newValue
  colorTertiaryGradient.value = makeGradient(newValue)
})

watch(colorSuccess, (newValue) => {
  colorSuccessGradient.value = makeGradient(newValue)
  colorSuccessButtonText.value = makeTextWithContrast(newValue)

  colorSuccessButtonTextError.value = checkColorContrast(newValue, colorSuccessButtonText.value)
})

watch(colorInfo, (newValue) => {
  colorInfoGradient.value = makeGradient(newValue)
  colorInfoButtonText.value = makeTextWithContrast(newValue)

  colorInfoButtonTextError.value = checkColorContrast(newValue, colorInfoButtonText.value)
})

watch(colorWarning, (newValue) => {
  colorWarningGradient.value = makeGradient(newValue)
  colorWarningButtonText.value = makeTextWithContrast(newValue)

  colorWarningButtonTextError.value = checkColorContrast(newValue, colorWarningButtonText.value)
})

watch(colorDanger, (newValue) => {
  colorDangerGradient.value = makeGradient(newValue)
  colorDangerButtonText.value = makeTextWithContrast(newValue)
})

// check for contrast of text
const colorPrimaryButtonTextError = ref("")
watch(
  colorPrimaryButtonText,
  (newValue) => (colorPrimaryButtonTextError.value = checkColorContrast(new Color("white"), newValue)),
)

const colorPrimaryButtonAlternativeTextError = ref("")
watch(
  colorPrimaryButtonAlternativeText,
  (newValue) => (colorPrimaryButtonAlternativeTextError.value = checkColorContrast(colorPrimary.value, newValue)),
)

const colorSecondaryButtonTextError = ref("")
watch(
  colorSecondaryButtonText,
  (newValue) => (colorSecondaryButtonTextError.value = checkColorContrast(colorSecondary.value, newValue)),
)

const colorTertiaryButtonTextError = ref("")
watch(
  colorTertiaryButtonText,
  (newValue) => (colorTertiaryButtonTextError.value = checkColorContrast(colorTertiary.value, newValue)),
)

const colorSuccessButtonTextError = ref("")
watch(
  colorSuccessButtonText,
  (newValue) => (colorSuccessButtonTextError.value = checkColorContrast(colorSuccess.value, newValue)),
)

const colorInfoButtonTextError = ref("")
watch(
  colorInfoButtonText,
  (newValue) => (colorInfoButtonTextError.value = checkColorContrast(colorInfo.value, newValue)),
)

const colorWarningButtonTextError = ref("")
watch(
  colorWarningButtonText,
  (newValue) => (colorWarningButtonTextError.value = checkColorContrast(colorWarning.value, newValue)),
)

const colorDangerButtonTextError = ref("")
watch(
  colorDangerButtonText,
  (newValue) => (colorDangerButtonTextError.value = checkColorContrast(new Color("white"), newValue)),
)
</script>

<template>
  <ColorThemeSelector
    ref="themeSelectorEl"
    v-model="selectedThemeIri"
    @change="onChangeTheme"
  />

  <SectionHeader
    :title="t('Modify color theme')"
    size="6"
  >
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
        <BaseColorPicker
          v-model="colorPrimary"
          :label="t('Primary color')"
        />
        <BaseColorPicker
          v-model="colorPrimaryGradient"
          :label="t('Primary color hover/background')"
        />
        <BaseColorPicker
          v-model="colorPrimaryButtonText"
          :error="colorPrimaryButtonTextError"
          :label="t('Primary color button text')"
        />
        <BaseColorPicker
          v-model="colorPrimaryButtonAlternativeText"
          :error="colorPrimaryButtonAlternativeTextError"
          :label="t('Primary color button alternative text')"
        />
      </div>

      <div class="field-group">
        <BaseColorPicker
          v-model="colorSecondary"
          :label="t('Secondary color')"
        />
        <BaseColorPicker
          v-model="colorSecondaryGradient"
          :label="t('Secondary color hover/background')"
        />
        <BaseColorPicker
          v-model="colorSecondaryButtonText"
          :error="colorSecondaryButtonTextError"
          :label="t('Secondary color button text')"
        />
      </div>

      <div class="field-group">
        <BaseColorPicker
          v-model="colorTertiary"
          :label="t('Tertiary color')"
        />
        <BaseColorPicker
          v-model="colorTertiaryGradient"
          :label="t('Tertiary color hover/background')"
        />
      </div>

      <div class="field-group">
        <BaseColorPicker
          v-model="colorSuccess"
          :label="t('Success color')"
        />
        <BaseColorPicker
          v-model="colorSuccessGradient"
          :label="t('Success color hover/background')"
        />
        <BaseColorPicker
          v-model="colorSuccessButtonText"
          :error="colorSuccessButtonTextError"
          :label="t('Success color button text')"
        />
      </div>

      <div class="field-group">
        <BaseColorPicker
          v-model="colorInfo"
          :label="t('Info color')"
        />
        <BaseColorPicker
          v-model="colorInfoGradient"
          :label="t('Info color hover/background')"
        />
        <BaseColorPicker
          v-model="colorInfoButtonText"
          :error="colorInfoButtonTextError"
          :label="t('Info color button text')"
        />
      </div>

      <div class="field-group">
        <BaseColorPicker
          v-model="colorWarning"
          :label="t('Warning color')"
        />
        <BaseColorPicker
          v-model="colorWarningGradient"
          :label="t('Warning color hover/background')"
        />
        <BaseColorPicker
          v-model="colorWarningButtonText"
          :error="colorWarningButtonTextError"
          :label="t('Warning color button text')"
        />
      </div>

      <div class="field-group">
        <BaseColorPicker
          v-model="colorDanger"
          :label="t('Danger color')"
        />
        <BaseColorPicker
          v-model="colorDangerGradient"
          :label="t('Danger color hover/background')"
        />
      </div>

      <div class="field-group">
        <BaseColorPicker
          v-model="formColor"
          :label="t('Form outline color')"
        />
      </div>
    </div>

    <!-- Simple mode -->
    <div v-show="!isAdvancedMode">
      <div class="field-group">
        <BaseColorPicker
          v-model="colorPrimary"
          :label="t('Primary color')"
        />
        <BaseColorPicker
          v-model="colorSecondary"
          :label="t('Secondary color')"
        />
        <BaseColorPicker
          v-model="colorTertiary"
          :label="t('Tertiary color')"
        />
      </div>

      <div class="field-group">
        <BaseColorPicker
          v-model="colorSuccess"
          :label="t('Success color')"
        />
        <BaseColorPicker
          v-model="colorInfo"
          :label="t('Info color')"
        />
        <BaseColorPicker
          v-model="colorWarning"
          :label="t('Warning color')"
        />
        <BaseColorPicker
          v-model="colorDanger"
          :label="t('Danger color')"
        />
      </div>

      <div class="field-group">
        <BaseColorPicker
          v-model="formColor"
          :label="t('Form outline color')"
        />
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
    @cancel-clicked="dialogCreateVisible = false"
  >
    <BaseInputText
      v-model="themeTitle"
      :label="t('Title')"
    />
  </BaseDialogConfirmCancel>
</template>
