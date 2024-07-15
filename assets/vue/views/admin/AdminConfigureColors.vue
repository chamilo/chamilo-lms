<template>
  <div class="admin-colors">
    <SectionHeader :title="t('Configure Chamilo colors')" />

    <div class="admin-colors__container">
      <form class="admin-colors__form">
        <ColorThemeSelector
          ref="themeSelectorEl"
          v-model="selectedTheme"
        />
        <BaseButton
          :label="t('Save')"
          icon="save"
          type="primary"
          @click="onClickChangeColorTheme"
        />
      </form>

      <BaseDivider />

      <div class="admin-colors__settings">
        <div class="admin-colors__settings-form">
          <h3
            v-t="selectedTheme ? 'Edit color theme' : 'Create color theme'"
            class="admin-colors__settings-form-title"
          />

          <BaseInputText
            v-model="themeTitle"
            :disabled="!!selectedTheme"
            :label="t('Title')"
          />

          <!-- Advanced mode -->
          <div v-show="isAdvancedMode">
            <div class="row-group">
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

            <div class="row-group">
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

            <div class="row-group">
              <BaseColorPicker
                v-model="colorTertiary"
                :label="t('Tertiary color')"
              />
              <BaseColorPicker
                v-model="colorTertiaryGradient"
                :label="t('Tertiary color hover/background')"
              />
            </div>

            <div class="row-group">
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

            <div class="row-group">
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

            <div class="row-group">
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

            <div class="row-group">
              <BaseColorPicker
                v-model="colorDanger"
                :label="t('Danger color')"
              />
              <BaseColorPicker
                v-model="colorDangerGradient"
                :label="t('Danger color hover/background')"
              />
            </div>

            <div class="row-group">
              <BaseColorPicker
                v-model="formColor"
                :label="t('Form outline color')"
              />
            </div>
          </div>

          <!-- Simple mode -->
          <div v-show="!isAdvancedMode">
            <div class="row-group">
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

            <div class="row-group">
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

            <div class="row-group">
              <BaseColorPicker
                v-model="formColor"
                :label="t('Form outline color')"
              />
            </div>
          </div>

          <div class="field">
            <BaseButton
              :label="isAdvancedMode ? t('Hide advanced mode') : t('Show advanced mode')"
              icon="cog"
              type="black"
              @click="isAdvancedMode = !isAdvancedMode"
            />
          </div>

          <div class="field">
            <BaseButton
              :label="t('Save')"
              icon="send"
              type="primary"
              @click="saveColors"
            />
          </div>
        </div>

        <BaseDivider layout="vertical" />

        <ColorThemePreview />
      </div>
    </div>
  </div>
</template>

<script setup>
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import { useI18n } from "vue-i18n"
import { ref, watch } from "vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseColorPicker from "../../components/basecomponents/BaseColorPicker.vue"
import { useTheme } from "../../composables/theme"
import { useNotification } from "../../composables/notification"
import Color from "colorjs.io"
import themeService from "../../services/colorThemeService"
import BaseDivider from "../../components/basecomponents/BaseDivider.vue"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import ColorThemeSelector from "../../components/platform/ColorThemeSelector.vue"
import ColorThemePreview from "../../components/admin/ColorThemePreview.vue"
import colorThemeService from "../../services/colorThemeService"

const { t } = useI18n()
const { getColorTheme, getColors, setColors } = useTheme()
const { showSuccessNotification, showErrorNotification } = useNotification()

const themeSelectorEl = ref()

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

const themeTitle = ref()
const selectedTheme = ref()

const saveColors = async () => {
  try {
    const updatedTheme = await themeService.updateTheme({
      iri: selectedTheme.value || undefined,
      title: themeTitle.value,
      colors: getColors(),
    })

    showSuccessNotification(t("Color updated"))

    await themeSelectorEl.value.loadThemes()

    selectedTheme.value = updatedTheme["@id"]
  } catch (error) {
    showErrorNotification(error)
    console.error(error)
  }
}

const isAdvancedMode = ref(false)

watch(colorPrimary, (newValue) => {
  if (!isAdvancedMode.value) {
    colorPrimaryGradient.value = makeGradient(newValue)
    colorPrimaryButtonText.value = newValue
    colorPrimaryButtonAlternativeText.value = makeTextWithContrast(newValue)
  }
  checkColorContrast(newValue, colorPrimaryButtonText.value, colorPrimaryButtonAlternativeTextError)
})

watch(colorSecondary, (newValue) => {
  if (!isAdvancedMode.value) {
    colorSecondaryGradient.value = makeGradient(newValue)
    colorSecondaryButtonText.value = makeTextWithContrast(newValue)
  }
  checkColorContrast(newValue, colorSecondaryButtonText.value, colorSecondaryButtonTextError)
})

watch(colorTertiary, (newValue) => {
  if (!isAdvancedMode.value) {
    colorTertiaryButtonText.value = newValue
    colorTertiaryGradient.value = makeGradient(newValue)
  }
})

