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

// Internal value used by the DatePicker
const internalValue = ref(modelValue.value)

// Sync internal value when the external model changes (e.g. reset from parent)
watch(
  () => modelValue.value,
  (newValue) => {
    internalValue.value = newValue
  },
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

// When showTime is false, we keep the old behavior: update parent immediately
watch(
  () => internalValue.value,
  (newValue) => {
    if (!props.showTime) {
      modelValue.value = newValue
    }
  },
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

// User confirms the current selection
const onApplyClick = () => {
  modelValue.value = internalValue.value
  hideOverlay()
}

// User cancels the selection and restores external value
const onCancelClick = () => {
  internalValue.value = modelValue.value
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
        :input-id="id"
        :invalid="isInvalid"
        :manual-input="allowManualInput"
        :selection-mode="type"
        :show-time="showTime"
        :step-minute="timepicketIncrement"
        fluid
        icon-display="input"
        show-icon
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
