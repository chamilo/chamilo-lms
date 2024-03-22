<template class="personal-theme">
  <h4 class="mb-4">{{ t("Configure chamilo colors") }}</h4>

  <!-- Advanced mode -->
  <div v-show="isAdvancedMode">
    <div class="flex flex-col gap-2 mb-3 md:grid md:grid-cols-2 xl:grid-cols-4">
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
        :label="t('Primary color button text')"
        :error="colorPrimaryButtonTextError"
      />
      <BaseColorPicker
        v-model="colorPrimaryButtonAlternativeText"
        :label="t('Primary color button alternative text')"
        :error="colorPrimaryButtonAlternativeTextError"
      />
    </div>

    <div class="flex flex-col gap-2 mb-3 md:grid md:grid-cols-2 xl:grid-cols-4">
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
        :label="t('Secondary color button text')"
        :error="colorSecondaryButtonTextError"
      />
    </div>

    <div class="flex flex-col gap-2 mb-3 md:grid md:grid-cols-2 xl:grid-cols-4">
      <BaseColorPicker
        v-model="colorTertiary"
        :label="t('Tertiary color')"
      />
      <BaseColorPicker
        v-model="colorTertiaryGradient"
        :label="t('Tertiary color hover/background')"
      />
    </div>

    <div class="flex flex-col gap-2 mb-3 md:grid md:grid-cols-2 xl:grid-cols-4">
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
        :label="t('Success color button text')"
        :error="colorSuccessButtonTextError"
      />
    </div>

    <div class="flex flex-col gap-2 mb-3 md:grid md:grid-cols-2 xl:grid-cols-4">
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
        :label="t('Info color button text')"
        :error="colorInfoButtonTextError"
      />
    </div>

    <div class="flex flex-col gap-2 mb-3 md:grid md:grid-cols-2 xl:grid-cols-4">
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
        :label="t('Warning color button text')"
        :error="colorWarningButtonTextError"
      />
    </div>

    <div class="flex flex-col gap-2 mb-3 md:grid md:grid-cols-2 xl:grid-cols-4">
      <BaseColorPicker
        v-model="colorDanger"
        :label="t('Danger color')"
      />
      <BaseColorPicker
        v-model="colorDangerGradient"
        :label="t('Danger color hover/background')"
      />
    </div>

    <div class="flex flex-col gap-2 mb-3 md:grid md:grid-cols-2 xl:grid-cols-4">
      <BaseColorPicker
        v-model="formColor"
        :label="t('Form outline color')"
      />
    </div>
  </div>

  <!-- Simple mode -->
  <div v-show="!isAdvancedMode">
    <div class="flex flex-col gap-2 mb-3 md:grid md:grid-cols-2 xl:grid-cols-4">
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

    <div class="flex flex-col gap-2 mb-3 md:grid md:grid-cols-2 xl:grid-cols-4">
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

    <div class="flex flex-col gap-2 mb-3 md:grid md:grid-cols-2 xl:grid-cols-4">
      <BaseColorPicker
        v-model="formColor"
        :label="t('Form outline color')"
      />
    </div>
  </div>

  <div class="flex flex-wrap mb-4 gap-3">
    <BaseButton
      type="primary"
      icon="send"
      :label="t('Save')"
      @click="saveColors"
    />
    <BaseButton
      type="black"
      icon="cog"
      :label="isAdvancedMode ? t('Hide advanced mode') : t('Show advanced mode')"
      @click="isAdvancedMode = !isAdvancedMode"
    />
  </div>

  <hr />
  <h5 class="mb-4">{{ t("You can see examples of how chamilo will look here") }}</h5>

  <div class="mb-4">
    <p class="mb-3 text-lg">{{ t("Buttons") }}</p>
    <div class="flex flex-row flex-wrap mb-3">
      <BaseButton
        class="mr-2 mb-2"
        :label="t('Primary')"
        type="primary"
        icon="eye-on"
      />
      <BaseButton
        class="mr-2 mb-2"
        :label="t('Primary alternative')"
        type="primary-alternative"
        icon="eye-on"
      />
      <BaseButton
        class="mr-2 mb-2"
        :label="t('Secondary')"
        type="secondary"
        icon="eye-on"
      />
      <BaseButton
        class="mr-2 mb-2"
        :label="t('Tertiary')"
        type="black"
        icon="eye-on"
      />
    </div>
    <div class="flex flex-row flex-wrap mb-3">
      <BaseButton
        class="mr-2 mb-2"
        :label="t('Success')"
        type="success"
        icon="send"
      />
      <BaseButton
        class="mr-2 mb-2"
        :label="t('Info')"
        type="info"
        icon="send"
      />
      <BaseButton
        class="mr-2 mb-2"
        :label="t('Warning')"
        type="warning"
        icon="send"
      />
      <BaseButton
        class="mr-2 mb-2"
        :label="t('Danger')"
        type="danger"
        icon="delete"
      />
    </div>
    <div class="flex flex-row flex-wrap mb-3">
      <BaseButton
        class="mr-2 mb-2"
        :label="t('Disabled')"
        type="primary"
        icon="eye-on"
        disabled
      />
      <BaseButton
        class="mr-2 mb-2"
        type="primary"
        icon="cog"
        only-icon
      />
    </div>
  </div>

  <div class="mb-4">
    <p class="mb-3 text-lg">{{ t("Dropdowns") }}</p>
    <div class="flex flex-row gap-3">
      <BaseButton
        class="mr-3 mb-2"
        type="primary"
        icon="cog"
        popup-identifier="menu"
        only-icon
        @click="toggle"
      />
      <BaseMenu
        id="menu"
        ref="menu"
        :model="menuItems"
      />
      <BaseDropdown
        v-model="dropdown"
        class="w-36"
        input-id="dropdown"
        option-label="label"
        option-value="value"
        :label="t('Dropdown')"
        :options="[
          {
            label: t('Option 1'),
            value: 'option_1',
          },
          {
            label: t('Option 2'),
            value: 'option_2',
          },
          {
            label: t('Option 3'),
            value: 'option_3',
          },
        ]"
        name="dropdown"
      />
    </div>
  </div>

  <div class="mb-4">
    <p class="mb-3 text-lg">{{ t("Checkbox and radio buttons") }}</p>
    <BaseCheckbox
      id="check1"
      v-model="checkbox1"
      :label="t('Checkbox 1')"
      name="checkbox1"
    />
    <BaseCheckbox
      id="check2"
      v-model="checkbox2"
      :label="t('Checkbox 2')"
      name="checkbox2"
    />
    <div class="mb-2"></div>
    <BaseRadioButtons
      v-model="radioValue"
      :options="radioButtons"
      :initial-value="radioValue"
      name="radio"
    />
  </div>

  <div class="mb-4">
    <p class="mb-3 text-lg">{{ t("Toggle") }}</p>
    <BaseToggleButton
      :model-value="toggleState"
      :off-label="t('Show all')"
      :on-label="t('Hide all')"
      off-icon="eye-on"
      on-icon="eye-off"
      size="normal"
      without-borders
      @update:model-value="toggleState = !toggleState"
    />
  </div>

  <div class="mb-4">
    <p class="mb-3 text-lg">{{ t("Forms") }}</p>
    <BaseInputText
      :label="t('This is the default form')"
      :model-value="null"
    />
    <BaseInputText
      :label="t('This is a form with an error')"
      :is-invalid="true"
      :model-value="null"
    />
    <BaseInputDate
      id="date"
      :label="t('Date')"
      class="w-32"
    />
  </div>

  <div class="mb-4">
    <p class="mb-3 text-lg">{{ t("Dialogs") }}</p>
    <BaseButton
      :label="t('Show dialog')"
      type="black"
      icon="eye-on"
      @click="isDialogVisible = true"
    />
    <BaseDialogConfirmCancel
      :title="t('Dialog example')"
      :is-visible="isDialogVisible"
      @confirm-clicked="isDialogVisible = false"
      @cancel-clicked="isDialogVisible = false"
    />
  </div>
  <div class="mb-4">
    <p class="mb-3 text-lg">{{ t("Some more elements") }}</p>
    <div class="course-tool cursor-pointer">
      <div class="course-tool__link hover:primary-gradient hover:bg-primary-gradient/10">
        <span
          class="course-tool__icon mdi mdi-bookshelf"
          aria-hidden="true"
        />
      </div>
      <p class="course-tool__title">{{ t("Documents") }}</p>
    </div>
  </div>
