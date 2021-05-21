<template>
  <div class=" flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
      <div>
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
          Sign in
        </h2>
      </div>
      <Login />
    </div>
  </div>
</template>

<script>
import Login from '../components/Login';

export default {
  components: {
    Login
  },
  name: "LoginPage"
}
</script>