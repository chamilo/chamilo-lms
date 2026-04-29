<template>
  <div
    v-if="buyCoursesEnabled === true"
    class="mx-auto w-full max-w-[1600px] space-y-8 px-4 py-8 sm:px-6 lg:px-8 2xl:px-10"
  >
    <header class="rounded-3xl border border-gray-20 bg-white p-6 shadow-sm lg:p-8">
      <div class="space-y-3">
        <div class="inline-flex items-center rounded-full bg-support-1 px-3 py-1 text-xs font-semibold text-support-4">
          {{ t("Buy courses") }}
        </div>

        <div>
          <h1 class="text-2xl font-semibold tracking-tight text-gray-90 sm:text-3xl">
            {{ t("My services") }}
          </h1>
          <p class="mt-2 max-w-3xl text-body-2 text-gray-50">
            {{ t("Review your active service benefits, purchase history and available receipts.") }}
          </p>
        </div>
      </div>
    </header>

    <div
      v-if="loadError"
      class="rounded-2xl border border-warning bg-support-6 px-5 py-4 text-body-2 text-gray-90"
    >
      {{ loadError }}
    </div>

    <section class="space-y-4">
      <div class="flex items-center justify-between gap-4">
        <h2 class="text-xl font-semibold text-gray-90">
          {{ t("Active services") }}
        </h2>
      </div>

      <div
        v-if="isLoading"
        class="rounded-2xl border border-gray-20 bg-support-2 px-5 py-4 text-body-2 text-gray-50"
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
        class="grid gap-6 xl:grid-cols-2"
      >
        <article
          v-for="service in activeServices"
          :key="service.id"
          class="rounded-3xl border border-gray-20 bg-white p-6 shadow-sm"
        >
          <div class="space-y-5">
            <div class="space-y-2">
              <div class="flex flex-wrap items-start justify-between gap-3">
                <h3 class="text-xl font-semibold text-gray-90">
                  {{ service.name || "—" }}
                </h3>

                <div
                  class="inline-flex items-center rounded-full bg-support-2 px-3 py-1 text-xs font-semibold text-gray-50"
                >
                  {{ t("Valid until") }}: {{ formatDate(service.dateEnd || service.date_end) }}
                </div>
              </div>

              <p class="text-body-2 leading-6 text-gray-50">
                {{ getPlainText(service.description || service.service_information) || "—" }}
              </p>
            </div>

            <div
              v-if="getBenefitList(service).length"
              class="space-y-3"
            >
              <p class="text-sm font-semibold text-gray-90">
                {{ t("Granted benefits") }}
              </p>

              <div class="grid gap-3">
                <div
                  v-for="benefit in getBenefitList(service)"
                  :key="getBenefitKey(service, benefit)"
                  class="rounded-2xl border border-gray-20 bg-support-2 px-4 py-4"
                >
                  <div class="space-y-1.5">
                    <p class="text-sm font-semibold text-gray-90">
                      {{ benefit.title || "—" }}
                    </p>

                    <p class="text-xs leading-5 text-gray-50">
                      {{ benefit.description || "—" }}
                    </p>

                    <p class="pt-1 text-sm font-semibold text-gray-90">
                      {{ formatBenefitValue(benefit) }}
                    </p>

                    <p
                      v-if="benefit.activeSummary || benefit.active_summary"
                      class="text-xs font-medium text-primary"
                    >
                      {{ benefit.activeSummary || benefit.active_summary }}
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </article>
      </div>
    </section>

    <section class="space-y-4">
      <div class="flex items-center justify-between gap-4">
        <h2 class="text-xl font-semibold text-gray-90">
          {{ t("Purchase history") }}
        </h2>
      </div>

      <div
        v-if="isLoading"
        class="rounded-2xl border border-gray-20 bg-support-2 px-5 py-4 text-body-2 text-gray-50"
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
        class="overflow-hidden rounded-3xl border border-gray-20 bg-white shadow-sm"
      >
        <div class="overflow-x-auto">
          <table class="min-w-full text-left text-sm">
            <thead class="bg-support-2">
              <tr>
                <th class="whitespace-nowrap px-4 py-3 font-semibold text-gray-50">
                  {{ t("Date") }}
                </th>
                <th class="whitespace-nowrap px-4 py-3 font-semibold text-gray-50">
                  {{ t("Type") }}
                </th>
                <th class="whitespace-nowrap px-4 py-3 font-semibold text-gray-50">
                  {{ t("Product") }}
                </th>
                <th class="whitespace-nowrap px-4 py-3 font-semibold text-gray-50">
                  {{ t("Reference") }}
                </th>
                <th class="whitespace-nowrap px-4 py-3 font-semibold text-gray-50">
                  {{ t("Amount") }}
                </th>
                <th class="whitespace-nowrap px-4 py-3 font-semibold text-gray-50">
                  {{ t("Receipt") }}
                </th>
              </tr>
            </thead>

            <tbody class="divide-y divide-gray-20">
              <tr
                v-for="purchase in purchaseHistory"
                :key="getPurchaseKey(purchase)"
                class="align-top"
              >
                <td class="whitespace-nowrap px-4 py-3 text-gray-50">
                  {{ formatDate(purchase.date) }}
                </td>

                <td class="whitespace-nowrap px-4 py-3 text-gray-90">
                  {{ purchase.type || "—" }}
                </td>

                <td class="px-4 py-3 text-gray-90">
                  {{ purchase.productName || purchase.product_name || "—" }}
                </td>

                <td class="whitespace-nowrap px-4 py-3 text-gray-50">
                  {{ purchase.reference || "—" }}
                </td>

                <td class="whitespace-nowrap px-4 py-3 font-medium text-gray-90">
                  {{ purchase.amount || "—" }}
                </td>

                <td class="whitespace-nowrap px-4 py-3">
                  <a
                    v-if="purchase.receiptUrl || purchase.receipt_url"
                    :href="purchase.receiptUrl || purchase.receipt_url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex items-center gap-2 text-sm font-semibold text-primary transition hover:underline"
                  >
                    {{ t("Download") }}
                  </a>

                  <span
                    v-else
                    class="text-gray-50"
                  >
                    —
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import axios from "axios"
import { computed, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useRouter } from "vue-router"
import { usePlatformConfig } from "../../store/platformConfig"

