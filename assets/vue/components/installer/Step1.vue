<template>
  <div class="install-step">
    <img
      :alt="t('Install step 1')"
      class="install-icon w-36 mx-auto mb-4"
      src="/main/install/chamilo-install.svg"
    >
    <h2
      class="install-title mb-8"
      v-text="t('Step 1 - Installation Language')"
    />

    <div class="field">
      <div class="p-float-label">
        <Dropdown
          v-model="installerData.langIso"
          :options="languages"
          input-id="language_list"
          option-label="english_name"
          option-value="isocode"
        />
        <label
          v-t="'Please select installation language'"
          for="language_list"
        />
      </div>
      <small v-text="t('Cannot find your language in the list? Contact us at info@chamilo.org to contribute as a translator.')" />
    </div>

    <input
      v-model="installerData.langIso"
      name="language_list"
      type="hidden"
    >

    <input
      v-model="installerData.stepData.installationProfile"
      type="hidden"
      name="installationProfile"
    >

    <hr>

    <div class="formgroup-inline">
      <Button
        :label="t('Next')"
        class="p-button-secondary"
        icon="mdi mdi-page-next"
        name="step1"
        type="submit"
      />
      <input
        id="is_executable"
        name="is_executable"
        type="hidden"
        value="step1"
      >
    </div>
  </div>
</template>

<script setup>
import { inject } from 'vue';
import { useI18n } from 'vue-i18n';

import Dropdown from 'primevue/dropdown';
import Button from 'primevue/button';

import languages from '../../utils/languages';

const { t } = useI18n();

const installerData = inject('installerData');
</script>
