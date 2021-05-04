<template>
  <div class="flex flex-col items-center justify-center min-h-screen p-4 space-y-4 antialiased text-gray-900 bg-white">
    <router-view />
    <slot></slot>
  </div>
</template>

<script>
export default {
  name: "EmptyLayout",
  components: {},
  setup() {
  }
}
</script>