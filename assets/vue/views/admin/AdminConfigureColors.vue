<template class="personal-theme">
  <h4 class="mb-4">{{ t("Configure chamilo colors") }}</h4>

  <div class="grid grid-cols-2 gap-2 mb-8">
    <BaseColorPicker
      v-model="primaryColor"
      :label="t('Pick primary color')"
    />
    <BaseColorPicker
      v-model="primaryColorGradient"
      :label="t('Pick primary color gradient')"
    />
    <BaseColorPicker
      v-model="secondaryColor"
      :label="t('Pick secondary color')"
    />
    <BaseColorPicker
      v-model="secondaryColorGradient"
      :label="t('Pick secondary color gradient')"
    />
    <BaseColorPicker
      v-model="tertiaryColor"
      :label="t('Pick tertiary color')"
    />
    <BaseColorPicker
      v-model="tertiaryColorGradient"
      :label="t('Pick tertiary color gradient')"
    />
    <BaseColorPicker
      v-model="successColor"
      :label="t('Pick success color')"
    />
    <BaseColorPicker
      v-model="successColorGradient"
      :label="t('Pick success color gradient')"
    />
    <BaseColorPicker
      v-model="dangerColor"
      :label="t('Pick danger color')"
    />
  </div>

  <div class="flex flex-wrap mb-4">
    <BaseButton
      type="primary"
      icon="send"
      :label="t('Save')"
      @click="saveColors"
    />
  </div>

  <hr />
  <h5 class="mb-4">{{ t("You can see examples of how chamilo will look here") }}</h5>

  <div class="mb-4">
    <p class="mb-3 text-lg">{{ t("Buttons") }}</p>
    <div class="flex flex-row flex-wrap">
      <BaseButton
        class="mr-2 mb-2"
        :label="t('Button')"
        type="primary"
        icon="eye-on"
      />
      <BaseButton
        class="mr-2 mb-2"
        :label="t('Disabled')"
        type="primary"
        icon="eye-on"
        disabled
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
      <BaseButton
        class="mr-2 mb-2"
        type="primary"
        icon="cog"
        only-icon
      />
      <BaseButton
        class="mr-2 mb-2"
        :label="t('Success')"
        type="success"
        icon="send"
      />
      <BaseButton
        class="mr-2 mb-2"
        :label="t('Danger')"
        type="danger"
        icon="delete"
      />
    </div>
  </div>

  <div class="mb-4">
    <p class="mb-3 text-lg">{{ t("Menu on button pressed") }}</p>
    <BaseButton
      class="mr-2 mb-2"
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
    ></BaseMenu>
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
    <p class="mb-3 text-lg">Forms</p>
    <BaseInputText
      v-model="inputText"
      :label="t('This is a text example')"
    />
  </div>

  <div class="mb-4">
    <p class="mb-3 text-lg">Dialogs</p>
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
</template>

<script setup>
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import { useI18n } from "vue-i18n"
import BaseMenu from "../../components/basecomponents/BaseMenu.vue"
import { ref } from "vue"
import BaseCheckbox from "../../components/basecomponents/BaseCheckbox.vue"
import BaseRadioButtons from "../../components/basecomponents/BaseRadioButtons.vue"
import BaseDialogConfirmCancel from "../../components/basecomponents/BaseDialogConfirmCancel.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseColorPicker from "../../components/basecomponents/BaseColorPicker.vue"
import { useTheme } from "../../composables/theme"
import axios from "axios"

const { t } = useI18n()
const { getColorTheme, getColors } = useTheme()

let primaryColor = getColorTheme("--color-primary-base")
let primaryColorGradient = getColorTheme("--color-primary-gradient")
let secondaryColor = getColorTheme("--color-secondary-base")
let secondaryColorGradient = getColorTheme("--color-secondary-gradient")
let tertiaryColor = getColorTheme("--color-tertiary-base")
let tertiaryColorGradient = getColorTheme("--color-tertiary-gradient")
let successColor = getColorTheme("--color-success-base")
let successColorGradient = getColorTheme("--color-success-gradient")
let dangerColor = getColorTheme("--color-danger-base")

const saveColors = async () => {
  let colors = getColors()
  // TODO send colors to backend, then notify if was correct or incorrect
  await axios.post("/api/color_themes", {
    variables: colors,
  })
}

const menu = ref("menu")
const menuItems = [{ label: t("Item 1") }, { label: t("Item 2") }, { label: t("Item 3") }]
const toggle = (event) => {
  menu.value.toggle(event)
}

const checkbox1 = ref(true)
const checkbox2 = ref(false)

const radioButtons = [
  { label: t("Value 1"), value: "value1" },
  { label: t("Value 2"), value: "value2" },
  { label: t("Value 3"), value: "value3" },
]
const radioValue = ref("value1")

const isDialogVisible = ref(false)

const inputText = ref("")
</script>
