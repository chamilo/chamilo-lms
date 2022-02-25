<template>
  <form class="mt-8 space-y-4" @submit.prevent="onSubmit">
    <input type="hidden" name="remember" value="true" />
    <div class="rounded-md shadow-sm -space-y-px">
      <div>
        <label for="login" class="sr-only">{{ $t('Username') }}</label>
        <input id="login" v-model="login" name="login" type="text" autocomplete="login" required=""
               class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
               :placeholder="$t('Username')"
               tabindex="1"
        />
      </div>
      <div>
        <label for="password" class="sr-only">{{ $t('Password') }}</label>
        <input id="password" v-model="password" name="password" type="password" autocomplete="current-password"
               required=""
               class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
               :placeholder="$t('Password')"
               tabindex="2"
        />
      </div>
    </div>

    <div class="flex items-center gap-4 justify-between">
      <div class="flex items-center">
        <input id="remember_me" name="remember_me" type="checkbox"
               class="h-4 w-4 text-ch-primary focus:ch-primary-dark border-gray-900 rounded"
               tabindex="4"
        />
        <label for="remember_me" class="ml-2 block text-sm text-gray-900">
          {{ $t('Remember me') }}
        </label>
      </div>

      <div class="text-sm">
        <a href="/main/auth/lostPassword.php" id="forgot"
           class="font-medium text-ch-primary hover:text-ch-primary-dark"
           tabindex="5"
        >
          {{ $t('Forgot your password ?') }}
        </a>
      </div>
    </div>

    <div>
      <!--          class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"-->
      <button
          type="submit"
          class="btn btn-primary group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium text-white"
          tabindex="3"
      >
        <span class="absolute left-0 inset-y-0 flex items-center pl-3">
            <svg v-if="isLoading"
                 class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
          </span>
          {{ $t('Sign in') }}
      </button>
      <a
          href="/main/auth/inscription.php"
          class="btn btn-default group mt-4 relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium text-gray-600"
          tabindex="3"
      >
        <span class="absolute left-0 inset-y-0 flex items-center pl-3">
            <svg v-if="isLoading"
                 class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
          </span>
        {{ $t('Register oneself') }}
      </a>
    </div>
  </form>
</template>

<script>
import {mapGetters, useStore} from 'vuex';
import useState from "../hooks/useState";
import {ref} from "vue";
import {useRoute, useRouter} from "vue-router";

export default {
  name: "Login",
  components: {
    //ErrorMessage,
    //LockClosedIcon
  },
  setup() {
    const { isSidebarOpen } = useState();
    const route = useRoute();
    const router = useRouter();
    const store = useStore();

    const login = ref('');
    const password = ref('');

    let redirect = route.query.redirect;
    if (store.getters["security/isAuthenticated"]) {
      if (typeof redirect !== "undefined") {
        router.push({path: redirect});
      } else {
        router.push({path: "/home"});
      }
    }

    isSidebarOpen.value = false;

    function onSubmit(evt) {
      evt.preventDefault()
      performLogin();
    }

    async function performLogin() {
      let payload = {login: login.value, password: password.value};
      let redirect = route.query.redirect;
      await store.dispatch("security/login", payload);
      if (!store.getters["security/hasError"]) {
        //isSidebarOpen.value = true;
        if (typeof redirect !== "undefined") {
          router.push({path: redirect});
        } else {
          // router.replace({path: "/home"});
          window.location.href = '/home';
        }
      }
    }

    return {
      onSubmit,
      login,
      password
    }
  },

  computed: {
    ...mapGetters({
      'isLoading': 'security/isLoading',
      'hasError': 'security/hasError',
      'error': 'security/error',
    }),
  }
}
</script>
