<template>
  <div class="blog-layout">
    <SectionHeader :title="blog?.title || t('Blogs')">
      <div
        class="text-h6"
        v-text="blog?.subtitle"
      />
    </SectionHeader>

    <BaseToolbar>
      <template #start>
        <!-- Primary nav -->
        <BaseButton
          :label="t('Posts')"
          :route="{ name: 'BlogPosts', params: route.params, query: route.query }"
          :type="route.name === 'BlogPosts' ? 'primary' : 'primary-alternative'"
          icon=""
        />

        <BaseButton
          :label="t('Tasks')"
          :route="{ name: 'BlogTasks', params: route.params, query: route.query }"
          :type="route.name === 'BlogTasks' ? 'primary' : 'primary-alternative'"
          icon=""
        />

        <BaseButton
          :label="t('Members')"
          :route="{ name: 'BlogMembers', params: route.params, query: route.query }"
          :type="route.name === 'BlogMembers' ? 'primary' : 'primary-alternative'"
          icon=""
        />

        <!-- Visible to course admins/teachers only -->
        <BaseButton
          v-if="isAdminOrTeacher"
          :label="t('Projects')"
          :route="{
            name: 'BlogsAdmin',
            params: { ...route.params, node: route.params.node ?? 'course' },
            query: route.query,
          }"
          :type="route.name === 'BlogsAdmin' ? 'primary' : 'primary-alternative'"
          icon=""
        />
      </template>
    </BaseToolbar>

    <section>
      <RouterView />
    </section>
  </div>
</template>

<script setup>
import { onMounted, watch, ref, computed } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute } from "vue-router"
import SectionHeader from "../layout/SectionHeader.vue"
import BaseToolbar from "../basecomponents/BaseToolbar.vue"
import service from "../../services/blogs"
import { useSecurityStore } from "../../store/securityStore"
import BaseButton from "../basecomponents/BaseButton.vue"

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
    console.warn("BlogLayout: failed to fetch blog meta", e)
    blog.value = null
  }
}

onMounted(loadBlogMeta)
watch(() => route.params.blogId, loadBlogMeta)
</script>
