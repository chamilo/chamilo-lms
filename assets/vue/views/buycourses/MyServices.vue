<template>
  <div class="mx-auto max-w-6xl space-y-8 px-4 py-8">
    <header class="space-y-2">
      <h1 class="text-2xl font-semibold text-gray-90">{{ t("My services") }}</h1>
      <p class="text-body-2 text-gray-50">
        {{ t("Review your active service benefits, purchase history and available receipts.") }}
      </p>
    </header>

    <section class="space-y-4">
      <h2 class="text-lg font-semibold text-gray-90">{{ t("Active services") }}</h2>

      <div v-if="isLoading" class="text-body-2 text-gray-50">
        {{ t("Loading") }}…
      </div>

      <div
        v-else-if="activeServices.length === 0"
        class="rounded-2xl border border-gray-20 bg-support-2 px-5 py-4 text-body-2 text-gray-50"
      >
        {{ t("No active services") }}
      </div>

      <div v-else class="grid gap-4 lg:grid-cols-2">
        <article
          v-for="service in activeServices"
          :key="service.id"
          class="rounded-2xl border border-gray-20 bg-white p-5 shadow-sm"
        >
          <div class="space-y-2">
            <h3 class="text-body-1 font-semibold text-gray-90">{{ service.name }}</h3>
            <p class="text-body-2 text-gray-50">{{ service.description || "—" }}</p>
            <p class="text-caption text-gray-50">
              {{ t("Valid until") }}:
              <span class="font-semibold text-gray-90">{{ formatDate(service.dateEnd) }}</span>
            </p>
          </div>

          <div v-if="service.benefitSummaries?.length" class="mt-4 space-y-3">
            <p class="text-body-2 font-semibold text-gray-90">{{ t("Granted benefits") }}</p>

            <div
              v-for="benefit in service.benefitSummaries"
              :key="`${service.id}-${benefit.title}`"
              class="rounded-xl border border-gray-20 bg-support-2 px-4 py-3"
            >
              <p class="text-body-2 font-semibold text-gray-90">{{ benefit.title }}</p>
              <p class="mt-1 text-caption text-gray-50">{{ benefit.description }}</p>
              <p class="mt-2 text-caption text-gray-70">
                {{ benefit.grantedValue }} {{ benefit.unit }}
              </p>
              <p v-if="benefit.activeSummary" class="mt-1 text-caption text-primary">
                {{ benefit.activeSummary }}
              </p>
            </div>
          </div>
        </article>
      </div>
    </section>

    <section class="space-y-4">
      <h2 class="text-lg font-semibold text-gray-90">{{ t("Purchase history") }}</h2>

      <div v-if="isLoading" class="text-body-2 text-gray-50">
        {{ t("Loading") }}…
      </div>

      <div
        v-else-if="purchaseHistory.length === 0"
        class="rounded-2xl border border-gray-20 bg-support-2 px-5 py-4 text-body-2 text-gray-50"
      >
        {{ t("No purchases yet") }}
      </div>

      <div v-else class="overflow-hidden rounded-2xl border border-gray-20 bg-white">
        <table class="w-full text-left text-body-2">
          <thead class="bg-support-2">
            <tr>
              <th class="px-4 py-3 font-semibold text-gray-70">{{ t("Date") }}</th>
              <th class="px-4 py-3 font-semibold text-gray-70">{{ t("Type") }}</th>
              <th class="px-4 py-3 font-semibold text-gray-70">{{ t("Product") }}</th>
              <th class="px-4 py-3 font-semibold text-gray-70">{{ t("Reference") }}</th>
              <th class="px-4 py-3 font-semibold text-gray-70">{{ t("Amount") }}</th>
              <th class="px-4 py-3 font-semibold text-gray-70">{{ t("Receipt") }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-20">
            <tr
              v-for="purchase in purchaseHistory"
              :key="`${purchase.date}-${purchase.reference}-${purchase.productName}`"
            >
              <td class="px-4 py-3 text-gray-70">{{ formatDate(purchase.date) }}</td>
              <td class="px-4 py-3 text-gray-90">{{ purchase.type || "—" }}</td>
              <td class="px-4 py-3 text-gray-90">{{ purchase.productName || "—" }}</td>
              <td class="px-4 py-3 text-gray-70">{{ purchase.reference || "—" }}</td>
              <td class="px-4 py-3 text-gray-70">{{ purchase.amount || "—" }}</td>
              <td class="px-4 py-3">
                <a
                  v-if="purchase.receiptUrl"
                  :href="purchase.receiptUrl"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="text-primary hover:underline"
                >
                  {{ t("Download") }}
                </a>
                <span v-else class="text-gray-50">—</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
  </div>
</template>

<script setup>
import axios from "axios"
import { onMounted, ref } from "vue"
import { useI18n } from "vue-i18n"

const { t } = useI18n()

const isLoading = ref(true)
const activeServices = ref([])
const purchaseHistory = ref([])

onMounted(async () => {
  try {
    const { data } = await axios.get("/my-services-data")
    activeServices.value = data.activeServices ?? []
    purchaseHistory.value = data.purchaseHistory ?? []
  } finally {
    isLoading.value = false
  }
})

function formatDate(dateValue) {
  if (!dateValue) {
    return "—"
  }

  return String(dateValue).substring(0, 10)
}
</script>
