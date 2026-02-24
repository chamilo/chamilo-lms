<script setup>
import { computed, ref } from "vue"
import { useI18n } from "vue-i18n"
import InputText from "primevue/inputtext"
import Password from "primevue/password"
import Button from "primevue/button"
import ToggleSwitch from "primevue/toggleswitch"
import { useLogin } from "../../../assets/vue/composables/auth/login"
import { usePlatformConfig } from "../../../assets/vue/store/platformConfig"
import BaseCheckbox from "../../../assets/vue/components/basecomponents/BaseCheckbox.vue"

const { t } = useI18n()

const { performLogin, isLoading } = useLogin()

const ldapAuth = ref(false)
const login = ref("")
const password = ref("")
const remember = ref(false)

const platformConfigStore = usePlatformConfig()
const allowRegistration = computed(() => "false" !== platformConfigStore.getSetting("registration.allow_registration"))

function onSubmitLoginForm() {
  performLogin({
    login: login.value,
    password: password.value,
    _remember_me: remember.value,
    isLoginLdap: ldapAuth.value,
  })
}
</script>

<template>
  <div
    class="sidebar__login-form py-3 px-6"
    @click="$event.stopPropagation()"
    @keydown="$event.stopPropagation()"
  >
    <form
      v-if="[null, 'ldap'].includes(platformConfigStore.forcedLoginMethod)"
      class="login-section__form p-input-filled"
      @submit.prevent="onSubmitLoginForm"
    >
      <BaseCheckbox
        v-if="platformConfigStore.ldapAuth?.enabled && 'ldap' !== platformConfigStore.forcedLoginMethod"
        id="chb-ldap"
        :label="platformConfigStore.ldapAuth.title"
        name="ldap_auth"
        v-model="ldapAuth"
      />

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
        <ToggleSwitch
          v-model="remember"
          input-id="binary"
          name="remember_me"
          tabindex="4"
        />
        <label
          v-text="t('Remember me')"
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
          v-if="allowRegistration"
          v-text="t('Sign up')"
          class="btn btn--primary-outline"
          href="/main/auth/registration.php"
          tabindex="3"
        />
      </div>

      <a
        id="forgot"
        v-text="t('Forgot your password?')"
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
