<script setup>
import BaseAppLink from "../basecomponents/BaseAppLink.vue"
import BaseDivider from "../basecomponents/BaseDivider.vue"
import { useI18n } from "vue-i18n"
import { usePlatformConfig } from "../../store/platformConfig"

const { t } = useI18n()

const platformConfig = usePlatformConfig()
</script>

<template>
  <div
    v-if="platformConfig.externalAuthentication.length > 0"
    class="external-logins"
  >
    <BaseDivider
      :title="t('Or')"
      align="center"
      class="external-logins__divider"
    />

    <ul class="external-logins__button-list">
      <li
        v-for="(extAuth, idx) in platformConfig.externalAuthentication"
        :key="idx"
      >
        <BaseAppLink
          :url="extAuth.url"
          class="external-logins__button"
        >
          {{ t("Continue with %s", [extAuth.title]) }}
        </BaseAppLink>
      </li>
    </ul>
  </div>
</template>
