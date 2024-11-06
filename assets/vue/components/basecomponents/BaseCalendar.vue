<script setup>
import {computed, onMounted, ref, watch} from "vue"
import Calendar from "primevue/calendar"
import {calendarLocales} from "../../utils/calendarLocales"
import {useLocale} from "../../composables/locale"
import {usePrimeVue} from "primevue/config"

const { appLocale } = useLocale()
const localePrefix = ref(getLocalePrefix(appLocale.value))
const props = defineProps({
  modelValue: {
    type: [String, Date, Array],
    required: false,
    default: null,
  },
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
  showIcon: {
    type: Boolean,
    required: false,
    default: false,
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
  }
})

const model = ref(
  props.modelValue
    ? Array.isArray(props.modelValue)
      ? props.modelValue.map((date) => new Date(date))
      : new Date(props.modelValue)
    : null
);

const emit = defineEmits(["update:modelValue"])

watch(model, (newValue) => {
  emit("update:modelValue", newValue)
})

function getLocalePrefix(locale) {
  const defaultLang = "en"
  return typeof locale === "string" ? locale.split('_')[0] : defaultLang
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

const primevue = usePrimeVue();
onMounted(() => {
  if (selectedLocale.value) {
    primevue.config.locale = selectedLocale.value
  }
})
</script>
<template>
  <div class="field">
    <div class="p-float-label">
      <Calendar
        :id="id"
        v-model="model"
        :class="{ 'p-invalid': isInvalid }"
        :manual-input="type !== 'range'"
        :selection-mode="type"
        :show-icon="showIcon"
        :show-time="showTime"
        :locale="selectedLocale"
        :date-format="dateFormat"
      />
      <label v-text="label"/>
    </div>
    <small></small>
  </div>
</template>
