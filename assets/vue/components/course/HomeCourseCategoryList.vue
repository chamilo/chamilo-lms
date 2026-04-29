<template>
  <div
    v-if="categories.length"
    class="w-full mt-8"
  >
    <h2 class="text-xl font-semibold mb-4">
      {{ t("Course categories") }}
    </h2>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <RouterLink
        v-for="category in categories"
        :key="category.id"
        :to="getCategoryRoute(category)"
        class="block rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-blue-300 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-blue-300"
      >
        <div class="flex items-start gap-3">
          <span class="mdi mdi-view-grid-outline ch-tool-icon mt-0.5" />

          <div class="min-w-0 w-full">
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <h3 class="text-base font-semibold text-gray-900 break-words">
                  {{ category.title }}
                </h3>

                <p
                  v-if="category.code"
                  class="mt-1 text-xs uppercase tracking-wide text-gray-500"
                >
                  {{ category.code }}
                </p>
              </div>

              <span class="shrink-0 rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-700">
                {{ category.visibleCourseCount }} {{ t("Courses") }}
              </span>
            </div>

            <p
              v-if="category.description"
              class="mt-3 text-sm text-gray-600"
            >
              {{ category.description }}
            </p>
          </div>
        </div>
      </RouterLink>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from "vue"
import { useI18n } from "vue-i18n"
import { RouterLink } from "vue-router"
import baseService from "../../services/baseService"

const { t } = useI18n()

const categories = ref([])

function getCategoryRoute(category) {
  return {
    name: "CatalogueCourses",
    query: {
      categories: category.iri || `/api/course_categories/${category.id}`,
    },
  }
}

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
