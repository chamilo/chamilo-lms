<template>
  <div
    v-if="fields.length"
    class="flex flex-col gap-4"
  >
    <template
      v-for="field in fields"
      :key="field.id"
    >
      <BaseCheckbox
        v-if="field.valueType === FIELD_CHECKBOX"
        :id="fieldId(field)"
        v-model="values[field.id]"
        :label="field.label"
        :name="fieldName(field)"
      />

      <BaseRadioButtons
        v-else-if="field.valueType === FIELD_RADIO"
        v-model="values[field.id]"
        :name="fieldName(field)"
        :options="field.options"
        :title="field.label"
      />

      <BaseSelect
        v-else-if="field.valueType === FIELD_SELECT"
        :id="fieldId(field)"
        v-model="values[field.id]"
        :label="field.label"
        :message-text="field.helpText || null"
        :name="fieldName(field)"
        :options="field.options"
        allow-clear
      />

      <div
        v-else-if="field.valueType === FIELD_MULTI_SELECT || field.valueType === FIELD_TAG"
        class="field"
      >
        <FloatLabel variant="on">
          <MultiSelect
            v-model="values[field.id]"
            :input-id="fieldId(field)"
            :name="fieldName(field)"
            :options="field.options"
            display="chip"
            fluid
            option-label="label"
            option-value="value"
          />
          <label :for="fieldId(field)">{{ field.label }}</label>
        </FloatLabel>
        <small
          v-if="field.helpText"
          class="form-text text-muted"
        >{{ field.helpText }}</small>
      </div>

      <BaseCalendar
        v-else-if="field.valueType === FIELD_DATE || field.valueType === FIELD_DATETIME"
        :id="fieldId(field)"
        v-model="values[field.id]"
        :label="field.label"
        :show-time="field.valueType === FIELD_DATETIME"
      />

      <BaseInputNumber
        v-else-if="field.valueType === FIELD_INTEGER || field.valueType === FIELD_FLOAT || field.valueType === FIELD_DURATION"
        :id="fieldId(field)"
        v-model="values[field.id]"
        :help-text="field.helpText || null"
        :label="field.label"
        :step="field.valueType === FIELD_FLOAT ? 0.01 : 1"
      />

      <div
        v-else-if="field.valueType === FIELD_TEXTAREA"
        class="field"
      >
        <FloatLabel variant="on">
          <Textarea
            :id="fieldId(field)"
            v-model="values[field.id]"
            :name="fieldName(field)"
            auto-resize
            fluid
            rows="4"
          />
          <label :for="fieldId(field)">{{ field.label }}</label>
        </FloatLabel>
        <small
          v-if="field.helpText"
          class="form-text text-muted"
        >{{ field.helpText }}</small>
      </div>

      <div
        v-else-if="field.valueType === FIELD_FILE_IMAGE || field.valueType === FIELD_FILE"
        class="flex flex-col gap-2"
      >
        <span class="text-sm font-medium text-gray-90">{{ field.label }}</span>
        <span
          v-if="field.assetName"
          class="text-sm text-gray-50"
        >{{ field.assetName }}</span>
        <BaseFileUpload
          :accept="field.valueType === FIELD_FILE_IMAGE ? 'image/png,image/jpeg,image/gif' : undefined"
          :label="t('Browse')"
          @file-selected="emit('file-selected', field.id, $event)"
        />
        <small
          v-if="field.helpText"
          class="form-text text-muted"
        >{{ field.helpText }}</small>
      </div>

      <BaseInputText
        v-else
        :id="fieldId(field)"
        v-model="values[field.id]"
        :help-text="field.helpText || ''"
        :label="field.label"
        :name="fieldName(field)"
      />
    </template>
  </div>
</template>

<script setup>
import { computed } from "vue"
import { useI18n } from "vue-i18n"
import FloatLabel from "primevue/floatlabel"
import MultiSelect from "primevue/multiselect"
import Textarea from "primevue/textarea"
import BaseCalendar from "../basecomponents/BaseCalendar.vue"
import BaseCheckbox from "../basecomponents/BaseCheckbox.vue"
import BaseFileUpload from "../basecomponents/BaseFileUpload.vue"
import BaseInputNumber from "../basecomponents/BaseInputNumber.vue"
import BaseInputText from "../basecomponents/BaseInputText.vue"
import BaseRadioButtons from "../basecomponents/BaseRadioButtons.vue"
import BaseSelect from "../basecomponents/BaseSelect.vue"

const FIELD_TEXTAREA = 2
const FIELD_RADIO = 3
const FIELD_SELECT = 4
const FIELD_MULTI_SELECT = 5
const FIELD_DATE = 6
const FIELD_DATETIME = 7
const FIELD_TAG = 10
const FIELD_CHECKBOX = 13
const FIELD_INTEGER = 15
const FIELD_FILE_IMAGE = 16
const FIELD_FLOAT = 17
const FIELD_FILE = 18
const FIELD_DURATION = 28

const props = defineProps({
  fields: {
    type: Array,
    default: () => [],
  },
  modelValue: {
    type: Object,
    required: true,
  },
})

const emit = defineEmits(["update:modelValue", "file-selected"])
const { t } = useI18n()

const values = computed({
  get: () => props.modelValue,
  set: (value) => emit("update:modelValue", value),
})

function fieldId(field) {
  return `lp-extra-${field.id}`
}

function fieldName(field) {
  return `extra_${field.variable}`
}
</script>
