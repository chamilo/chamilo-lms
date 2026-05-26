<template>
  <div :class="wrapperClass">
    <RouterLink
      :to="categoryRoute"
      class="group flex items-start gap-3 rounded-xl border border-transparent px-3 py-3 transition hover:border-primary hover:bg-primary/5 focus:outline-none focus:ring-2 focus:ring-primary/30"
    >
      <span class="mdi mdi-folder-outline ch-tool-icon mt-0.5 shrink-0 text-xl group-hover:text-primary" />

      <div class="min-w-0 flex-1">
        <div class="flex flex-wrap items-start justify-between gap-2">
          <div class="min-w-0">
            <h3 class="break-words text-base font-semibold text-gray-90 group-hover:text-primary">
              {{ category.title }}
            </h3>

            <p
              v-if="category.code"
              class="mt-1 text-xs uppercase tracking-wide text-gray-50"
            >
              {{ category.code }}
            </p>
          </div>

          <span class="shrink-0 rounded-full bg-primary px-2.5 py-1 text-xs font-semibold text-white">
            {{ courseCount }} {{ t("Courses") }}
          </span>
        </div>

        <p
          v-if="category.description"
          class="mt-2 text-sm text-gray-60"
        >
          {{ category.description }}
        </p>
      </div>
    </RouterLink>

    <div
      v-if="children.length"
      class="ml-5 border-l border-gray-25 pl-3"
    >
      <HomeCourseCategoryTreeNode
        v-for="child in children"
        :key="child.id"
        :category="child"
        :level="level + 1"
      />
    </div>
  </div>
</template>

<script setup>
import { computed } from "vue"
import { RouterLink } from "vue-router"
import { useI18n } from "vue-i18n"

const props = defineProps({
  category: {
    type: Object,
    required: true,
  },
  level: {
    type: Number,
    default: 0,
  },
})

const { t } = useI18n()

const children = computed(() => (Array.isArray(props.category.children) ? props.category.children : []))

const courseCount = computed(() => {
  const total = Number(props.category.visibleCourseTotalCount ?? 0)

  if (total > 0) {
    return total
  }

  return Number(props.category.visibleCourseCount ?? 0)
})

const categoryRoute = computed(() => ({
  name: "CatalogueCourses",
  query: {
    categories: props.category.iri || `/api/course_categories/${props.category.id}`,
  },
}))

const wrapperClass = computed(() => [
  "py-1",
  props.level > 0 ? "mt-1" : "",
])
</script>
