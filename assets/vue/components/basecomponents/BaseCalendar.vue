<script setup>
import { computed, onMounted, ref, watch } from "vue"
import DatePicker from "primevue/datepicker"
import FloatLabel from "primevue/floatlabel"
import Message from "primevue/message"
import { usePlatformConfig } from "../../store/platformConfig"
import { calendarLocales } from "../../utils/calendarLocales"
import { useLocale } from "../../composables/locale"
import { usePrimeVue } from "primevue/config"

const platformConfigStore = usePlatformConfig()
const timepicketIncrement = Number(platformConfigStore.getSetting("platform.timepicker_increment"))

const modelValue = defineModel({
  type: [Date, Array, String, undefined, null],
  required: false,
  default: null,
})

const { appLocale } = useLocale()
const localePrefix = ref(getLocalePrefix(appLocale.value))

defineProps({
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
</script>
<template>
  <div class="field">
    <FloatLabel variant="on">
      <DatePicker
        v-model="modelValue"
        :date-format="dateFormat"
        :input-id="id"
        :invalid="isInvalid"
        :locale="selectedLocale"
        :manual-input="type !== 'range'"
        :selection-mode="type"
        :show-time="showTime"
        :step-minute="timepicketIncrement"
        fluid
        icon-display="input"
        show-icon
      />
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
