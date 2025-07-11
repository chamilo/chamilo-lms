<template>
  <div class="login-section">
    <h2
      v-t="'Sign in'"
      class="login-section__title"
    />

    <form
      class="login-section__form"
      @submit.prevent="onSubmitLoginForm"
    >
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

      <div class="field login-section__remember-me">
        <ToggleSwitch
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
import { computed, ref } from "vue"
import Button from "primevue/button"
import InputText from "primevue/inputtext"
import Password from "primevue/password"
import ToggleSwitch from "primevue/toggleswitch"
import { useI18n } from "vue-i18n"
import { useLogin } from "../composables/auth/login"
import LoginOAuth2Buttons from "./login/LoginOAuth2Buttons.vue"
import { usePlatformConfig } from "../store/platformConfig"
import { useRouter } from "vue-router"

const { t } = useI18n()
const router = useRouter()
const platformConfigStore = usePlatformConfig()
const allowRegistration = computed(() => "false" !== platformConfigStore.getSetting("registration.allow_registration"))

const { redirectNotAuthenticated, performLogin, isLoading, requires2FA } = useLogin()

const login = ref("")
const password = ref("")
const totp = ref("")
const remember = ref(false)

redirectNotAuthenticated()

async function onSubmitLoginForm() {
  const response = await performLogin({
    login: login.value,
    password: password.value,
    totp: requires2FA.value ? totp.value : null,
    _remember_me: remember.value,
  })
}
</script>
