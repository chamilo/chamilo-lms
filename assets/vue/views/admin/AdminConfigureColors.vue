<template class="personal-theme">
  <h4 class="mb-4">{{ t("Configure chamilo colors") }}</h4>

  <div class="flex flex-col md:grid md:grid-cols-2 gap-2 mb-8">
    <BaseColorPicker
      v-model="primaryColor"
      :label="t('Primary color')"
    />
    <BaseColorPicker
      v-model="primaryColorGradient"
      :label="t('Primary color hover')"
    />
    <BaseColorPicker
      v-model="secondaryColor"
      :label="t('Secondary color')"
    />
    <BaseColorPicker
      v-model="secondaryColorGradient"
      :label="t('Secondary color hover')"
    />
    <BaseColorPicker
      v-model="tertiaryColor"
      :label="t('Tertiary color')"
    />
    <BaseColorPicker
      v-model="tertiaryColorGradient"
      :label="t('Tertiary color gradient')"
    />
    <BaseColorPicker
      v-model="successColor"
      :label="t('Success color')"
    />
    <BaseColorPicker
      v-model="successColorGradient"
      :label="t('Success color gradient')"
    />
    <BaseColorPicker
      v-model="dangerColor"
      :label="t('Danger color')"
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
      :model-value="date"
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
import { provide, ref } from "vue"
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

const { t } = useI18n()
const { getColorTheme, getColors } = useTheme()
const { showSuccessNotification, showErrorNotification } = useNotification()

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
  console.log(colors)
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

const date = ref(null)
const toggleState = ref(true)

// needed for course tool
const isSorting = ref(false)
const isCustomizing = ref(false)
provide("isSorting", isSorting)
provide("isCustomizing", isCustomizing)
</script>
