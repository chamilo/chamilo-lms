<template>
  <section
    v-if="categories.length"
    class="mt-8 w-full"
  >
    <div class="mb-4 flex items-center justify-between gap-4">
      <h2 class="text-xl font-semibold text-gray-90">
        {{ t("Course categories") }}
      </h2>
    </div>

    <div class="rounded-2xl border border-gray-25 bg-white p-4 shadow-sm">
      <HomeCourseCategoryTreeNode
        v-for="category in categories"
        :key="category.id"
        :category="category"
        :level="0"
      />
    </div>
  </section>
</template>

<script setup>
import { onMounted, ref } from "vue"
import { useI18n } from "vue-i18n"
import baseService from "../../services/baseService"
import HomeCourseCategoryTreeNode from "./HomeCourseCategoryTreeNode.vue"

const { t } = useI18n()

const categories = ref([])

onMounted(async () => {
  try {
    const response = await baseService.get("/home-categories-data")
    categories.value = Array.isArray(response.items) ? response.items : []
  } catch (error) {
    console.error(error)
    categories.value = []
  }
})
</script>
