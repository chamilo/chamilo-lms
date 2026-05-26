<template>
  <div class="install-step">
    <SectionHeader
      :title="
        'update' !== installerData.installType
          ? t('Step 7 - Installation process execution')
          : t('Step 7 - Update process execution')
      "
      class="RequirementHeading"
    />

    <p
      v-if="installerData.installationProfile"
      class="mb-4"
      v-text="installerData.installationProfile"
    />

    <div class="RequirementContent">
      <p
        v-text="
          t(
            'When you enter your portal for the first time, the best way to understand it is to create a course with the \'Create course\' link in the menu and play around a little.',
          )
        "
        class="mb-3"
      />

      <Message
        :closable="false"
        severity="warn"
        style="margin-bottom: 8px;"
      >
        <strong v-text="t('Security advice')" />

        <i18n-t
          keypath="To protect your site, make the whole {0} directory read-only (chmod -R 0555 on Linux) and delete the {1} directory."
          tag="p"
        >
          <code>config/</code>
          <code>public/main/install/</code>
        </i18n-t>
      </Message>

      <div class="formgroup-inline">
        <div class="field">
          <BaseAppLink url="../../">
            <Button
              :label="t('Go to your newly created portal.')"
              class="p-button-success"
              type="button"
            />
          </BaseAppLink>
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
import SectionHeader from "../layout/SectionHeader.vue"
import BaseAppLink from "../basecomponents/BaseAppLink.vue"

const { t } = useI18n()

const installerData = inject("installerData")
</script>
