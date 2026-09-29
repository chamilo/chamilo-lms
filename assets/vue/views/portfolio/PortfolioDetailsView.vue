<template>
  <section class="space-y-6">
    <BaseToolbar class="mb-4 border-b border-gray-25 bg-white">
      <template #start>
        <BaseButton
          icon="back"
          :label="t('Back')"
          only-icon
          size="large"
          type="primary-text"
          class="!flex !h-12 !w-12 !items-center !justify-center !rounded-xl !p-0 [&_.p-button-icon]:!text-2xl"
          :route="listRoute"
        />
      </template>
      <template #end>
        <BaseButton
          v-if="data.canExport"
          icon="file-pdf"
          :label="t('Export to PDF')"
          only-icon
          size="large"
          type="primary-text"
          class="!flex !h-12 !w-12 !items-center !justify-center !rounded-xl !p-0 [&_.p-button-icon]:!text-2xl"
          :to-url="pdfUrl"
        />
        <BaseButton
          v-if="data.canExport"
          icon="download"
          :label="t('Export to ZIP')"
          only-icon
          size="large"
          type="primary-text"
          class="!flex !h-12 !w-12 !items-center !justify-center !rounded-xl !p-0 [&_.p-button-icon]:!text-2xl"
          :to-url="zipUrl"
        />
      </template>
    </BaseToolbar>

    <div
      v-if="isLoading"
      class="rounded-xl border border-gray-20 bg-white p-6 text-center text-sm text-gray-600 shadow-sm"
    >
      {{ t("Loading...") }}
    </div>
    <div
      v-else-if="errorMessage"
      class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"
    >
      {{ errorMessage }}
    </div>

    <template v-else>
      <BaseCard>
        <template #title>
          <div class="flex items-center gap-3">
            <BaseUserAvatar
              :alt="data.owner.fullName || t('User')"
              :image-url="data.owner.imageUrl || ''"
              size="large"
            />
            <div>
              <div class="font-semibold text-gray-90">{{ data.owner.fullName }}</div>
              <div class="text-sm text-gray-500">{{ t("Portfolio details") }}</div>
            </div>
          </div>
        </template>

        <BaseSelect
          v-if="data.canSelectOwner && data.owners.length > 1"
          id="portfolio_details_owner"
          v-model="selectedOwnerId"
          :label="t('User')"
          name="user"
          :options="ownerOptions"
          @change="changeOwner"
        />

        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
          <div class="rounded-xl bg-gray-10 p-4">
            <div class="text-2xl font-semibold">{{ data.totalItems }}</div>
            <div class="text-sm text-gray-600">{{ t("Portfolio items") }}</div>
            <div v-if="data.requiredItems > 0" class="mt-1 text-xs text-gray-500">
              {{ data.totalItems }} / {{ data.requiredItems }} {{ t("required") }}
            </div>
          </div>
          <div class="rounded-xl bg-gray-10 p-4">
            <div class="text-2xl font-semibold">{{ data.totalComments }}</div>
            <div class="text-sm text-gray-600">{{ t("Comments") }}</div>
            <div v-if="data.requiredComments > 0" class="mt-1 text-xs text-gray-500">
              {{ data.totalComments }} / {{ data.requiredComments }} {{ t("required") }}
            </div>
          </div>
          <div class="rounded-xl bg-gray-10 p-4">
            <div class="text-2xl font-semibold">{{ data.itemScoreTotal }}</div>
            <div class="text-sm text-gray-600">{{ t("Item score") }}</div>
          </div>
          <div class="rounded-xl bg-gray-10 p-4">
            <div class="text-2xl font-semibold">{{ data.commentScoreTotal }}</div>
            <div class="text-sm text-gray-600">{{ t("Comment score") }}</div>
          </div>
        </div>
      </BaseCard>

      <BaseCard>
        <template #title>{{ t("Portfolio items") }}</template>
        <div v-if="data.items.length === 0" class="py-6 text-center text-sm italic text-gray-500">
          {{ t("No data available") }}
        </div>
        <div v-else class="overflow-x-auto">
          <table class="w-full border-collapse text-left text-sm">
            <thead>
              <tr class="border-b border-gray-25">
                <th class="p-3">{{ t("Title") }}</th>
                <th class="p-3">{{ t("Creation date") }}</th>
                <th class="p-3">{{ t("Last update") }}</th>
                <th class="p-3">{{ t("Category") }}</th>
                <th class="p-3">{{ t("Comments") }}</th>
                <th class="p-3">{{ t("Score") }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in data.items" :key="item.id" class="border-b border-gray-15">
                <td class="p-3">
                  <RouterLink :to="itemRoute(item.id)" class="text-primary hover:underline">
                    {{ item.title }}
                  </RouterLink>
                </td>
                <td class="p-3">{{ formatDate(item.createdAt) }}</td>
                <td class="p-3">{{ formatDate(item.updatedAt) }}</td>
                <td class="p-3">{{ item.category }}</td>
                <td class="p-3">{{ item.commentsCount }}</td>
                <td class="p-3">{{ item.score ?? "—" }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </BaseCard>

      <BaseCard>
        <template #title>{{ t("Comments made") }}</template>
        <div v-if="data.comments.length === 0" class="py-6 text-center text-sm italic text-gray-500">
          {{ t("No data available") }}
        </div>
        <div v-else class="space-y-3">
          <div v-for="comment in data.comments" :key="comment.id" class="rounded-lg border border-gray-20 p-3">
            <RouterLink :to="itemRoute(comment.itemId)" class="font-medium text-primary hover:underline">
              {{ comment.itemTitle }}
            </RouterLink>
            <div class="mt-1 text-sm text-gray-700">{{ comment.excerpt }}</div>
            <div class="mt-2 text-xs text-gray-500">
              {{ formatDate(comment.date) }} · {{ t("Score") }}: {{ comment.score ?? "—" }}
            </div>
          </div>
        </div>
      </BaseCard>
    </template>
  </section>
</template>

<script setup>
import { computed, reactive, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseCard from "../../components/basecomponents/BaseCard.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue"
import BaseUserAvatar from "../../components/basecomponents/BaseUserAvatar.vue"
import portfolioService from "../../services/portfolioService"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const isLoading = ref(false)
const errorMessage = ref("")
const selectedOwnerId = ref(null)
const data = reactive(emptyData())

const mode = computed(() => route.meta.portfolioMode || "personal")
const prefix = computed(() => (mode.value === "course" ? "PortfolioCourse" : "PortfolioPersonal"))
const listRoute = computed(() => ({
  name: `${prefix.value}List`,
  params: mode.value === "course" ? { node: route.params.node } : {},
  query: contextParams(),
}))
const ownerOptions = computed(() => data.owners.map((owner) => ({ label: owner.fullName, value: owner.id })))
const pdfUrl = computed(() => portfolioService.exportPdfUrl({ ...contextParams(), user: selectedOwnerId.value || undefined }))
const zipUrl = computed(() => portfolioService.exportZipUrl({ ...contextParams(), user: selectedOwnerId.value || undefined }))

function emptyData() {
  return {
    owner: {}, owners: [], items: [], comments: [], totalItems: 0, requiredItems: 0,
    totalComments: 0, requiredComments: 0, itemScoreTotal: 0, commentScoreTotal: 0,
    canSelectOwner: false, canExport: true,
  }
}

function firstQueryValue(value) {
  return Array.isArray(value) ? value[0] : value
}

function contextParams() {
  const params = {}
  const cid = Number(firstQueryValue(route.query.cid) || 0)
  const sid = Number(firstQueryValue(route.query.sid) || 0)
  if (cid > 0) params.cid = cid
  if (sid > 0) params.sid = sid

  return params
}

function itemRoute(id) {
  return {
    name: `${prefix.value}Item`,
    params: mode.value === "course" ? { node: route.params.node, id } : { id },
    query: { ...contextParams(), ...(selectedOwnerId.value ? { user: selectedOwnerId.value } : {}) },
  }
}

function formatDate(value) {
  const date = new Date(value)
  return Number.isNaN(date.getTime()) ? String(value || "") : new Intl.DateTimeFormat(undefined, {
    dateStyle: "medium", timeStyle: "short",
  }).format(date)
}

function changeOwner() {
  router.replace({
    name: `${prefix.value}Details`,
    params: mode.value === "course" ? { node: route.params.node } : {},
    query: { ...contextParams(), ...(selectedOwnerId.value ? { user: selectedOwnerId.value } : {}) },
  })
}

async function loadDetails() {
  isLoading.value = true
  errorMessage.value = ""
  try {
    const response = await portfolioService.getDetails({ ...contextParams(), user: Number(firstQueryValue(route.query.user) || 0) || undefined })
    Object.assign(data, emptyData(), response)
    selectedOwnerId.value = response.owner?.id || null
  } catch (error) {
    console.error("Error loading Portfolio details", error)
    errorMessage.value = error?.response?.data?.detail || error?.response?.data?.["hydra:description"] || t("An error occurred")
  } finally {
    isLoading.value = false
  }
}

watch(() => route.fullPath, () => loadDetails(), { immediate: true })
</script>
