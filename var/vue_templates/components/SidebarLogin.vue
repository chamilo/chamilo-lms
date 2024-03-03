<script setup>
import { computed, ref } from "vue"
import { useStore } from "vuex"
import { useI18n } from "vue-i18n"
import InputText from "primevue/inputtext"
import Password from "primevue/password"
import Button from "primevue/button"
import InputSwitch from "primevue/inputswitch"
import { useRoute, useRouter } from "vue-router"
import { useSecurityStore } from "../../../assets/vue/store/securityStore"

const route = useRoute()
const router = useRouter()
const store = useStore()
const { t } = useI18n()
const securityStore = useSecurityStore()

const login = ref("")
const password = ref("")
const remember = ref(false)

const isLoading = computed(() => store.getters["security/isLoading"])

async function performLogin() {
  let payload = {
    login: login.value,
    password: password.value,
  }
  let redirect = route.query.redirect

  await store.dispatch("security/login", payload)

  if (!store.getters["security/hasError"]) {
    securityStore.user = store.state["security/user"]
    const responseData = await store.dispatch("security/login", payload)

    if (typeof redirect !== "undefined") {
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
</script>

<template>
  <div
    class="sidebar__login-form py-3 px-6"
    @click="$event.stopPropagation()"
    @keydown="$event.stopPropagation()"
  >
    <form
      class="login-section__form p-input-filled"
      @submit.prevent="performLogin"
    >
      <div class="mb-2">
        <InputText
          id="login"
          v-model="login"
          :placeholder="t('Username')"
          type="text"
        />
      </div>

      <div class="mb-3">
        <Password
          v-model="password"
          :feedback="false"
          :placeholder="t('Password')"
          input-id="password"
          toggle-mask
        />
      </div>

      <div class="mb-3 flex flex-row gap-2">
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

      <div class="mb-2 flex flex-col gap-2">
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

      <a
        id="forgot"
        v-t="'Forgot your password ?'"
        class="field"
        href="/main/auth/lostPassword.php"
        tabindex="5"
      />
    </form>
  </div>
</template>

<style scoped lang="scss">
.p-panelmenu-content .sidebar__login-form {
  @apply border-t border-b border-gray-25;
  background-color: #f1f1f1;
}
</style>