</template>

<script setup>
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import { useI18n } from "vue-i18n"
import BaseMenu from "../../components/basecomponents/BaseMenu.vue"
import { provide, ref, watch } from "vue"
import BaseCheckbox from "../../components/basecomponents/BaseCheckbox.vue"
import BaseRadioButtons from "../../components/basecomponents/BaseRadioButtons.vue"
import BaseDialogConfirmCancel from "../../components/basecomponents/BaseDialogConfirmCancel.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseColorPicker from "../../components/basecomponents/BaseColorPicker.vue"
import { useTheme } from "../../composables/theme"
import axios from "axios"
import { useNotification } from "../../composables/notification"
import BaseDropdown from "../../components/basecomponents/BaseDropdown.vue"
import BaseInputDate from "../../components/basecomponents/BaseInputDate.vue"
import BaseToggleButton from "../../components/basecomponents/BaseToggleButton.vue"
import Color from "colorjs.io"

const { t } = useI18n()
const { getColorTheme, getColors } = useTheme()
const { showSuccessNotification, showErrorNotification } = useNotification()

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

const saveColors = async () => {
  let colors = getColors()
  try {
    await axios.post("/api/color_themes", {
      variables: colors,
    })
    showSuccessNotification(t("Colors updated"))
  } catch (error) {
    showErrorNotification(error)
    console.error(error)
  }
}

const isAdvancedMode = ref(true)

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

// properties for example components
const menu = ref("menu")
const menuItems = [{ label: t("Item 1") }, { label: t("Item 2") }, { label: t("Item 3") }]
const toggle = (event) => {
  menu.value.toggle(event)
}
const dropdown = ref("")

const checkbox1 = ref(true)
const checkbox2 = ref(false)

const radioButtons = [
  { label: t("Value 1"), value: "value1" },
  { label: t("Value 2"), value: "value2" },
  { label: t("Value 3"), value: "value3" },
]
const radioValue = ref("value1")

const isDialogVisible = ref(false)

const toggleState = ref(true)

// needed for course tool
const isSorting = ref(false)
const isCustomizing = ref(false)
provide("isSorting", isSorting)
provide("isCustomizing", isCustomizing)
</script>
