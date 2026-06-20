<template>
  <div class="flex flex-col gap-1">
    <BaseSelect
      id="gradebook-calculation-mode"
      v-model="selectedMode"
      name="calculationMode"
      :label="t('Calculation mode')"
      :options="modeOptions"
      :disabled="disabled"
      @change="onChange"
    />
    <p class="text-sm text-gray-50 flex items-start gap-1">
      <BaseIcon icon="information" />
      <span>{{ helpText }}</span>
    </p>
  </div>
</template>

<script setup>
import { computed, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import BaseSelect from "../basecomponents/BaseSelect.vue"
import BaseIcon from "../basecomponents/BaseIcon.vue"

const props = defineProps({
  modelValue: {
    type: String,
    required: false,
    default: "weighted_average",
  },
  disabled: {
    type: Boolean,
    required: false,
    default: false,
  },
})

const emit = defineEmits(["update:modelValue", "change"])

const { t } = useI18n()

const selectedMode = ref(props.modelValue)

watch(
  () => props.modelValue,
  (value) => {
    selectedMode.value = value
  },
)

const modeOptions = computed(() => [
  { label: t("Weighted average"), value: "weighted_average" },
  { label: t("Points sum"), value: "points_sum" },
])

const helpText = computed(() =>
  "points_sum" === selectedMode.value
    ? t("Each item weight is treated as its maximum points; the grade is the sum of points, not normalized.")
    : t("The grade is the weighted average of items, normalized by the sum of weights."),
)

/**
 * Propagates the selected calculation mode to the parent component.
 */
function onChange() {
  emit("update:modelValue", selectedMode.value)
  emit("change", selectedMode.value)
}
</script>
