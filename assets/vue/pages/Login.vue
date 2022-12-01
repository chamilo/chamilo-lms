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
import { computed } from 'vue';
import { useStore } from "vuex";
import { useRouter } from "vue-router";

import Login from '../components/Login';

const router = useRouter();
const store = useStore();

const isAuthenticated = computed(() => store.getters["security/isAuthenticated"]);

// If user is already logged in redirect to home.
if (isAuthenticated.value) {
    router.push({path: "/home"});
}
</script>
