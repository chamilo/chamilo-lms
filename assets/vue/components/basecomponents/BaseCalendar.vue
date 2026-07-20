<script setup>
import { computed, onMounted, ref, watch } from "vue"
import DatePicker from "primevue/datepicker"
import FloatLabel from "primevue/floatlabel"
import Message from "primevue/message"
import { usePlatformConfig } from "../../store/platformConfig"
import { calendarLocales } from "../../utils/calendarLocales"
import { useLocale } from "../../composables/locale"
import { usePrimeVue } from "primevue/config"
import { useI18n } from "vue-i18n"
import BaseButton from "./BaseButton.vue"

const { t } = useI18n()
const platformConfigStore = usePlatformConfig()
/**
 * @type {Number}
 */
const timepicketIncrement = platformConfigStore.getSetting("platform.timepicker_increment")

const modelValue = defineModel({
  type: [Date, Array, String, null],
  required: false,
  default: null,
})

function cloneCalendarValue(value) {
  if (value instanceof Date) {
    return new Date(value.getTime())
  }

  if (Array.isArray(value)) {
    return value.map((item) => cloneCalendarValue(item))
  }

  return value
}

function calendarValuesEqual(left, right) {
  if (left instanceof Date || right instanceof Date) {
    return left instanceof Date && right instanceof Date && left.getTime() === right.getTime()
  }

  if (Array.isArray(left) || Array.isArray(right)) {
    return (
      Array.isArray(left) &&
      Array.isArray(right) &&
      left.length === right.length &&
      left.every((item, index) => calendarValuesEqual(item, right[index]))
    )
  }

  return left === right
}

// Internal value used by the DatePicker.
const internalValue = ref(cloneCalendarValue(modelValue.value))
const valueBeforeOpen = ref(null)
const hasOpenSnapshot = ref(false)

// Sync internal value when the external model changes (e.g. reset from parent).
watch(
  () => modelValue.value,
  (newValue) => {
    if (!calendarValuesEqual(internalValue.value, newValue)) {
      internalValue.value = cloneCalendarValue(newValue)
    }
  },
  { deep: true },
)

const datepickerRef = ref(null)

const { appLocale } = useLocale()
const localePrefix = ref(getLocalePrefix(appLocale.value))

const props = defineProps({
  label: {
    type: String,
    required: true,
  },
  id: {
    type: String,
    required: true,
    default: "",
  },
  type: {
    type: String,
    required: false,
    default: "single",
    validator: (value) => ["single", "range"].includes(value),
  },
  showTime: {
    type: Boolean,
    required: false,
    default: false,
  },
  disabled: {
    type: Boolean,
    required: false,
    default: false,
  },
  isInvalid: {
    type: Boolean,
    required: false,
    default: false,
  },
  errorText: {
    type: String,
    required: false,
    default: null,
  },
  showInline: {
    type: Boolean,
    required: false,
    default: false,
  },
})

function getLocalePrefix(locale) {
  const defaultLang = "en"
  return typeof locale === "string" ? locale.split("_")[0] : defaultLang
}

const dateFormat = computed(() => {
  switch (localePrefix.value) {
    case "en":
      return "mm/dd/yy"
    case "fr":
      return "dd/mm/yy"
    case "de":
      return "dd.mm.yy"
    case "es":
      return "dd/mm/yy"
    default:
      return "dd/mm/yy"
  }
})

const selectedLocale = computed(() => calendarLocales[localePrefix.value] || calendarLocales.en)

const primevue = usePrimeVue()
onMounted(() => {
  if (selectedLocale.value) {
    primevue.config.locale = selectedLocale.value
  }
})

// When showTime is enabled, do NOT allow manual input.
// Manual typing can produce ambiguous strings like "09/01/2025" which might be sent to backend.
const allowManualInput = computed(() => {
  if (props.type === "range") {
    return false
  }
  return !props.showTime
})

// Keep the parent model synchronized with the value displayed by PrimeVue.
// A snapshot is restored when the user explicitly cancels the selection.
watch(
  () => internalValue.value,
  (newValue) => {
    if (!calendarValuesEqual(modelValue.value, newValue)) {
      modelValue.value = cloneCalendarValue(newValue)
    }
  },
  { deep: true },
)

// Safely hide the calendar overlay (PrimeVue internal API)
const hideOverlay = () => {
  const instance = datepickerRef.value
  if (!instance) {
    return
  }

  // PrimeVue DatePicker exposes overlayVisible / hideOverlay in runtime instance
  if (typeof instance.hideOverlay === "function") {
    instance.hideOverlay()
    return
  }

  if ("overlayVisible" in instance) {
    instance.overlayVisible = false
  }
}

const onOverlayShow = () => {
  valueBeforeOpen.value = cloneCalendarValue(modelValue.value)
  hasOpenSnapshot.value = true
}

const onOverlayHide = () => {
  hasOpenSnapshot.value = false
  valueBeforeOpen.value = null
}

// User confirms the current selection.
const onApplyClick = () => {
  modelValue.value = cloneCalendarValue(internalValue.value)
  hasOpenSnapshot.value = false
  valueBeforeOpen.value = null
  hideOverlay()
}

// User cancels the selection and restores the value from before the overlay opened.
const onCancelClick = () => {
  const restoredValue = hasOpenSnapshot.value
    ? cloneCalendarValue(valueBeforeOpen.value)
    : cloneCalendarValue(modelValue.value)

  internalValue.value = restoredValue
  modelValue.value = cloneCalendarValue(restoredValue)
  hasOpenSnapshot.value = false
  valueBeforeOpen.value = null
  hideOverlay()
}
</script>

<template>
  <div class="field">
    <FloatLabel variant="on">
      <DatePicker
        ref="datepickerRef"
        v-model="internalValue"
        :date-format="dateFormat"
        :disabled="disabled"
        :inline="showInline"
        :input-id="id"
        :invalid="isInvalid"
        :manual-input="allowManualInput"
        :selection-mode="type"
        :show-time="showTime"
        :step-minute="timepicketIncrement"
        fluid
        icon-display="input"
        show-icon
        @hide="onOverlayHide"
        @show="onOverlayShow"
      >
        <!-- Custom footer only when using time selection -->
        <template
          v-if="showTime"
          #footer
        >
          <div class="base-calendar-footer">
            <BaseButton
              :label="t('Cancel')"
              icon="close"
              size="small"
              type="black"
              @click="onCancelClick"
            />
            <BaseButton
              :label="t('Select')"
              icon="confirm"
              size="small"
              type="secondary"
              @click="onApplyClick"
            />
          </div>
        </template>
      </DatePicker>
      <label
        :for="id"
        v-text="label"
      />
    </FloatLabel>
    <Message
      v-if="isInvalid"
      size="small"
      severity="seconday"
      variant="simple"
    >
      {{ errorText }}
    </Message>
  </div>
</template>
