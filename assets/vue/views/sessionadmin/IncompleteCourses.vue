<template>
  <div class="p-6">
    <h1 class="text-2xl font-bold mb-4">{{ t("Incomplete courses") }}</h1>

    <div
      v-if="loading"
      class="text-gray-500"
    >
      {{ t("Loading") }}...
    </div>

    <table
      v-else
      class="w-full text-left border-collapse"
    >
      <thead class="bg-gray-100">
        <tr>
          <th class="p-2">{{ t("User") }}</th>
          <th class="p-2">{{ t("Course") }}</th>
          <th class="p-2">{{ t("Session") }}</th>
          <th class="p-2">{{ t("Session period") }}</th>
        </tr>
      </thead>
      <tbody>
        <tr
          v-for="item in incomplete"
          :key="`${item.user.id}-${item.course.id}-${item.session.id}`"
          class="border-b"
        >
          <td class="p-2">{{ item.user.name }}</td>
          <td class="p-2">{{ item.course.title }}</td>
          <td class="p-2">{{ item.session.title }}</td>
          <td class="p-2">{{ item.session.startDate }} - {{ item.session.endDate || "∞" }}</td>
        </tr>
      </tbody>
    </table>

    <div
      v-if="incomplete.length === 0"
      class="mt-4 text-gray-500"
    >
      {{ t("No data available") }}
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from "vue"
import { useI18n } from "vue-i18n"
import courseService from "../../services/courseService"

const { t } = useI18n()
const incomplete = ref([])
const loading = ref(false)

onMounted(async () => {
  loading.value = true
  const response = await courseService.getIncompleteCourses()
  incomplete.value = response?.items ?? []
  loading.value = false
})
</script>