const { t } = useI18n()
const router = useRouter()
const platformConfigStore = usePlatformConfig()

const isLoading = ref(false)
const loadError = ref("")
const activeServices = ref([])
const purchaseHistory = ref([])
const hasResolvedPluginState = ref(false)

function normalizeBooleanFlag(value) {
  if (typeof value === "boolean") {
    return value
  }

  if (typeof value === "number") {
    return value === 1
  }

  if (typeof value === "string") {
    const normalized = value.trim().toLowerCase()
    return ["1", "true", "yes", "on"].includes(normalized)
  }

  return false
}

const buyCoursesConfig = computed(() => platformConfigStore.plugins?.buycourses ?? null)

const buyCoursesEnabled = computed(() => {
  if (buyCoursesConfig.value === null) {
    return null
  }

  return normalizeBooleanFlag(buyCoursesConfig.value.enabled)
})

async function redirectToIndex() {
  await router.replace({ name: "Index" })
}

async function loadMyServicesData() {
  isLoading.value = true
  loadError.value = ""

  try {
    const { data } = await axios.get("/my-services-data")

    activeServices.value = Array.isArray(data?.activeServices) ? data.activeServices : []
    purchaseHistory.value = Array.isArray(data?.purchaseHistory) ? data.purchaseHistory : []
    loadError.value = typeof data?.error === "string" ? data.error : ""
  } catch (error) {
    const status = error?.response?.status ?? 0

    if (404 === status) {
      await redirectToIndex()
      return
    }

    activeServices.value = []
    purchaseHistory.value = []
    loadError.value = t("Unable to load your services right now.")
  } finally {
    isLoading.value = false
  }
}

watch(
  buyCoursesEnabled,
  async (enabled) => {
    if (enabled === null || hasResolvedPluginState.value) {
      return
    }

    hasResolvedPluginState.value = true

    if (!enabled) {
      await redirectToIndex()
      return
    }

    await loadMyServicesData()
  },
  { immediate: true },
)

function formatDate(dateValue) {
  if (!dateValue) {
    return "—"
  }

  const date = new Date(dateValue)

  if (Number.isNaN(date.getTime())) {
    return String(dateValue).substring(0, 10)
  }

  return new Intl.DateTimeFormat(undefined, {
    year: "numeric",
    month: "2-digit",
    day: "2-digit",
  }).format(date)
}

function getPlainText(value) {
  if (!value) {
    return ""
  }

  const html = String(value)
  const temp = document.createElement("div")
  temp.innerHTML = html

  return (temp.textContent || temp.innerText || "").replace(/\s+/g, " ").trim()
}

function getBenefitList(service) {
  return service.benefitSummaries ?? service.benefit_summaries ?? []
}

function getBenefitKey(service, benefit) {
  return [
    service.id ?? "service",
    benefit.title ?? benefit.description ?? "benefit",
    benefit.grantedValue ?? benefit.granted_value ?? "",
  ].join("-")
}

function formatBenefitValue(benefit) {
  const value = benefit.grantedValue ?? benefit.granted_value
  const unit = benefit.unit ?? ""

  if (value === undefined || value === null || value === "") {
    return "—"
  }

  return unit ? `${value} ${unit}` : String(value)
}

function getPurchaseKey(purchase) {
  return [
    purchase.date ?? "",
    purchase.reference ?? "",
    purchase.productName ?? purchase.product_name ?? "",
    purchase.type ?? "",
  ].join("-")
}
</script>
