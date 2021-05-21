<template>
    <router-view />
    <slot></slot>
</template>

<script>
export default {
  name: "EmptyLayout",
  components: {},
  setup() {
  }
}
</script>