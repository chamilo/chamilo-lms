<template>
  <div class=" flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
      <div>
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
          {{ $t('Sign in') }}
        </h2>
      </div>
      <Login />
    </div>
  </div>
</template>

<script>
import Login from '../components/Login';
import {useRouter} from "vue-router";
import {useStore} from "vuex";
export default {
  components: {
    Login
  },
  name: "LoginPage",
  setup() {
    const router = useRouter();
    const store = useStore();
    // If user is already logged in redirect to home.
    if (store.getters["security/isAuthenticated"]) {
        router.push({path: "/home"});
    }
  }
}
</script>