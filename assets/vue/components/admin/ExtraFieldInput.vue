<template>
  <BaseInputText
    v-if="1 === field.valueType"
    :id="inputId"
    v-model="textModel"
    :disabled="disabled"
    :help-text="field.helperText"
    :label="field.displayText"
    :name="inputId"
  />

  <BaseTextArea
    v-else-if="2 === field.valueType"
    :id="inputId"
    v-model="textModel"
    :disabled="disabled"
    :label="field.displayText"
    :name="inputId"
    rows="3"
  />

  <BaseRadioButtons
    v-else-if="3 === field.valueType"
    v-model="textModel"
    :disabled="disabled"
    :name="inputId"
    :options="optionItems"
    :title="field.displayText"
  />

  <BaseSelect
    v-else-if="4 === field.valueType"
    :id="inputId"
    v-model="textModel"
    :disabled="disabled"
    :label="field.displayText"
    :options="optionItems"
    allow-clear
  />

  <BaseMultiSelect
    v-else-if="isMultipleValueType"
    v-model="arrayModel"
    :disabled="disabled"
    :input-id="inputId"
    :label="field.displayText"
    :options="optionItems"
  />

  <BaseCalendar
    v-else-if="6 === field.valueType"
    :id="inputId"
    v-model="dateModel"
    :disabled="disabled"
    :label="field.displayText"
  />

  <BaseCalendar
    v-else-if="7 === field.valueType"
    :id="inputId"
    v-model="dateModel"
    :disabled="disabled"
    :label="field.displayText"
    show-time
  />

  <BaseInputNumber
    v-else-if="15 === field.valueType"
    :id="inputId"
    v-model="numberModel"
    :disabled="disabled"
    :label="field.displayText"
  />

  <BaseInputNumber
    v-else-if="17 === field.valueType"
    :id="inputId"
    v-model="numberModel"
    :disabled="disabled"
    :label="field.displayText"
    :step="0.1"
  />

  <BaseCheckbox
    v-else-if="13 === field.valueType"
    :id="inputId"
    v-model="booleanModel"
    :disabled="disabled"
    :label="field.displayText"
    :name="inputId"
  />

  <BaseInputText
    v-else
    :id="inputId"
    v-model="textModel"
    :disabled="disabled"
    :help-text="t('This field type is not fully supported yet — raw value only.')"
    :label="field.displayText"
    :name="inputId"
  />
</template>

<script setup>
import { computed } from "vue"
import { useI18n } from "vue-i18n"
import BaseCalendar from "../basecomponents/BaseCalendar.vue"
import BaseCheckbox from "../basecomponents/BaseCheckbox.vue"
import BaseInputNumber from "../basecomponents/BaseInputNumber.vue"
import BaseInputText from "../basecomponents/BaseInputText.vue"
import BaseMultiSelect from "../basecomponents/BaseMultiSelect.vue"
import BaseRadioButtons from "../basecomponents/BaseRadioButtons.vue"
import BaseSelect from "../basecomponents/BaseSelect.vue"
import BaseTextArea from "../basecomponents/BaseTextArea.vue"

// Mirrors Chamilo\CoreBundle\Entity\ExtraField's FIELD_TYPE_* constants.
const SELECT_MULTIPLE = 5
const CHECKBOX = 13

const { t } = useI18n()

const props = defineProps({
  field: {
    type: Object,
    required: true,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
})

const model = defineModel({ default: "" })

const inputId = computed(() => `extra_${props.field.variable}`)
const optionItems = computed(() => props.field.options.map((o) => ({ label: o.label, value: o.value })))
const isMultipleValueType = computed(
  () =>
    (SELECT_MULTIPLE === props.field.valueType || CHECKBOX === props.field.valueType) && optionItems.value.length > 0,
)

const textModel = computed({
  get: () => (Array.isArray(model.value) ? "" : (model.value ?? "")),
  set: (value) => {
    model.value = value
  },
})

const arrayModel = computed({
  get: () => (Array.isArray(model.value) ? model.value : []),
  set: (value) => {
    model.value = value
  },
})

const dateModel = computed({
  get: () => (model.value ? new Date(model.value) : null),
  set: (value) => {
    model.value = value ? new Date(value).toISOString() : ""
  },
})

const numberModel = computed({
  get: () => (model.value ? Number(model.value) : null),
  set: (value) => {
    model.value = null === value || undefined === value ? "" : String(value)
  },
})

const booleanModel = computed({
  get: () => "1" === model.value,
  set: (value) => {
    model.value = value ? "1" : "0"
  },
})
</script>
