<template>
  <div class="install-step">
    <img
      :alt="t('Step 1 - Installation Language')"
      class="install-icon w-36 mx-auto mb-4"
      src="/main/install/chamilo-install.svg"
    />
    <SectionHeader :title="t('Step 1 - Installation Language')" />

    <BaseSelect
      v-model="installerData.langIso"
      :message-text="
        t('Cannot find your language in the list? Contact us at {0} to contribute as a translator.', [
          'info@chamilo.org',
        ])
      "
      :label="t('Please select installation language')"
      :options="availableLanguages"
      id="language_list"
      name="language_list_alt"
      option-label="original_name"
      option-value="isocode"
    />

    <input
      v-model="installerData.langIso"
      name="language_list"
      type="hidden"
    />

    <input
      v-model="installerData.stepData.installationProfile"
      name="installationProfile"
      type="hidden"
    />

    <hr />

    <div class="formgroup">
      <Message
        v-if="installerData.isUpdateAvailable"
        id="pleasewait"
        :closable="false"
        severity="warn"
      >
        <p
          class="update-message-text"
          v-t="'An update is available. Click the button below to proceed with the update.'"
        />
        <p>{{ installerData.checkMigrationStatus.message }}</p>
        <p v-if="installerData.checkMigrationStatus.current_migration">
          Current Migration: {{ installerData.checkMigrationStatus.current_migration }}
        </p>
        <p v-if="installerData.checkMigrationStatus.progress_percentage">
          Progress: {{ installerData.checkMigrationStatus.progress_percentage }}%
        </p>
        <hr />
      </Message>
      <BaseButton
        :label="t('Next')"
        :name="'step1'"
        :type="installerData.isUpdateAvailable ? 'secondary' : 'success'"
        icon="next"
        is-submit
      />
      <input
        id="is_executable"
        :value="!installerData.isUpdateAvailable ? 'step1' : '-'"
        name="is_executable"
        type="hidden"
      />
    </div>
  </div>
</template>

<script setup>
import { inject, computed } from "vue"
import { useI18n } from "vue-i18n"

import Message from "primevue/message"
import BaseSelect from "../basecomponents/BaseSelect.vue"
import BaseButton from "../basecomponents/BaseButton.vue"
import SectionHeader from "../layout/SectionHeader.vue"

import languages from "../../utils/languages"

const { t } = useI18n()
const installerData = inject("installerData")

const ALLOWED = ["ar", "de", "en_US", "es", "fr_FR", "he_IL", "it", "nl", "pt_BR", "sl_SI"]

const availableLanguages = computed(() => {
  const allow = new Set(ALLOWED.map((x) => x.toLowerCase()))
  const list = languages
    .filter((l) => allow.has(l.isocode.toLowerCase()))
    .map((l) => ({
      isocode: l.isocode,
      original_name: l.original_name || l.english_name || l.isocode,
    }))
  const iso = installerData?.value?.langIso
  if (iso && !list.some((l) => l.isocode.toLowerCase() === String(iso).toLowerCase())) {
    const found = languages.find((l) => l.isocode.toLowerCase() === String(iso).toLowerCase())
    list.unshift({
      isocode: iso,
      original_name: found?.original_name || found?.english_name || iso,
    })
  }

  return list
})
</script>
