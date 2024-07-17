<template>
  <div class="install-step">
    <h2
      v-if="'update' !== installerData.installType"
      v-t="'Step 7 - Installation process execution'"
      class="RequirementHeading mb-8"
    />
    <h2
      v-else
      v-t="'Step 7 - Update process execution'"
      class="RequirementHeading mb-8"
    />

    <p
      v-if="installerData.installationProfile"
      class="mb-4"
      v-text="installerData.installationProfile"
    />

    <div class="RequirementContent">
      <p
        v-t="
          'When you enter your portal for the first time, the best way to understand it is to create a course with the \'Create course\' link in the menu and play around a little.'
        "
        class="mb-3"
      />

      <Message
        :closable="false"
        severity="warn"
      >
        <strong v-t="'Security advice'" />

        <i18n-t
          keypath="To protect your site, make the whole %s directory read-only (chmod -R 0555 on Linux) and delete the %s directory."
          tag="p"
        >
          <code>config/</code>
          <code>main/install/</code>
        </i18n-t>
      </Message>

      <div class="formgroup-inline">
        <div class="field">
          <Button
            :label="t('Go to your newly created portal.')"
            class="p-button-success"
            type="button"
            @click="btnFinishOnClick"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { inject } from "vue"
import { useI18n } from "vue-i18n"

import Message from "primevue/message"
import Button from "primevue/button"

const { t } = useI18n()

const installerData = inject("installerData")

function btnFinishOnClick() {
  window.location = "../../"
}
</script>
