<template>
  <div
    v-if="!isInIframe"
    class="login-section"
  >
    <h2 class="login-section__title">{{ t("Sign in") }}</h2>

    <form
      class="login-section__form p-input-filled"
      @submit.prevent="onSubmitLoginForm"
    >
      <div class="field">
        <InputText
          id="login"
          v-model="login"
          :placeholder="t('Username')"
          type="text"
        />
      </div>

      <div class="field">
        <Password
          v-model="password"
          :feedback="false"
          :placeholder="t('Password')"
          input-id="password"
          toggle-mask
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
        />
      </div>

      <div class="field login-section__remember-me">
        <InputSwitch
          v-model="remember"
          input-id="binary"
          name="remember_me"
          tabindex="4"
        />
        <label
          v-t="'Remember me'"
          for="binary"
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
          v-t="'Sign up'"
          class="btn btn--primary-outline"
          href="/main/auth/inscription.php"
          tabindex="3"
        />
      </div>

      <div class="field text-center">
        <a
          id="forgot"
          v-t="'Forgot your password?'"
          class="field"
          href="/main/auth/lostPassword.php"
          tabindex="5"
        />
      </div>
    </form>

    <LoginOAuth2Buttons />
  </div>
</template>

<script setup>
const isInIframe = window.self !== window.top
if (isInIframe) {
  try {
    const parentUrl = window.top.location.href
    window.top.location.href = "/login?redirect=" + encodeURIComponent(parentUrl)
  } catch (e) {
    window.top.location.href = "/login"
  }
}

import { computed, ref } from "vue"
import { useRouter } from "vue-router"
import Button from "primevue/button"
import InputText from "primevue/inputtext"
import Password from "primevue/password"
import InputSwitch from "primevue/inputswitch"
import { useI18n } from "vue-i18n"
import { useLogin } from "../composables/auth/login"
import LoginOAuth2Buttons from "./login/LoginOAuth2Buttons.vue"
import { usePlatformConfig } from "../store/platformConfig"

const { t } = useI18n()

const router = useRouter()

const platformConfigStore = usePlatformConfig()
const allowRegistration = computed(() => "false" !== platformConfigStore.getSetting("registration.allow_registration"))

const { redirectNotAuthenticated, performLogin, isLoading } = useLogin()

const login = ref("")
const password = ref("")
const totp = ref("")
const remember = ref(false)
const requires2FA = ref(false)

redirectNotAuthenticated()

async function onSubmitLoginForm() {
  try {
    const response = await performLogin({
      login: login.value,
      password: password.value,
      totp: requires2FA.value ? totp.value : null,
      _remember_me: remember.value,
    })

    if (!response) {
      console.warn("[Login] No response from performLogin.")
      return
    }

    if (response.requires2FA) {
      requires2FA.value = true
    } else {
      await router.replace({ name: "Home" })
    }
  } catch (error) {
    console.error("[Login] performLogin failed:", error)
  }
}
</script>
