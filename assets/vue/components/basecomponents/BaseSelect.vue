<template>
  <div class="field">
    <FloatLabel>
      <Dropdown
        :id="id"
        :disabled="disabled"
        :model-value="modelValue"
        :options="realOptions"
        :option-label="optionLabel"
        :option-value="optionValue"
        :loading="isLoading"
        :show-clear="allowClear"
        @update:model-value="emit('update:modelValue', $event)"
        @change="emit('change', $event)"
      >
        <template #emptyfilter>--</template>
        <template #empty>
          <p class="pt-2 px-2">{{ t("No available options") }}</p>
        </template>
      </Dropdown>
      <label
        v-t="label"
        :for="id"
      />
    </FloatLabel>
  </div>
</template>

<script setup>
import { useI18n } from "vue-i18n"
import { computed } from "vue"
import FloatLabel from "primevue/floatlabel"

const { t } = useI18n()

const props = defineProps({
  id: {
    type: String,
    require: true,
    default: "",
  },
  label: {
    type: String,
    required: true,
    default: "",
  },
  modelValue: {
    type: [String, Number, null],
    required: true,
  },
  options: {
    type: Array,
    required: true,
  },
  optionLabel: {
    type: String,
    required: true,
  },
  optionValue: {
    type: String,
    required: true,
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
})

const emit = defineEmits(["update:modelValue", "change"])

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
