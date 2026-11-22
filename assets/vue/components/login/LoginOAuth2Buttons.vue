<script setup>
import BaseDivider from "../basecomponents/BaseDivider.vue"
import { useI18n } from "vue-i18n"
import { usePlatformConfig } from "../../store/platformConfig"

const { t } = useI18n()

const platformConfig = usePlatformConfig()
</script>

<template>
  <div
    v-if="platformConfig.oauth2Providers.length > 0 || platformConfig.casAuth"
    class="external-logins"
  >
    <BaseDivider
      v-if="!platformConfig.forcedLoginMethod"
      :title="t('Or')"
      align="center"
      class="external-logins__divider"
    />

    <ul class="external-logins__button-list">
      <li
        v-for="(extAuth, idx) in platformConfig.oauth2Providers"
        :key="idx"
      >
        <BaseAppLink
          :url="extAuth.url"
          class="external-logins__button"
        >
          {{ t("Continue with {0}", [extAuth.title]) }}
        </BaseAppLink>
      </li>
      <li v-if="platformConfig.casAuth">
        <a
          :href="platformConfig.casAuth.url"
          class="external-logins__button"
        >
          {{ t("Continue with {0}", [platformConfig.casAuth.title]) }}
        </a>
      </li>
    </ul>
  </div>
</template>
