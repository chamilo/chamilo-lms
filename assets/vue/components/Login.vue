<template>
  <div class="login-section">
    <h2
      v-t="'Sign in'"
      class="login-section__title"
    />

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
          :label="t('Sign in')"
          :loading="isLoading"
          type="submit"
        />

        <a
          v-t="'Register oneself'"
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

    <ExternalLoginButtons />
  </div>
</template>

<script setup>
import { ref } from "vue"
import Button from "primevue/button"
import InputText from "primevue/inputtext"
import Password from "primevue/password"
import InputSwitch from "primevue/inputswitch"
import { useI18n } from "vue-i18n"
import { useLogin } from "../composables/auth/login"
import ExternalLoginButtons from "./login/LoginExternalButtons.vue"

const { t } = useI18n()

const { redirectNotAuthenticated, performLogin, isLoading } = useLogin()

const login = ref("")
const password = ref("")
const remember = ref(false)

redirectNotAuthenticated()

function onSubmitLoginForm() {
  performLogin({
    login: login.value,
    password: password.value,
    _remember_me: remember.value,
  })
}
</script>
