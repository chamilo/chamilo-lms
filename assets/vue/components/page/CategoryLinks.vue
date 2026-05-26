<template>
  <div
    v-if="items.length"
    class="mb-2"
  >
    <div class="flex flex-col gap-1">
      <a
        v-for="it in items"
        :key="it.slug"
        class="text-sm text-gray-50 hover:text-gray-30 hover:underline"
        :href="`/pages/${it.slug}`"
      >
        {{ it.title }}
      </a>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref, watch } from "vue"
import { useI18n } from "vue-i18n"

const props = defineProps({
  category: { type: String, required: true },
})

const { locale } = useI18n()
const items = ref([])

const load = async () => {
  const loc = (locale.value || "").toString()
  const url = `/pages/_category-links?category=${encodeURIComponent(props.category)}&locale=${encodeURIComponent(loc)}`
  const res = await fetch(url, { headers: { Accept: "application/json" } })
  if (!res.ok) {
    items.value = []
    return
  }
  const data = await res.json()
  items.value = Array.isArray(data.items) ? data.items : []
}

onMounted(load)
watch(() => locale.value, load)
</script>
