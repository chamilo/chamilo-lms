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
import baseService from "../../services/baseService"

const props = defineProps({
  category: { type: String, required: true },
})

const { locale } = useI18n()
const items = ref([])

const load = async () => {
  const loc = (locale.value || "").toString()
  try {
    const data = await baseService.get("/pages/_category-links", { category: props.category, locale: loc })
    items.value = Array.isArray(data.items) ? data.items : []
  } catch {
    items.value = []
  }
}

onMounted(load)
watch(() => locale.value, load)
</script>
