<template>
  <div class="field">
    <FloatLabel variant="on">
      <Dropdown
        v-model="modelValue"
        :disabled="disabled"
        :input-id="id"
        :invalid="isInvalid"
        :loading="isLoading"
        :name="name"
        :option-label="optionLabel"
        :option-value="optionValue"
        :options="realOptions"
        :placeholder="placeholder"
        :show-clear="allowClear"
        @change="emit('change', $event)"
      >
        <template #emptyfilter>--</template>
        <template #empty>
          {{ t("No available options") }}
        </template>
      </Dropdown>
      <label
        :for="id"
        v-text="label"
      />
    </FloatLabel>
    <Message
      v-if="isInvalid || messageText"
      size="small"
      :severity="isInvalid ? 'error' : 'contrast'"
      variant="simple"
    >
      {{ messageText }}
    </Message>
  </div>
</template>

<script setup>
import { useI18n } from "vue-i18n"
import { computed } from "vue"
import FloatLabel from "primevue/floatlabel"
import Dropdown from "primevue/select"
import Message from "primevue/message"

const { t } = useI18n()

const modelValue = defineModel({
  type: [String, Number, Object],
})

const props = defineProps({
  id: {
    type: String,
    require: true,
    default: "",
  },
  name: {
    type: String,
    required: false,
    default: undefined,
  },
  label: {
    type: String,
    required: true,
    default: "",
  },
  options: {
    type: Array,
    required: true,
  },
  optionLabel: {
    type: String,
    required: false,
    default: "label",
  },
  optionValue: {
    type: String,
    required: false,
    default: "value",
  },
  isInvalid: {
    type: Boolean,
    required: false,
    default: false,
  },
  hastEmptyValue: {
    type: Boolean,
    default: false,
  },
  isLoading: {
    type: Boolean,
    default: false,
  },
  allowClear: {
    type: Boolean,
    default: false,
  },
  disabled: {
    default: false,
    required: false,
    type: Boolean,
  },
  placeholder: {
    type: String,
    required: false,
    default: "",
  },
  messageText: {
    type: [String, null],
    required: false,
    default: null,
  },
})

const emit = defineEmits(["change"])

const realOptions = computed(() => {
  if (props.hastEmptyValue) {
    const emptyValue = {
      [props.optionLabel]: "--",
      [props.optionValue]: "",
    }
    return [emptyValue, ...props.options]
  }
  return props.options
})
</script>
