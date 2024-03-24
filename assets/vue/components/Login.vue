<template>
  <div class="login-section">
    <h2
      v-t="'Sign in'"
      class="login-section__title"
    />

    <form
      class="login-section__form p-input-filled"
      @submit.prevent="performLogin"
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
  </div>
</template>

<script setup>
import { useStore } from "vuex"
import { computed, ref } from "vue"
import { useRoute, useRouter } from "vue-router"
import Button from "primevue/button"
import InputText from "primevue/inputtext"
import Password from "primevue/password"
import InputSwitch from "primevue/inputswitch"
import { useI18n } from "vue-i18n"
import { useSecurityStore } from "../store/securityStore"
import { usePlatformConfig } from "../store/platformConfig"

const route = useRoute()
const router = useRouter()
const store = useStore()
const { t } = useI18n()
const securityStore = useSecurityStore()

const login = ref("")
const password = ref("")
const remember = ref(false)

const isLoading = computed(() => store.getters["security/isLoading"])

let redirect = route.query.redirect

if (securityStore.isAuthenticated) {
  if (typeof redirect !== "undefined") {
    router.push({ path: redirect.toString() })
  } else {
    router.replace({ name: "Home" })
  }
}

async function performLogin() {
  let payload = {
    login: login.value,
    password: password.value,
    _remember_me: remember.value,
  }
  let redirect = route.query.redirect

  const responseData = await store.dispatch("security/login", payload)

  if (!store.getters["security/hasError"]) {
    // Check if 'redirect' is an absolute URL
    if (typeof redirect !== "undefined" && isValidHttpUrl(redirect.toString())) {
      // If it's an absolute URL, redirect directly
      window.location.href = redirect.toString()
    } else if (typeof redirect !== "undefined") {
      securityStore.user = responseData

      const platformConfigurationStore = usePlatformConfig()
      await platformConfigurationStore.initialize()

      // If 'redirect' is a relative path, use 'router.push' to navigate
      await router.push({ path: redirect.toString() })
    } else {
      if (responseData.load_terms) {
        window.location.href = responseData.redirect
      } else {
        window.location.href = "/home"
      }
    }
  }
}

function isValidHttpUrl(string) {
  let url

  try {
    url = new URL(string)
  } catch (_) {
    return false
  }

  return url.protocol === "http:" || url.protocol === "https:"
}
</script>
