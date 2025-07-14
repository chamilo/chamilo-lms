<template>
  <div class="p-4">
    <button
      class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"
      @click="handleClick"
    >
      {{ block?.label || "Click me" }}
    </button>
  </div>
</template>

<script setup>
import { useRouter } from "vue-router"

const props = defineProps({
  block: Object,
})

const router = useRouter()

function handleClick() {
  if (props.block?.url) {
    router.push(props.block.url)
  } else {
    alert("No URL specified.")
  }
}
</script>
