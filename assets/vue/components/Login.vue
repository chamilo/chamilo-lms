<template>
  <div>
    <h2 class="text-3xl font-extrabold text-gray-900">
      {{ t('Sign in') }}
    </h2>

    <form
      class="mt-8 space-y-4"
      @submit.prevent="performLogin"
    >
      <InputText
        v-model="login"
        :placeholder="t('Username')"
        type="text"
      />

      <Password
        v-model="password"
        :feedback="false"
        :placeholder="t('Password')"
        toggle-mask
      />

      <div class="flex gap-4">
        <Button
          :label="t('Sign in')"
          :loading="isLoading"
          class="w-6/12 btn btn-primary text-center py-2 px-4 border border-transparent text-sm font-medium text-white"
          type="submit"
        />

        <a
          class="w-6/12 btn btn-default text-center py-2 px-4 border border-transparent text-sm font-medium text-gray-600"
          href="/main/auth/inscription.php"
          tabindex="3"
        >
          {{ t('Register oneself') }}
        </a>
      </div>

      <div class="text-center text-sm">
        <div class="field-checkbox">
          <Checkbox
            id="binary"
            v-model="remember"
            :binary="true"
            tabindex="4"
          />
          <label for="binary">{{ t('Remember me') }}</label>
        </div>

        <a
          id="forgot"
          class="font-medium text-ch-primary hover:text-ch-primary-dark"
          href="/main/auth/lostPassword.php"
          tabindex="5"
        >
          {{ t('Forgot your password ?') }}
        </a>
      </div>

      <input
        name="remember"
        type="hidden"
        value="true"
      >
    </form>
  </div>
</template>

<script setup>
import {useStore} from 'vuex';
import {computed, ref} from "vue";
import {useRoute, useRouter} from "vue-router";
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Checkbox from 'primevue/checkbox';
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
