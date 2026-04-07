<template>
  <div class="mx-auto max-w-5xl space-y-8 px-4 py-8">
    <h1 class="text-2xl font-semibold text-gray-90">{{ t("My services") }}</h1>

    <!-- Active services -->
    <section>
      <h2 class="mb-4 text-lg font-semibold text-gray-90">{{ t("Active services") }}</h2>

      <div
        v-if="isLoading"
        class="text-body-2 text-gray-50"
      >
        {{ t("Loading") }}…
      </div>

      <div
        v-else-if="activeServices.length === 0"
        class="rounded-2xl border border-gray-20 bg-support-2 px-5 py-4 text-body-2 text-gray-50"
      >
        {{ t("No active services") }}
      </div>

      <div
        v-else
        class="grid gap-4 sm:grid-cols-2"
      >
        <div
          v-for="service in activeServices"
          :key="service.id"
          class="rounded-2xl border border-gray-20 bg-white p-5 shadow-sm"
        >
          <p class="text-body-1 font-semibold text-gray-90">{{ service.name }}</p>
          <p class="mt-1 text-body-2 text-gray-50">{{ service.description }}</p>
          <p class="mt-3 text-caption text-gray-50">
            {{ t("Valid until") }}: <span class="font-semibold text-gray-90">{{ formatDate(service.date_end) }}</span>
          </p>
        </div>
      </div>
    </section>

    <!-- Purchase history -->
    <section>
      <h2 class="mb-4 text-lg font-semibold text-gray-90">{{ t("Purchase history") }}</h2>

      <div
        v-if="isLoading"
        class="text-body-2 text-gray-50"
      >
        {{ t("Loading") }}…
      </div>

      <div
        v-else-if="purchaseHistory.length === 0"
        class="rounded-2xl border border-gray-20 bg-support-2 px-5 py-4 text-body-2 text-gray-50"
      >
        {{ t("No purchases yet") }}
      </div>

      <div
        v-else
        class="overflow-hidden rounded-2xl border border-gray-20"
      >
        <table class="w-full text-left text-body-2">
          <thead class="bg-support-2">
            <tr>
              <th class="px-4 py-3 font-semibold text-gray-70">{{ t("Date") }}</th>
              <th class="px-4 py-3 font-semibold text-gray-70">{{ t("Type") }}</th>
              <th class="px-4 py-3 font-semibold text-gray-70">{{ t("Product") }}</th>
              <th class="px-4 py-3 font-semibold text-gray-70">{{ t("Amount") }}</th>
              <th class="px-4 py-3 font-semibold text-gray-70">{{ t("Receipt") }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-20 bg-white">
            <tr
              v-for="purchase in purchaseHistory"
              :key="`${purchase.type}-${purchase.id}`"
            >
              <td class="px-4 py-3 text-gray-70">{{ formatDate(purchase.date) }}</td>
              <td class="px-4 py-3">
                <span
                  class="inline-flex items-center rounded-full px-2.5 py-0.5 text-caption font-semibold"
                  :class="purchase.type === 'service' ? 'bg-support-1 text-primary' : 'bg-support-2 text-gray-70'"
                >
                  {{ purchase.type === "service" ? t("Service") : t("Course") }}
                </span>
              </td>
              <td class="px-4 py-3 text-gray-90">{{ purchase.product_name }}</td>
              <td class="px-4 py-3 text-gray-70">{{ purchase.price }}</td>
              <td class="px-4 py-3">
                <a
                  v-if="purchase.status == completedStatus"
                  :href="receiptUrl(purchase)"
                  target="_blank"
                  class="text-primary hover:underline"
                >
                  {{ t("Download") }}
                </a>
                <span
                  v-else
                  class="text-gray-50"
                >—</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue"
import { useI18n } from "vue-i18n"
import axios from "axios"

const { t } = useI18n()

const isLoading = ref(true)
const activeServices = ref([])
const purchaseHistory = ref([])

const completedStatus = 1

onMounted(async () => {
  try {
    const { data } = await axios.get("/my-services-data")
    activeServices.value = data.activeServices ?? []
    purchaseHistory.value = data.purchaseHistory ?? []
  } finally {
    isLoading.value = false
  }
})

function formatDate(dateStr) {
  if (!dateStr) return "—"
  return dateStr.substring(0, 10)
}

function receiptUrl(purchase) {
  if (purchase.type === "service") {
    return `/plugin/BuyCourses/src/invoice.php?sale_id=${purchase.id}&is_service=1`
  }
  return `/plugin/BuyCourses/src/invoice.php?sale_id=${purchase.id}`
}
</script>
