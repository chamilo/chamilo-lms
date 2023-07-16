<template>
  <div class="field">
    <BaseButton
      :label="showAdvancedSettingsLabel"
      class="mr-auto"
      icon="cog"
      type="black"
      @click="$emit('update:modelValue', !modelValue)"
    />
  </div>

  <div v-if="modelValue">
    <slot></slot>
  </div>
</template>

<script setup>
import BaseButton from "./BaseButton.vue";
import { computed } from "vue"
import { useI18n } from "vue-i18n";

const props = defineProps({
  modelValue: {
    type: Boolean,
    required: true,
  }
})

defineEmits(['update:modelValue'])

const { t } = useI18n();

const showAdvancedSettingsLabel = computed(() => {
  if (props.modelValue) {
    return t("Hide advanced settings");
  }

  return t("Show advanced settings");
});
</script>
