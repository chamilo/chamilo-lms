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

const { t } = useI18n();

const showAdvancedSettings = ref(false);

const showAdvancedSettingsLabel = computed(() => {
  if (showAdvancedSettings.value) {
    return t("Hide advanced settings");
  }

  return t("Show advanced settings");
});

const advancedSettingsClicked = () => {
  showAdvancedSettings.value = !showAdvancedSettings.value;
};
</script>
