<template>
  <div class=" flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
      <h2
        v-t="'Sign in'"
        class="mt-6 text-center text-3xl font-extrabold"
      />
      <Login />
    </div>
  </div>
</template>

<script setup>
import { useRouter } from "vue-router";

import Login from '../components/Login';
import { useSecurityStore } from "../store/securityStore"

const router = useRouter();
const securityStore = useSecurityStore()

// If user is already logged in redirect to home.
if (securityStore.isAuthenticated) {
    router.push({path: "/home"});
}
</script>
