<template>
  <div class="space-y-2">
    <BaseInputText
      v-if="['text', 'url', 'date'].includes(field.type)"
      :id="field.key"
      v-model="stringValue"
      :disabled="field.disabled"
      :help-text="translatedHelp"
      :label="translatedLabel"
      :name="field.key"
      :required="field.required"
      :show-required-marker="field.required"
      :type="field.type === 'url' ? 'url' : field.type === 'date' ? 'date' : 'text'"
    />

    <BaseInputNumber
      v-else-if="field.type === 'number'"
      :id="field.key"
      v-model="numberValue"
      :disabled="field.disabled"
      :help-text="translatedHelp"
      :label="translatedLabel"
      :max="field.max"
      :min="field.min"
    />

    <BaseSelect
      v-else-if="field.type === 'select'"
      :id="field.key"
      v-model="modelValue"
      :disabled="field.disabled"
      :label="translatedLabel"
      :name="field.key"
      option-label="label"
      option-value="value"
      :options="translatedOptions"
      :required="field.required"
      :show-required-marker="field.required"
    />

    <BaseRadioButtons
      v-else-if="field.type === 'radio'"
      v-model="radioValue"
      :name="field.key"
      :options="translatedOptions"
      :title="translatedLabel"
    />

    <BaseCheckbox
      v-else-if="field.type === 'checkbox'"
      :id="field.key"
      v-model="booleanValue"
      :disabled="field.disabled"
      :label="translatedLabel"
      :name="field.key"
    />

    <div
      v-else-if="field.type === 'checkbox-list'"
      class="space-y-2"
    >
      <p class="font-semibold text-gray-700">{{ translatedLabel }}</p>
      <BaseCheckbox
        v-for="option in translatedOptions"
        :id="`${field.key}_${option.value}`"
        :key="option.value"
        v-model="arrayValue"
        :label="option.label"
        :name="field.key"
        :value="option.value"
      />
    </div>

    <BaseTextArea
      v-else-if="field.type === 'textarea'"
      :id="field.key"
      v-model="stringValue"
      :label="translatedLabel"
      :name="field.key"
      rows="8"
    />

    <BaseTinyEditor
      v-else-if="field.type === 'editor'"
      :editor-id="field.key"
      v-model="stringValue"
      :full-page="false"
      :help-text="translatedHelp"
      :required="field.required"
      :show-required-marker="field.required"
      :title="translatedLabel"
    />

    <BaseInputText
      v-else-if="field.type === 'tags'"
      :id="field.key"
      v-model="stringValue"
      :help-text="t('Separate tags with commas')"
      :label="translatedLabel"
      :name="field.key"
    />

    <div
      v-else-if="field.type === 'readonly-link'"
      class="space-y-2"
    >
      <label
        :for="field.key"
        class="block text-body-2 font-semibold text-gray-700"
      >
        {{ translatedLabel }}
      </label>
      <div class="flex items-center gap-2">
        <input
          :id="field.key"
          class="min-w-0 flex-1 rounded-lg border border-gray-30 bg-gray-10 px-3 py-2 text-body-2 text-gray-800"
          :name="field.key"
          readonly
          type="text"
          :value="String(field.value || modelValue || '')"
        />
        <BaseButton
          icon="link-external"
          :label="t('Open link')"
          only-icon
          type="primary"
          :to-url="String(field.value || modelValue || '')"
        />
      </div>
      <p
        v-if="translatedHelp"
        class="text-caption text-gray-500"
      >
        {{ translatedHelp }}
      </p>
    </div>

    <div
      v-else-if="field.type === 'readonly'"
      class="rounded-lg border border-gray-25 bg-gray-10 p-4"
    >
      <p class="text-caption font-semibold text-gray-500">{{ translatedLabel }}</p>
      <p class="mt-1 break-all text-body-2 text-gray-800">{{ displayValue }}</p>
      <p
        v-if="translatedHelp"
        class="mt-1 text-caption text-gray-500"
      >
        {{ translatedHelp }}
      </p>
    </div>

    <BaseButton
      v-else-if="field.type === 'external-link'"
      icon="link-external"
      :label="translatedLabel"
      type="primary"
      :to-url="String(field.value || modelValue || '')"
    />
  </div>
</template>

<script setup>
import { computed } from "vue"
import { useI18n } from "vue-i18n"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseCheckbox from "../../components/basecomponents/BaseCheckbox.vue"
import BaseInputNumber from "../../components/basecomponents/BaseInputNumber.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseRadioButtons from "../../components/basecomponents/BaseRadioButtons.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseTextArea from "../../components/basecomponents/BaseTextArea.vue"
import BaseTinyEditor from "../../components/basecomponents/BaseTinyEditor.vue"

const { t } = useI18n()

const modelValue = defineModel({
  type: [String, Number, Boolean, Array, Object],
  default: null,
})

const props = defineProps({
  field: {
    type: Object,
    required: true,
  },
})

const translatedLabel = computed(() => t(props.field.label || props.field.key))
const translatedHelp = computed(() => (props.field.help ? t(props.field.help) : ""))
const translatedOptions = computed(() =>
  Array.isArray(props.field.options)
    ? props.field.options.map((option) => ({
        ...option,
        label: t(option.label),
      }))
    : [],
)

const radioValue = computed({
  get: () => {
    const matchingOption = translatedOptions.value.find((option) => String(option.value) === String(modelValue.value))

    return matchingOption ? matchingOption.value : modelValue.value
  },
  set: (value) => {
    modelValue.value = value
  },
})

const stringValue = computed({
  get: () => {
    const value = modelValue.value

    if (props.field.type === "date" && typeof value === "string" && value.length >= 10) {
      return value.slice(0, 10)
    }

    return value === null || value === undefined ? "" : String(value)
  },
  set: (value) => {
    modelValue.value = value
  },
})

const numberValue = computed({
  get: () => Number(modelValue.value || 0),
  set: (value) => {
    modelValue.value = Number(value || 0)
  },
})

const booleanValue = computed({
  get: () => [true, 1, "1", "true", "yes", "on"].includes(modelValue.value),
  set: (value) => {
    modelValue.value = typeof modelValue.value === "boolean" ? Boolean(value) : value ? 1 : 0
  },
})

const arrayValue = computed({
  get: () => (Array.isArray(modelValue.value) ? modelValue.value : []),
  set: (value) => {
    modelValue.value = Array.isArray(value) ? value : []
  },
})

const displayValue = computed(() => {
  const value = props.field.value ?? modelValue.value

  return value === null || value === undefined || value === "" ? t("Not available") : String(value)
})
</script>
