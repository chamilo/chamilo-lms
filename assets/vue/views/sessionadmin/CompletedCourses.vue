<template>
  <div class="p-6">
    <h1 class="text-2xl font-bold mb-4">{{ t("Completed courses") }}</h1>

    <div
      v-if="loading && certificates.length === 0"
      class="text-gray-500"
    >
      {{ t("Loading") }}...
    </div>

    <div v-else>
      <table class="w-full text-left border-collapse">
        <thead class="bg-gray-100">
          <tr>
            <th class="p-2">{{ t("User") }}</th>
            <th class="p-2">{{ t("Course") }}</th>
            <th class="p-2">{{ t("Session") }}</th>
            <th class="p-2">{{ t("Issued at") }}</th>
            <th class="p-2 text-right">{{ t("Download") }}</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="cert in certificates"
            :key="cert.id"
            class="border-b"
          >
            <td class="p-2">{{ cert.user.name }}</td>
            <td class="p-2">{{ cert.course.title }}</td>
            <td class="p-2">{{ cert.session?.title || '-' }}</td>
            <td class="p-2">{{ cert.issuedAt }}</td>
            <td class="p-2 text-right">
              <a
                :href="cert.downloadUrl"
                target="_blank"
                rel="noopener"
                class="text-blue-600 hover:underline"
              >
                <i class="pi pi-download" />
              </a>
            </td>
          </tr>
        </tbody>
      </table>

      <div
        v-if="hasMore"
        class="mt-4 text-center"
      >
        <button
          class="btn btn--primary"
          @click="loadMore"
          :disabled="loading"
        >
          {{ t("Load more") }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue"
import { useI18n } from "vue-i18n"
import courseService from "../../services/courseService"

const { t } = useI18n()

const certificates = ref([])
const offset = ref(0)
const limit = ref(10)
const hasMore = ref(true)
const loading = ref(false)

async function loadMore() {
  loading.value = true
  const response = await courseService.getCompletedCourses(offset.value, limit.value)

  if (response?.items?.length) {
    certificates.value.push(...response.items)
    offset.value += limit.value
    hasMore.value = response.count === limit.value
  } else {
    hasMore.value = false
  }

  loading.value = false
}

onMounted(() => {
  limit.value = 50
  loadMore().then(() => {
    limit.value = 10
  })
})
</script>
