<template>
  <div class="login-section">
    <h2
      v-t="'Sign in'"
      class="login-section__title"
    />

    <form
      @submit.prevent="performLogin"
      class="login-section__form"
    >
      <div class="form__field p-input-filled">
        <InputText
          v-model="login"
          :placeholder="t('Username')"
          name="login"
          type="text"
        />
      </div>

      <div class="form__field p-input-filled">
        <Password
          v-model="password"
          :feedback="false"
          :placeholder="t('Password')"
          name="password"
          toggle-mask
        />
      </div>

      <div class="form__field login-section__buttons">
        <Button
          :label="t('Sign in')"
          :loading="isLoading"
          class="btn btn--primary"
          type="submit"
        />

        <a
          v-t="'Register oneself'"
          class="btn btn--primary-outline"
          href="/main/auth/inscription.php"
          tabindex="3"
        />
      </div>

      <div class="form__field text-center">
        <InputSwitch
          id="binary"
          v-model="remember"
          :binary="true"
          name="remember_me"
          tabindex="4"
        />
        <label
          v-t="'Remember me'"
          for="binary"
        />
      </div>

      <div class="form__field text-center">
        <a
          id="forgot"
          v-t="'Forgot your password?'"
          class="form__field"
          href="/main/auth/lostPassword.php"
          tabindex="5"
        />
      </div>
    </form>
  </div>
</template>

<script setup>
import {useStore} from 'vuex';
import {computed, ref} from "vue";
import {useRoute, useRouter} from "vue-router";
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import InputSwitch from 'primevue/inputswitch';
import {useI18n} from "vue-i18n";

const route = useRoute();
const router = useRouter();
const store = useStore();
const {t} = useI18n();

const login = ref('');
const password = ref('');
const remember = ref(false);

const isLoading = computed(() => store.getters['security/isLoading']);

let redirect = route.query.redirect;

if (store.getters["security/isAuthenticated"]) {
  if (typeof redirect !== "undefined") {
    router.push({path: redirect.toString()});
  } else {
    router.replace({name: 'Home'});
  }
}

async function performLogin() {
  let payload = {login: login.value, password: password.value};
  let redirect = route.query.redirect;

  await store.dispatch("security/login", payload);

  if (!store.getters["security/hasError"]) {
    if (typeof redirect !== "undefined") {
      await router.push({path: redirect.toString()});
    } else {
      // router.replace({path: "/home"});
      window.location.href = '/home';
    }
  }
}
</script>
