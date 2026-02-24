<template>
  <div
    v-if="!isInIframe"
    class="login-section"
  >
    <h2 class="login-section__title">{{ t("Sign in") }}</h2>

    <form
      v-if="[null, 'ldap'].includes(platformConfigStore.forcedLoginMethod)"
      class="login-section__form"
      @submit.prevent="onSubmitLoginForm"
    >
      <BaseCheckbox
        v-if="platformConfigStore.ldapAuth?.enabled && 'ldap' !== platformConfigStore.forcedLoginMethod"
        id="chb-ldap"
        :label="platformConfigStore.ldapAuth.title"
        name="ldap_auth"
        v-model="ldapAuth"
      />

      <div class="field">
        <InputText
          id="login"
          v-model="login"
          :placeholder="t('Username')"
          type="text"
          variant="filled"
        />
      </div>

      <div class="field">
        <Password
          v-model="password"
          :feedback="false"
          :placeholder="t('Password')"
          input-id="password"
          toggle-mask
          variant="filled"
        />
      </div>

      <div
        v-if="requires2FA"
        class="field"
      >
        <InputText
          v-model="totp"
          :placeholder="t('Enter 2FA code')"
          type="text"
          variant="filled"
        />
      </div>

      <div
        v-if="isHttps"
        class="field login-section__remember-me"
      >
        <ToggleSwitch
          v-model="remember"
          input-id="remember_me"
          name="_remember_me"
          tabindex="4"
        />
        <label
          v-text="t('Remember me')"
          for="remember_me"
        />
      </div>

      <div class="field login-section__buttons">
        <Button
          :label="requires2FA ? t('Submit code') : t('Sign in')"
          :loading="isLoading"
          type="submit"
        />

        <a
          v-if="allowRegistration"
          v-text="t('Sign up')"
          class="btn btn--primary-outline"
          href="/main/auth/registration.php"
          tabindex="3"
        />
      </div>

      <div class="field text-center">
        <a
          id="forgot"
          v-text="t('Forgot your password?')"
          class="field"
          href="/main/auth/lostPassword.php"
          tabindex="5"
        />
      </div>
    </form>

    <LoginOAuth2Buttons />
    <div class="mt-3">
      <CategoryLinks category="menu_links" />
    </div>
  </div>
</template>

<script setup>
const isInIframe = window.self !== window.top
const isHttps = window.location.protocol === "https:"
if (isInIframe) {
  try {
    const parentUrl = window.top.location.href
    const parent = new URL(parentUrl)
    // Only keep path + query + hash so redirect stays internal
    const redirectPath = parent.pathname + parent.search + parent.hash
    window.top.location.href = "/login?redirect=" + encodeURIComponent(redirectPath)
  } catch (e) {
    // Cross-origin or other error: just go to login without redirect
    window.top.location.href = "/login"
  }
}

import { computed, ref } from "vue"
import Button from "primevue/button"
import InputText from "primevue/inputtext"
import Password from "primevue/password"
import ToggleSwitch from "primevue/toggleswitch"
import BaseCheckbox from "./basecomponents/BaseCheckbox.vue"
import { useI18n } from "vue-i18n"
import { useLogin } from "../composables/auth/login"
import LoginOAuth2Buttons from "./login/LoginOAuth2Buttons.vue"
import { usePlatformConfig } from "../store/platformConfig"
import { useRouter } from "vue-router"
import CategoryLinks from "./page/CategoryLinks.vue"

const { t } = useI18n()
const router = useRouter()
const platformConfigStore = usePlatformConfig()
const allowRegistration = computed(() => "false" !== platformConfigStore.getSetting("registration.allow_registration"))

const { redirectNotAuthenticated, performLogin, isLoading, requires2FA } = useLogin()

const ldapAuth = ref(false)
const login = ref("")
const password = ref("")
const totp = ref("")
const remember = ref(false)

redirectNotAuthenticated()

async function onSubmitLoginForm() {
  await performLogin({
    login: login.value,
    password: password.value,
    totp: requires2FA.value ? totp.value : null,
    _remember_me: isHttps ? remember.value : false,
    isLoginLdap: ldapAuth.value,
  })
}
</script>
