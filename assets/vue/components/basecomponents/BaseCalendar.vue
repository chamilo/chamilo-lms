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

const { t } = useI18n()
const platformConfigStore = usePlatformConfig()
const timepicketIncrement = Number(platformConfigStore.getSetting("platform.timepicker_increment"))

const modelValue = defineModel({
  type: [Date, Array, String, undefined, null],
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

function getDateFormat(locale) {
  switch (locale) {
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
}

const dateFormat = computed(() => {
  return getDateFormat(localePrefix.value)
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
        :locale="selectedLocale"
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
            <button
              type="button"
              class="base-calendar-footer__button base-calendar-footer__button--secondary"
              @click="onCancelClick"
            >
              {{ t("Cancel") }}
            </button>
            <button
              type="button"
              class="base-calendar-footer__button base-calendar-footer__button--primary"
              @click="onApplyClick"
            >
              {{ t("OK") }}
            </button>
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
<style scoped>
.base-calendar-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
  padding: 0.5rem 0.75rem 0.75rem;
}
.base-calendar-footer__button {
  border-radius: 9999px;
  padding: 0.25rem 0.75rem;
  font-size: 0.75rem;
  border: 1px solid transparent;
  cursor: pointer;
}
.base-calendar-footer__button--secondary {
  background-color: transparent;
  border-color: var(--gray-40, #d4d4d4);
}
.base-calendar-footer__button--primary {
  background-color: var(--primary-color, #0d9488);
  color: #ffffff;
}
</style>
