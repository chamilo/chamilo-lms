<template>
  <div
    v-if="buyCoursesEnabled === true"
    class="mx-auto w-full max-w-[1600px] space-y-8 px-4 py-8 sm:px-6 lg:px-8 2xl:px-10"
  >
    <header class="rounded-3xl border border-gray-20 bg-white p-6 shadow-sm lg:p-8">
      <div class="space-y-3">
        <div class="inline-flex items-center rounded-full bg-support-1 px-3 py-1 text-xs font-semibold text-support-4">
          {{ t("Shop") }}
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

    <div
      v-if="actionMessage"
      class="rounded-2xl border border-success bg-support-3 px-5 py-4 text-body-2 text-gray-90"
    >
      {{ actionMessage }}
    </div>

    <section class="space-y-4">
      <h2 class="text-xl font-semibold text-gray-90">
        {{ t("Active services") }}
      </h2>

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
                <div class="inline-flex items-center rounded-full bg-support-2 px-3 py-1 text-xs font-semibold text-gray-50">
                  {{ t("Valid until") }}: {{ formatDate(service.dateEnd || service.date_end) }}
                </div>
              </div>
              <p class="text-body-2 leading-6 text-gray-50">
                {{ getPlainText(service.description || service.service_information) || "—" }}
              </p>
            </div>

            <div
              v-if="isRenewableService(service)"
              class="rounded-2xl border border-gray-20 bg-support-2 px-4 py-4"
            >
              <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div class="space-y-1">
                  <p class="text-sm font-semibold text-gray-90">
                    {{ t("Recurring payment") }}
                  </p>
                  <p class="text-xs leading-5 text-gray-50">
                    {{ getRecurringPaymentStatusText(service) }}
                  </p>
                  <p
                    v-if="service.plannedRenewalDate || service.nextChargeDate || service.next_charge_date || service.dateEnd"
                    class="text-xs font-medium text-primary"
                  >
                    {{ service.renewalDateLabel || t("Next charge") }}:
                    {{ formatDate(service.plannedRenewalDate || service.nextChargeDate || service.next_charge_date || service.dateEnd) }}
                  </p>
                  <p
                    v-if="service.recurringProfileId || service.recurring_profile_id"
                    class="text-xs text-gray-50"
                  >
                    {{ t("Profile") }}: {{ service.recurringProfileId || service.recurring_profile_id }}
                  </p>
                </div>

                <div class="flex flex-wrap gap-2">
                  <a
                    v-if="service.canEnableRecurringPayment && service.recurringPaymentUrl"
                    :href="service.recurringPaymentUrl"
                    class="inline-flex items-center justify-center rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:opacity-90"
                  >
                    {{ t("Enable auto billing") }}
                  </a>
                  <button
                    v-if="service.canRestoreRecurringPayment && service.restoreRecurringPaymentUrl"
                    type="button"
                    class="inline-flex items-center justify-center rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-50"
                    :disabled="isCancellingRenewal || isRestoringRenewal"
                    @click="confirmRestoreRenewal(service)"
                  >
                    {{ service.restoreRenewalButtonLabel || t("Restore renewal") }}
                  </button>
                  <button
                    v-if="service.canCancelRecurringPayment && service.cancelRecurringPaymentUrl"
                    type="button"
                    class="inline-flex items-center justify-center rounded-xl border border-danger px-4 py-2 text-sm font-semibold text-danger transition hover:bg-danger hover:text-white disabled:cursor-not-allowed disabled:opacity-50"
                    :disabled="isCancellingRenewal || isRestoringRenewal"
                    @click="openCancelRenewalModal(service)"
                  >
                    {{ service.cancelRenewalButtonLabel || t("Cancel renewal") }}
                  </button>
                </div>
              </div>
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
                  <p class="text-sm font-semibold text-gray-90">
                    {{ benefit.title || "—" }}
                  </p>
                  <p class="mt-1 text-xs leading-5 text-gray-50">
                    {{ benefit.description || "—" }}
                  </p>
                  <p class="pt-2 text-sm font-semibold text-gray-90">
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

            <div class="flex flex-wrap gap-2">
              <a
                v-if="service.infoUrl"
                :href="service.infoUrl"
                class="inline-flex items-center justify-center rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:opacity-90"
              >
                {{ t("Info") }}
              </a>
            </div>
          </div>
        </article>
      </div>
    </section>

    <section class="space-y-4">
      <h2 class="text-xl font-semibold text-gray-90">
        {{ t("Purchase history") }}
      </h2>

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
                <th class="whitespace-nowrap px-4 py-3 font-semibold text-gray-50">{{ t("Date") }}</th>
                <th class="whitespace-nowrap px-4 py-3 font-semibold text-gray-50">{{ t("Type") }}</th>
                <th class="whitespace-nowrap px-4 py-3 font-semibold text-gray-50">{{ t("Product") }}</th>
                <th class="whitespace-nowrap px-4 py-3 font-semibold text-gray-50">{{ t("Reference") }}</th>
                <th class="whitespace-nowrap px-4 py-3 font-semibold text-gray-50">{{ t("Amount") }}</th>
                <th class="whitespace-nowrap px-4 py-3 font-semibold text-gray-50">{{ t("Documents") }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-20">
              <tr
                v-for="purchase in purchaseHistory"
                :key="getPurchaseKey(purchase)"
              >
                <td class="whitespace-nowrap px-4 py-3 text-gray-50">{{ formatDateTime(purchase.date) }}</td>
                <td class="whitespace-nowrap px-4 py-3 text-gray-90">{{ purchase.type || "—" }}</td>
                <td class="px-4 py-3 text-gray-90">{{ purchase.productName || purchase.product_name || "—" }}</td>
                <td class="whitespace-nowrap px-4 py-3 text-gray-50">{{ purchase.reference || "—" }}</td>
                <td class="whitespace-nowrap px-4 py-3 font-medium text-gray-90">{{ purchase.amount || "—" }}</td>
                <td class="whitespace-nowrap px-4 py-3 space-x-3">
                  <a
                    v-if="purchase.receiptUrl || purchase.receipt_url"
                    :href="purchase.receiptUrl || purchase.receipt_url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="text-sm font-semibold text-primary transition hover:underline"
                  >
                    {{ t("Receipt") }}
                  </a>
                  <a
                    v-if="purchase.invoiceUrl || purchase.invoice_url"
                    :href="purchase.invoiceUrl || purchase.invoice_url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="text-sm font-semibold text-primary transition hover:underline"
                  >
                    {{ t("Invoice") }}
                  </a>
                  <a
                    v-else-if="purchase.requestInvoiceUrl || purchase.request_invoice_url"
                    :href="purchase.requestInvoiceUrl || purchase.request_invoice_url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="text-sm font-semibold text-primary transition hover:underline"
                  >
                    {{ t("Request invoice") }}
                  </a>
                  <span
                    v-if="
                      !(purchase.receiptUrl || purchase.receipt_url) &&
                      !(purchase.invoiceUrl || purchase.invoice_url) &&
                      !(purchase.requestInvoiceUrl || purchase.request_invoice_url)
                    "
                    class="text-gray-50"
                    >—</span
                  >
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </section>
  </div>

  <Teleport to="body">
    <div
      v-if="cancelRenewalService"
      class="fixed inset-0 z-[1000] flex items-center justify-center bg-black/50 px-4 py-8"
      role="presentation"
      @click.self="closeCancelRenewalModal"
    >
      <section
        class="w-full max-w-2xl rounded-3xl bg-white p-6 shadow-xl sm:p-8"
        role="dialog"
        aria-modal="true"
        :aria-labelledby="`cancel-renewal-title-${cancelRenewalService.id}`"
      >
        <div class="space-y-5">
          <div class="space-y-2">
            <h2
              :id="`cancel-renewal-title-${cancelRenewalService.id}`"
              class="text-xl font-semibold text-gray-90"
            >
              {{ cancelRenewalService.cancelRenewalTitle || cancelRenewalService.cancelRenewalButtonLabel }}
            </h2>
            <p class="text-body-2 leading-6 text-gray-50">
              {{ cancelRenewalService.cancelRenewalMessage }}
            </p>
          </div>

          <div
            v-if="cancelRenewalError"
            class="rounded-2xl border border-danger bg-support-6 px-4 py-3 text-sm text-danger"
          >
            {{ cancelRenewalError }}
          </div>

          <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <button
              type="button"
              class="inline-flex items-center justify-center rounded-xl border border-gray-30 px-4 py-2 text-sm font-semibold text-gray-70 transition hover:bg-support-2 disabled:cursor-not-allowed disabled:opacity-50"
              :disabled="isCancellingRenewal"
              @click="closeCancelRenewalModal"
            >
              {{ cancelRenewalService.cancelRenewalDismissLabel || t("I changed my mind") }}
            </button>
            <button
              type="button"
              class="inline-flex items-center justify-center rounded-xl bg-danger px-4 py-2 text-sm font-semibold text-white transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-50"
              :disabled="isCancellingRenewal"
              @click="confirmCancelRenewal"
            >
              {{ cancelRenewalService.cancelRenewalButtonLabel || t("Cancel renewal") }}
            </button>
          </div>
        </div>
      </section>
    </div>
  </Teleport>
</template>

<script setup>
import buyCoursesService from "../../services/buyCoursesService"
import { computed, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useRouter } from "vue-router"
import { usePlatformConfig } from "../../store/platformConfig"

const { t } = useI18n()
const router = useRouter()
const platformConfigStore = usePlatformConfig()

const isLoading = ref(false)
const loadError = ref("")
const actionMessage = ref("")
const activeServices = ref([])
const purchaseHistory = ref([])
const hasResolvedPluginState = ref(false)
const cancelRenewalService = ref(null)
const cancelRenewalError = ref("")
const isCancellingRenewal = ref(false)
const isRestoringRenewal = ref(false)

function normalizeBooleanFlag(value) {
  if (typeof value === "boolean") return value
  if (typeof value === "number") return value === 1
  if (typeof value === "string") return ["1", "true", "yes", "on"].includes(value.trim().toLowerCase())
  return false
}

const buyCoursesConfig = computed(() => platformConfigStore.plugins?.buycourses ?? null)
const buyCoursesEnabled = computed(() => {
  if (buyCoursesConfig.value === null) return null
  return normalizeBooleanFlag(buyCoursesConfig.value.enabled)
})

async function redirectToIndex() {
  await router.replace({ name: "Index" })
}

async function loadMyServicesData() {
  isLoading.value = true
  loadError.value = ""
  try {
    const data = await buyCoursesService.getMyServices()
    activeServices.value = Array.isArray(data?.activeServices) ? data.activeServices : []
    purchaseHistory.value = Array.isArray(data?.purchaseHistory) ? data.purchaseHistory : []
    loadError.value = typeof data?.error === "string" ? data.error : ""
  } catch (error) {
    if ((error?.response?.status ?? 0) === 404) {
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
    if (enabled === null || hasResolvedPluginState.value) return
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
  if (!dateValue) return "—"
  const date = new Date(dateValue)
  if (Number.isNaN(date.getTime())) return String(dateValue).substring(0, 10)
  return new Intl.DateTimeFormat(undefined, { year: "numeric", month: "2-digit", day: "2-digit" }).format(date)
}

// Used only for the purchase-history date column, which needs the time (h:i, no
// seconds) to disambiguate purchases made on the same day. formatDate() above stays
// date-only since it's also used for the unrelated "Valid until"/"Next charge" fields.
function formatDateTime(dateValue) {
  if (!dateValue) return "—"
  const date = new Date(dateValue)
  if (Number.isNaN(date.getTime())) return String(dateValue).substring(0, 10)
  return new Intl.DateTimeFormat(undefined, {
    year: "numeric",
    month: "2-digit",
    day: "2-digit",
    hour: "2-digit",
    minute: "2-digit",
  }).format(date)
}

function getPlainText(value) {
  if (!value) return ""
  const temp = document.createElement("div")
  temp.innerHTML = String(value)
  return (temp.textContent || temp.innerText || "").replace(/\s+/g, " ").trim()
}

function getBenefitList(service) {
  return service.benefitSummaries ?? service.benefit_summaries ?? []
}

function getBenefitKey(service, benefit) {
  return [service.id ?? "service", benefit.title ?? benefit.description ?? "benefit", benefit.grantedValue ?? benefit.granted_value ?? ""].join("-")
}

function formatBenefitValue(benefit) {
  const value = benefit.grantedValue ?? benefit.granted_value
  const unit = benefit.unit ?? ""
  if (value === undefined || value === null || value === "") return "—"
  return unit ? `${value} ${unit}` : String(value)
}

function getPurchaseKey(purchase) {
  return [purchase.date ?? "", purchase.reference ?? "", purchase.productName ?? purchase.product_name ?? "", purchase.type ?? ""].join("-")
}

function isRenewableService(service) {
  return normalizeBooleanFlag(service.isRenewable ?? service.is_renewable ?? service.renewable)
}

function getRecurringPaymentStatusText(service) {
  const status = Number(service.recurringPayment ?? service.recurring_payment ?? 0)
  if (status === 1) return t("Auto billing enabled")
  if (status === 2) return t("Auto billing suspended")
  if (status === -1) return t("Auto billing cancelled")
  return t("Auto billing disabled")
}

function openCancelRenewalModal(service) {
  cancelRenewalError.value = ""
  cancelRenewalService.value = service
}

function closeCancelRenewalModal() {
  if (isCancellingRenewal.value) return
  cancelRenewalError.value = ""
  cancelRenewalService.value = null
}

async function confirmCancelRenewal() {
  const service = cancelRenewalService.value
  if (!service?.cancelRecurringPaymentUrl || !service?.cancelRecurringPaymentToken) return

  isCancellingRenewal.value = true
  cancelRenewalError.value = ""
  actionMessage.value = ""

  try {
    const formData = new URLSearchParams()
    formData.set("order", String(service.id))
    formData.set("action", "cancel_recurring_payment")
    formData.set("sec_token", service.cancelRecurringPaymentToken)

    const response = await fetch(service.cancelRecurringPaymentUrl, {
      method: "POST",
      credentials: "same-origin",
      headers: {
        Accept: "application/json",
        "Content-Type": "application/x-www-form-urlencoded;charset=UTF-8",
      },
      body: formData.toString(),
    })

    const result = await response.json().catch(() => ({}))
    if (!response.ok || result?.success !== true) {
      throw new Error(result?.message || t("Unable to cancel renewal right now."))
    }

    actionMessage.value = result.message || service.cancelRenewalButtonLabel
    cancelRenewalService.value = null
    await loadMyServicesData()
  } catch (error) {
    cancelRenewalError.value = error?.message || t("Unable to cancel renewal right now.")
  } finally {
    isCancellingRenewal.value = false
  }
}

async function confirmRestoreRenewal(service) {
  if (!service?.restoreRecurringPaymentUrl || !service?.restoreRecurringPaymentToken) return

  const message = service.restoreRenewalMessage || t("Are you sure you want to restore automatic renewal?")
  if (!window.confirm(message)) return

  isRestoringRenewal.value = true
  loadError.value = ""
  actionMessage.value = ""

  try {
    const formData = new URLSearchParams()
    formData.set("order", String(service.id))
    formData.set("action", "restore_recurring_payment")
    formData.set("sec_token", service.restoreRecurringPaymentToken)

    const response = await fetch(service.restoreRecurringPaymentUrl, {
      method: "POST",
      credentials: "same-origin",
      headers: {
        Accept: "application/json",
        "Content-Type": "application/x-www-form-urlencoded;charset=UTF-8",
      },
      body: formData.toString(),
    })

    const result = await response.json().catch(() => ({}))
    if (!response.ok || result?.success !== true) {
      throw new Error(result?.message || t("Unable to restore renewal right now."))
    }

    actionMessage.value = result.message || service.restoreRenewalButtonLabel
    await loadMyServicesData()
  } catch (error) {
    loadError.value = error?.message || t("Unable to restore renewal right now.")
  } finally {
    isRestoringRenewal.value = false
  }
}
</script>
