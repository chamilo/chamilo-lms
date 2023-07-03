<template>
  <div class="field">
    <div class="p-float-label">
      <Dropdown
        :id="id"
        :model-value="modelValue"
        :options="realOptions"
        :option-label="optionLabel"
        :option-value="optionValue"
        placeholder="--"
        @update:model-value="emit('update:modelValue', $event)"
      >
        <template #emptyfilter>--</template>
        <template #empty>
          <p class="pt-2 px-2">{{ t('No available options') }}</p>
        </template>
      </Dropdown>
      <label v-t="label" :class="{ 'p-error': isInvalid }" :for="id" />
    </div>
  </div>
</template>

<script setup>
import {useI18n} from "vue-i18n";
import {computed} from "vue";

const {t} = useI18n()

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
    type: [String, null],
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
    default: false
  }
})

const emit = defineEmits(["update:modelValue"])

const realOptions = computed(() => {
  if (props.hastEmptyValue) {
    const emptyValue = {
      [props.optionLabel]: "--",
      [props.optionValue]: "",
    }
    return [
      emptyValue,
      ...props.options,
    ]
  }
  return props.options
})

</script>
