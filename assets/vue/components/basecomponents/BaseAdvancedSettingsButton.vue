<template>
  <div class="field">
    <BaseButton
      :label="showAdvancedSettingsLabel"
      class="mr-auto"
      icon="cog"
      type="black"
      @click="advancedSettingsClicked"
    />
  </div>

  <div v-if="showAdvancedSettings">
    <slot></slot>
  </div>
</template>

<script setup>
import BaseButton from "./BaseButton.vue";
import { computed, ref } from "vue";
import { useI18n } from "vue-i18n";

const props = defineProps({
  modelValue: {
    type: Boolean,
    required: false,
    default: () => false,
  }
})
const emit = defineEmits(['update:modelValue']);

const { t } = useI18n();

const showAdvancedSettings = ref(props.modelValue);

const showAdvancedSettingsLabel = computed(() => {
  if (showAdvancedSettings.value) {
    return t("Hide advanced settings");
  }

  return t("Show advanced settings");
});

const advancedSettingsClicked = () => {
  showAdvancedSettings.value = !showAdvancedSettings.value;

  emit('update:modelValue', showAdvancedSettings.value);
};
</script>
