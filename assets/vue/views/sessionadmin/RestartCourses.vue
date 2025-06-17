<template>
  <div class="space-y-4">
    <h1 class="text-2xl font-bold text-gray-800">
      {{ t("Restartable courses") }}
    </h1>

    <div
      v-for="item in list"
      :key="`${item.user.id}-${item.session.id}-${item.course.id}`"
      class="p-4 bg-white rounded-xl shadow border flex justify-between items-center"
    >
      <div>
        <p class="text-base font-medium">
          <span class="font-semibold">{{ item.user.name }}</span>
          &mdash; <span class="text-gray-700">{{ item.course.title }}</span> &mdash;
          <span class="text-gray-500">{{ item.session.title }}</span>
        </p>
        <p class="text-sm text-gray-400">{{ t("Ended") }}: {{ formatDate(item.session.endDate) }}</p>
      </div>

      <Button
        class="p-button-sm p-button-outlined"
        icon="pi pi-clock"
        :label="t('Extend one week')"
        :disabled="actionLoading"
        @click="extend(item)"
      />
    </div>

    <div class="flex justify-center mt-4">
      <Button
        v-if="!loading && hasMore"
        class="p-button-text"
        icon="pi pi-chevron-down"
        :label="t('Load more')"
        @click="loadMore"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue"
import { useI18n } from "vue-i18n"
import { useToast } from "primevue/usetoast"
import Button from "primevue/button"
import courseService from "../../services/courseService"

const { t } = useI18n()
const toast = useToast()
const list = ref([])
const offset = ref(0)
const limit = 10
const loading = ref(false)
const actionLoading = ref(false)
const hasMore = ref(true)

const formatDate = (d) => new Date(d).toLocaleDateString()

async function loadMore() {
  if (loading.value) return
  loading.value = true
  try {
    const res = await courseService.getRestartableCourses(offset.value, limit)
    list.value.push(...res.items)
    offset.value += limit
    hasMore.value = res.count === limit
  } catch {
    toast.add({ severity: "error", summary: t("Error"), detail: t("Could not load data") })
  } finally {
    loading.value = false
  }
}

async function extend(item) {
  if (actionLoading.value) return
  actionLoading.value = true
  try {
    const { newEndDate } = await courseService.extendSessionByWeek(
      item.session.id,
      item.user.id,
      item.course.id
    )

    item.session = { ...item.session, endDate: newEndDate }
    toast.add({
      severity: "success",
      summary: t("Success"),
      detail: t("Session extended"),
    })
  } catch {
    toast.add({
      severity: "error",
      summary: t("Error"),
      detail: t("Action failed"),
    })
  } finally {
    actionLoading.value = false
  }
}

onMounted(loadMore)
</script>
