<script setup>
import { provide, ref } from "vue"
import { useI18n } from "vue-i18n"
import BaseInputDate from "../basecomponents/BaseInputDate.vue"
import BaseButton from "../basecomponents/BaseButton.vue"
import BaseMenu from "../basecomponents/BaseMenu.vue"
import BaseDialogConfirmCancel from "../basecomponents/BaseDialogConfirmCancel.vue"
import BaseDropdown from "../basecomponents/BaseDropdown.vue"
import BaseRadioButtons from "../basecomponents/BaseRadioButtons.vue"
import BaseCheckbox from "../basecomponents/BaseCheckbox.vue"
import BaseInputText from "../basecomponents/BaseInputText.vue"
import BaseToggleButton from "../basecomponents/BaseToggleButton.vue"

const { t } = useI18n()

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

<template>
  <div class="admin-colors__settings-preview">
    <h6>{{ t("You can see examples of how chamilo will look here") }}</h6>

    <div>
      <p class="mb-3 text-lg">{{ t("Buttons") }}</p>
      <div class="flex flex-row flex-wrap mb-3">
        <BaseButton
          :label="t('Primary')"
          class="mr-2 mb-2"
          icon="eye-on"
          type="primary"
        />
        <BaseButton
          :label="t('Primary alternative')"
          class="mr-2 mb-2"
          icon="eye-on"
          type="primary-alternative"
        />
        <BaseButton
          :label="t('Secondary')"
          class="mr-2 mb-2"
          icon="eye-on"
          type="secondary"
        />
        <BaseButton
          :label="t('Tertiary')"
          class="mr-2 mb-2"
          icon="eye-on"
          type="black"
        />
      </div>
      <div class="flex flex-row flex-wrap mb-3">
        <BaseButton
          :label="t('Success')"
          class="mr-2 mb-2"
          icon="send"
          type="success"
        />
        <BaseButton
          :label="t('Info')"
          class="mr-2 mb-2"
          icon="send"
          type="info"
        />
        <BaseButton
          :label="t('Warning')"
          class="mr-2 mb-2"
          icon="send"
          type="warning"
        />
        <BaseButton
          :label="t('Danger')"
          class="mr-2 mb-2"
          icon="delete"
          type="danger"
        />
      </div>
      <div class="flex flex-row flex-wrap mb-3">
        <BaseButton
          :label="t('Disabled')"
          class="mr-2 mb-2"
          disabled
          icon="eye-on"
          type="primary"
        />
        <BaseButton
          class="mr-2 mb-2"
          icon="cog"
          only-icon
          type="primary"
        />
      </div>
    </div>

    <div>
      <p class="mb-3 text-lg">{{ t("Dropdowns") }}</p>
      <div class="flex flex-row gap-3">
        <BaseButton
          class="mr-3 mb-2"
          icon="cog"
          only-icon
          popup-identifier="menu"
          type="primary"
          @click="toggle"
        />
        <BaseMenu
          id="menu"
          ref="menu"
          :model="menuItems"
        />
        <BaseDropdown
          v-model="dropdown"
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
          class="w-36"
          input-id="dropdown"
          name="dropdown"
          option-label="label"
          option-value="value"
        />
      </div>
    </div>

    <div>
      <p class="mb-3 text-lg">{{ t("Checkbox and radio buttons") }}</p>
      <div class="flex flex-col md:flex-row gap-3 md:gap-5">
        <BaseRadioButtons
          v-model="radioValue"
          :initial-value="radioValue"
          :options="radioButtons"
          name="radio"
        />
        <div>
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
        </div>
      </div>
    </div>

    <div>
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

    <div>
      <p class="mb-3 text-lg">{{ t("Forms") }}</p>
      <BaseInputText
        :label="t('This is the default form')"
        :model-value="null"
      />
      <BaseInputText
        :is-invalid="true"
        :label="t('This is a form with an error')"
        :model-value="null"
      />
      <BaseInputDate
        id="date"
        :label="t('Date')"
        class="w-32"
      />
    </div>

    <div>
      <p class="mb-3 text-lg">{{ t("Dialogs") }}</p>
      <BaseButton
        :label="t('Show dialog')"
        icon="eye-on"
        type="black"
        @click="isDialogVisible = true"
      />
      <BaseDialogConfirmCancel
        :is-visible="isDialogVisible"
        :title="t('Dialog example')"
        @confirm-clicked="isDialogVisible = false"
        @cancel-clicked="isDialogVisible = false"
      />
    </div>
    <div>
      <p class="mb-3 text-lg">{{ t("Some more elements") }}</p>
      <div class="course-tool cursor-pointer">
        <div class="course-tool__link hover:primary-gradient hover:bg-primary-gradient/10">
          <span
            aria-hidden="true"
            class="course-tool__icon mdi mdi-bookshelf"
          />
        </div>
        <p class="course-tool__title">{{ t("Documents") }}</p>
      </div>
    </div>
  </div>
</template>
