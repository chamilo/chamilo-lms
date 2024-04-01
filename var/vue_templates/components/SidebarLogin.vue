<script setup>
import { ref } from "vue"
import { useI18n } from "vue-i18n"
import InputText from "primevue/inputtext"
import Password from "primevue/password"
import Button from "primevue/button"
import InputSwitch from "primevue/inputswitch"
import { useLogin } from "../../../assets/vue/composables/auth/login"

const { t } = useI18n()

const { performLogin, isLoading } = useLogin()

const login = ref("")
const password = ref("")
const remember = ref(false)
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
