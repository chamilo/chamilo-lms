<script setup>
import { provide, ref } from "vue"
import { useI18n } from "vue-i18n"
import BaseCalendar from "../basecomponents/BaseCalendar.vue"
import BaseButton from "../basecomponents/BaseButton.vue"
import BaseMenu from "../basecomponents/BaseMenu.vue"
import BaseDialogConfirmCancel from "../basecomponents/BaseDialogConfirmCancel.vue"
import BaseSelect from "../basecomponents/BaseSelect.vue"
import BaseRadioButtons from "../basecomponents/BaseRadioButtons.vue"
import BaseCheckbox from "../basecomponents/BaseCheckbox.vue"
import BaseInputText from "../basecomponents/BaseInputText.vue"
import BaseToggleButton from "../basecomponents/BaseToggleButton.vue"

const { t } = useI18n()

// Properties for sample components
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

    <div class="space-y-2">
      <p class="text-lg">{{ t("Buttons") }}</p>

      <div class="flex gap-2 flex-wrap">
        <BaseButton
          :label="t('Primary')"
          icon="search"
          only-icon
          size="small"
          type="primary"
        />
        <BaseButton
          :label="t('Primary alternative')"
          icon="search"
          only-icon
          size="small"
          type="primary-alternative"
        />
        <BaseButton
          :label="t('Secondary')"
          icon="search"
          only-icon
          size="small"
          type="secondary"
        />
        <BaseButton
          :label="t('Tertiary')"
          icon="search"
          only-icon
          size="small"
          type="tertiary"
        />
        <BaseButton
          :label="t('Black')"
          icon="search"
          only-icon
          size="small"
          type="black"
        />
        <BaseButton
          :label="t('Success')"
          icon="search"
          only-icon
          size="small"
          type="success"
        />
        <BaseButton
          :label="t('Info')"
          icon="search"
          only-icon
          size="small"
          type="info"
        />
        <BaseButton
          :label="t('Warning')"
          icon="search"
          only-icon
          size="small"
          type="warning"
        />
        <BaseButton
          :label="t('Danger')"
          icon="search"
          only-icon
          size="small"
          type="danger"
        />
      </div>
      <div class="flex gap-2 flex-wrap">
        <BaseButton
          :label="t('Primary')"
          icon="search"
          size="small"
          type="primary-text"
        />
        <BaseButton
          :label="t('Secondary')"
          icon="search"
          size="small"
          type="secondary-text"
        />
        <BaseButton
          :label="t('Tertiary')"
          icon="search"
          size="small"
          type="tertiary-text"
        />
        <BaseButton
          :label="t('Success')"
          icon="search"
          size="small"
          type="success-text"
        />
        <BaseButton
          :label="t('Info')"
          icon="search"
          size="small"
          type="info-text"
        />
        <BaseButton
          :label="t('Warning')"
          icon="search"
          size="small"
          type="warning-text"
        />
        <BaseButton
          :label="t('Danger')"
          icon="search"
          size="small"
          type="danger-text"
        />
      </div>
      <div class="flex gap-2 flex-wrap">
        <BaseButton
          :label="t('Primary')"
          icon="search"
          size="small"
          type="primary"
        />
        <BaseButton
          :label="t('Secondary')"
          icon="search"
          size="small"
          type="secondary"
        />
        <BaseButton
          :label="t('Tertiary')"
          icon="search"
          size="small"
          type="tertiary"
        />
        <BaseButton
          :label="t('Success')"
          icon="search"
          size="small"
          type="success"
        />
        <BaseButton
          :label="t('Info')"
          icon="search"
          size="small"
          type="info"
        />
        <BaseButton
          :label="t('Warning')"
          icon="search"
          size="small"
          type="warning"
        />
        <BaseButton
          :label="t('Danger')"
          icon="search"
          size="small"
          type="danger"
        />
      </div>
      <div class="flex gap-2 flex-wrap">
        <BaseButton
          :label="t('Primary alternative')"
          icon="search"
          size="small"
          type="primary-alternative"
        />
        <BaseButton
          :label="t('Black')"
          icon="search"
          size="small"
          type="black"
        />
      </div>
      <div class="flex gap-2 flex-wrap">
        <BaseButton
          :label="t('Primary')"
          disabled
          icon="search"
          size="small"
          type="primary"
        />
        <BaseButton
          :label="t('Secondary')"
          disabled
          icon="search"
          size="small"
          type="secondary"
        />
        <BaseButton
          :label="t('Tertiary')"
          disabled
          icon="search"
          size="small"
          type="tertiary"
        />
        <BaseButton
          :label="t('Success')"
          disabled
          icon="search"
          size="small"
          type="success"
        />
        <BaseButton
          :label="t('Info')"
          disabled
          icon="search"
          size="small"
          type="info"
        />
        <BaseButton
          :label="t('Warning')"
          disabled
          icon="search"
          size="small"
          type="warning"
        />
        <BaseButton
          :label="t('Danger')"
          disabled
          icon="search"
          size="small"
          type="danger"
        />
      </div>
      <div class="flex gap-2 flex-wrap">
        <BaseButton
          :label="t('Primary alternative')"
          disabled
          icon="search"
          size="small"
          type="primary-alternative"
        />
        <BaseButton
          :label="t('Black')"
          disabled
          icon="search"
          size="small"
          type="black"
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
        <BaseSelect
          id="dropdown"
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
      <BaseCalendar
        id="dates"
        :label="t('Date')"
        show-time
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
          <svg
            class="course-tool__shadow"
            fill="none"
            viewBox="0 0 117 105"
            xmlns="http://www.w3.org/2000/svg"
          >
            <path
              class="fill-current"
              d="M104.167 20.2899C104.167 43.3465 116.11 59.1799 116.11 70.2899C116.11 81.3999 109.723 104.733 58.6133 104.733C7.50333 104.733 0 73.3432 0 61.1232C0 3.89987 104.167 -20.5435 104.167 20.2899Z"
            />
          </svg>
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
