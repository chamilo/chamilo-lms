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
        v-model="ldapAuth"
        :label="platformConfigStore.ldapAuth.title"
        name="ldap_auth"
      />

      <div class="field">
        <InputText
          id="login"
          v-model="login"
          :placeholder="t('Username')"
          type="text"
          variant="filled"
          @blur="updateCaptchaStatus(login)"
          @focus="setFocusedField('login')"
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
          @focus="setFocusedField('password')"
        />
      </div>
      <div
        v-if="useVirtualKeyboard && focusedField && !requires2FA"
        class="field"
      >
        <VirtualKeyboard @key-press="handleVirtualKeyboardKey" />
      </div>
      <div
        v-if="captcha.required && !requires2FA"
        class="field"
      >
        <div class="mb-3">
          <img
            v-if="captcha.imageUrl"
            :src="captcha.imageUrl"
            alt="Login captcha"
            class="block w-full max-w-[220px] rounded border border-gray-200 bg-white"
          />
        </div>
        <InputText
          v-model="captcha.code"
          :placeholder="t('Enter captcha code')"
          type="text"
          variant="filled"
        />
        <button
          class="mt-2 text-sm text-primary hover:underline"
          type="button"
          @click="refreshCaptcha"
        >
          {{ t("Refresh captcha") }}
        </button>
        <p
          v-if="captcha.blocked && captcha.blockedSeconds > 0"
          class="mt-2 text-sm text-danger"
        >
          {{ t("Captcha is temporarily blocked. Please try again later.") }}
        </p>
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
          id="remember_me"
          v-model="remember"
          input-id="remember_me"
          name="_remember_me"
          tabindex="4"
        />
        <label
          for="remember_me"
          v-text="t('Remember me')"
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
          class="btn btn--primary-outline"
          href="/registration"
          tabindex="3"
          v-text="t('Sign up')"
        />
      </div>

      <div class="field text-center">
        <a
          id="forgot"
          class="field"
          href="/lost-password"
          tabindex="5"
          v-text="t('Forgot your password?')"
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
import { computed, onMounted, ref } from "vue"
import { useI18n } from "vue-i18n"
import Button from "primevue/button"
import InputText from "primevue/inputtext"
import Password from "primevue/password"
import ToggleSwitch from "primevue/toggleswitch"
import BaseCheckbox from "./basecomponents/BaseCheckbox.vue"
import LoginOAuth2Buttons from "./login/LoginOAuth2Buttons.vue"
import CategoryLinks from "./page/CategoryLinks.vue"
import { useLogin } from "../composables/auth/login"
import { usePlatformConfig } from "../store/platformConfig"
import VirtualKeyboard from "./login/VirtualKeyboard.vue"

const useVirtualKeyboard = computed(() => {
  return "true" === platformConfigStore.getSetting("platform.use_virtual_keyboard")
})
const isInIframe = window.self !== window.top
const isHttps = window.location.protocol === "https:"

if (isInIframe) {
  try {
    const parentUrl = window.top.location.href
    const parent = new URL(parentUrl)
    const redirectPath = parent.pathname + parent.search + parent.hash
    window.top.location.href = "/login?redirect=" + encodeURIComponent(redirectPath)
  } catch {
    window.top.location.href = "/login"
  }
}

const { t } = useI18n()
const platformConfigStore = usePlatformConfig()

const allowRegistration = computed(() => {
  return "false" !== platformConfigStore.getSetting("registration.allow_registration")
})

const {
  redirectNotAuthenticated,
  submitLogin,
  isLoading,
  requires2FA,
  captcha,
  refreshCaptcha,
  loadCaptchaStatus,
  updateCaptchaStatus,
} = useLogin()

const ldapAuth = ref(false)
const login = ref("")
const password = ref("")
const focusedField = ref(null)
const totp = ref("")
const remember = ref(false)

function setFocusedField(field) {
  focusedField.value = field
}

function handleVirtualKeyboardKey(key) {
  if (!focusedField.value) {
    return
  }

  const target = "password" === focusedField.value ? password : login

  if ("backspace" === key) {
    target.value = target.value.slice(0, -1)

    return
  }

  if ("space" === key) {
    target.value += " "

    return
  }

  if ("clear" === key) {
    target.value = ""

    return
  }

  target.value += key
}

/**
 * Submits the login form, delegating the login and captcha flow to the composable.
 * @returns {Promise<void>}
 */
async function onSubmitLoginForm() {
  await submitLogin({
    login: login.value,
    password: password.value,
    totp: requires2FA.value ? totp.value : null,
    _remember_me: isHttps ? remember.value : false,
    isLoginLdap: ldapAuth.value,
  })
}

onMounted(async () => {
  await redirectNotAuthenticated()
  await loadCaptchaStatus(login.value)
})
</script>