watch(colorSuccess, (newValue) => {
  if (!isAdvancedMode.value) {
    colorSuccessGradient.value = makeGradient(newValue)
    colorSuccessButtonText.value = makeTextWithContrast(newValue)
  }
  checkColorContrast(newValue, colorSuccessButtonText.value, colorSuccessButtonTextError)
})

watch(colorInfo, (newValue) => {
  if (!isAdvancedMode.value) {
    colorInfoGradient.value = makeGradient(newValue)
    colorInfoButtonText.value = makeTextWithContrast(newValue)
  }
  checkColorContrast(newValue, colorInfoButtonText.value, colorInfoButtonTextError)
})

watch(colorWarning, (newValue) => {
  if (!isAdvancedMode.value) {
    colorWarningGradient.value = makeGradient(newValue)
    colorWarningButtonText.value = makeTextWithContrast(newValue)
  }
  checkColorContrast(newValue, colorWarningButtonText.value, colorWarningButtonTextError)
})

watch(colorDanger, (newValue) => {
  if (!isAdvancedMode.value) {
    colorDangerGradient.value = makeGradient(newValue)
    colorDangerButtonText.value = makeTextWithContrast(newValue)
  }
})

function makeGradient(color) {
  const light = color.clone().to("oklab").l
  // when color is light (lightness > 0.5), darken gradient color
  // when color is dark, lighten gradient color
  // The values 0.5 and 1.6 were chosen through experimentation, there could be a better way to do this
  if (light > 0.5) {
    return color
      .clone()
      .set({ "oklab.l": (l) => l * 0.8 })
      .to("srgb")
  } else {
    return color
      .clone()
      .set({ "oklab.l": (l) => l * 1.6 })
      .to("srgb")
  }
}

function makeTextWithContrast(color) {
  // according to colorjs library https://colorjs.io/docs/contrast#accessible-perceptual-contrast-algorithm-apca
  // this algorithm is better than WCAGG 2.1 to check for contrast
  // "APCA is being evaluated for use in version 3 of the W3C Web Content Accessibility Guidelines (WCAG)"
  let onWhite = Math.abs(color.contrast("white", "APCA"))
  let onBlack = Math.abs(color.contrast("black", "APCA"))
  return onWhite > onBlack ? new Color("white") : new Color("black")
}

// check for contrast of text
const colorPrimaryButtonTextError = ref("")
watch(colorPrimaryButtonText, (newValue) => {
  checkColorContrast(new Color("white"), newValue, colorPrimaryButtonTextError)
})

const colorPrimaryButtonAlternativeTextError = ref("")
watch(colorPrimaryButtonAlternativeText, (newValue) => {
  checkColorContrast(colorPrimary.value, newValue, colorPrimaryButtonAlternativeTextError)
})

const colorSecondaryButtonTextError = ref("")
watch(colorSecondaryButtonText, (newValue) => {
  checkColorContrast(colorSecondary.value, newValue, colorSecondaryButtonTextError)
})

const colorTertiaryButtonTextError = ref("")
watch(colorTertiaryButtonText, (newValue) => {
  checkColorContrast(colorTertiary.value, newValue, colorTertiaryButtonTextError)
})

const colorSuccessButtonTextError = ref("")
watch(colorSuccessButtonText, (newValue) => {
  checkColorContrast(colorSuccess.value, newValue, colorSuccessButtonTextError)
})

const colorInfoButtonTextError = ref("")
watch(colorInfoButtonText, (newValue) => {
  checkColorContrast(colorInfo.value, newValue, colorInfoButtonTextError)
})

const colorWarningButtonTextError = ref("")
watch(colorWarningButtonText, (newValue) => {
  checkColorContrast(colorWarning.value, newValue, colorWarningButtonTextError)
})

const colorDangerButtonTextError = ref("")
watch(colorDangerButtonText, (newValue) => {
  checkColorContrast(new Color("white"), newValue, colorDangerButtonTextError)
})

function checkColorContrast(background, foreground, textErrorRef) {
  if (isAdvancedMode.value) {
    // using APCA for text contrast in buttons. In chamilo buttons the text
    // has a font size of 16px and weight of 600
    // Lc 60 The minimum level recommended for content text that is not body, column, or block text
    // https://git.apcacontrast.com/documentation/APCA_in_a_Nutshell#use-case--size-ranges
    let contrast = Math.abs(background.contrast(foreground, "APCA"))
    console.log(`Contrast ${contrast}`)
    if (contrast < 60) {
      textErrorRef.value = t("Does not have enough contrast against background")
    } else {
      textErrorRef.value = ""
    }
  }
}

async function onClickChangeColorTheme() {
  if (selectedTheme.value) {
    await colorThemeService.changePlatformColorTheme(selectedTheme.value)
  }
}
</script>
