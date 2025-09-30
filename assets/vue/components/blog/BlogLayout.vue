<template>
  <div class="dbx w-full min-h-full flex flex-col bg-[var(--surface-ground,#fff)] blog-layout">
    <BaseToolbar class="sticky top-0 z-20 bg-[var(--surface-card,#fff)]">
      <template #start>
        <div class="flex items-center gap-3">
          <i class="mdi mdi-notebook-outline text-2xl text-primary"></i>
          <div>
            <h2 class="m-0 text-lg font-semibold">
              {{ blog?.title || t("Blogs") }}
            </h2>
            <div v-if="blog?.subtitle" class="text-xs text-gray-500">
              {{ blog.subtitle }}
            </div>
          </div>
        </div>
      </template>

      <template #end>
        <!-- Primary nav -->
        <RouterLink
          class="nav-link"
          :class="{ active: $route.name === 'BlogPosts' }"
          :to="{ name:'BlogPosts', params:$route.params, query:$route.query }"
        >
          {{ t("Posts") }}
        </RouterLink>

        <RouterLink
          class="nav-link"
          :class="{ active: $route.name === 'BlogTasks' }"
          :to="{ name:'BlogTasks', params:$route.params, query:$route.query }"
        >
          {{ t("Tasks") }}
        </RouterLink>

        <RouterLink
          class="nav-link"
          :class="{ active: $route.name === 'BlogMembers' }"
          :to="{ name:'BlogMembers', params:$route.params, query:$route.query }"
        >
          {{ t("Members") }}
        </RouterLink>

        <!-- Visible to course admins/teachers only -->
        <RouterLink
          v-if="isAdminOrTeacher"
          class="nav-link"
          :class="{ active: $route.name === 'BlogsAdmin' }"
          :to="{
            name: 'BlogsAdmin',
            params: { ...$route.params, node: $route.params.node ?? 'course' },
            query: $route.query
          }"
        >
          {{ t("Projects") }}
        </RouterLink>
      </template>
    </BaseToolbar>

    <section class="p-4 md:p-6">
      <RouterView />
    </section>
  </div>
</template>

<script setup>
import { onMounted, watch, ref, computed } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute } from "vue-router"
import BaseToolbar from "../basecomponents/BaseToolbar.vue"
import service from "../../services/blogs"
import { useSecurityStore } from "../../store/securityStore"

const { t } = useI18n()
const route = useRoute()

// Access control (admin/teacher only)
const securityStore = useSecurityStore()
const isAdminOrTeacher = computed(() => securityStore.isAdmin || securityStore.isTeacher)

// Blog meta (title/subtitle)
const blog = ref(null)

async function loadBlogMeta() {
  try {
    const id = Number(route.params.blogId)
    if (!id) {
      blog.value = null
      return
    }
    blog.value = await service.getProject(id)
  } catch (e) {
    // eslint-disable-next-line no-console
    console.warn("BlogLayout: failed to fetch blog meta", e)
    blog.value = null
  }
}

onMounted(loadBlogMeta)
watch(() => route.params.blogId, loadBlogMeta)
</script>
