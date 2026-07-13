<template>
  <section class="space-y-6">
    <BaseToolbar class="mb-4 border-b border-gray-25 bg-white">
      <template #start>
        <BaseButton
          v-if="data.canCreate"
          icon="plus"
          :label="t('Add')"
          only-icon
          size="large"
          type="success"
          class="!flex !h-12 !w-12 !items-center !justify-center !rounded-xl !p-0 [&_.p-button-icon]:!text-2xl"
          :route="formRoute()"
        />
        <BaseButton
          v-if="data.canViewDetails"
          icon="view-table"
          :label="t('Details')"
          only-icon
          size="large"
          type="primary-text"
          class="!flex !h-12 !w-12 !items-center !justify-center !rounded-xl !p-0 [&_.p-button-icon]:!text-2xl"
          :route="detailsRoute"
        />
        <BaseButton
          v-if="data.canManageCategories"
          icon="folder-generic"
          :label="t('Categories')"
          only-icon
          size="large"
          type="primary-text"
          class="!flex !h-12 !w-12 !items-center !justify-center !rounded-xl !p-0 [&_.p-button-icon]:!text-2xl"
          :route="managementRoute('Categories')"
        />
        <BaseButton
          v-if="data.canManageTags"
          icon="tag-outline"
          :label="t('Tags')"
          only-icon
          size="large"
          type="primary-text"
          class="!flex !h-12 !w-12 !items-center !justify-center !rounded-xl !p-0 [&_.p-button-icon]:!text-2xl"
          :route="managementRoute('Tags')"
        />
      </template>

      <template #end>
        <BaseButton
          icon="filter"
          :label="t('Filter')"
          only-icon
          size="large"
          :type="filtersVisible ? 'primary' : 'primary-text'"
          class="!flex !h-12 !w-12 !items-center !justify-center !rounded-xl !p-0 [&_.p-button-icon]:!text-2xl"
          @click="filtersVisible = !filtersVisible"
        />
      </template>
    </BaseToolbar>

    <BaseCard
      v-if="filtersVisible"
      plain
    >
      <template #title>
        <div class="flex items-center gap-2">
          <BaseIcon icon="filter" />
          <span>{{ t("Filter") }}</span>
        </div>
      </template>

      <form
        class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3"
        @submit.prevent="applyFilters"
      >
        <BaseInputText
          id="portfolio_filter_text"
          v-model="filters.text"
          :label="t('Search')"
          name="text"
        />

        <BaseCalendar
          id="portfolio_filter_date"
          v-model="filters.date"
          :label="t('Date')"
        />

        <BaseSelect
          id="portfolio_filter_order"
          v-model="filters.order"
          :label="t('Order')"
          name="order"
          :options="orderOptions"
        />

        <BaseSelect
          v-if="data.mode === 'course' && data.authors.length"
          id="portfolio_filter_user"
          v-model="filters.user"
          :label="t('User')"
          name="user"
          :options="authorOptions"
          allow-clear
        />

        <BaseSelect
          v-if="rootCategoryOptions.length"
          id="portfolio_filter_category"
          v-model="filters.categoryId"
          :label="t('Category')"
          name="categoryId"
          :options="rootCategoryOptions"
          allow-clear
          @change="handleCategoryChange"
        />

        <BaseMultiSelect
          v-if="childCategoryOptions.length"
          v-model="filters.subCategoryIds"
          input-id="portfolio_filter_subcategories"
          :label="t('Categories')"
          :options="childCategoryOptions"
          option-label="label"
          option-value="id"
        />

        <BaseMultiSelect
          v-if="data.tags.length"
          v-model="filters.tags"
          input-id="portfolio_filter_tags"
          :label="t('Tags')"
          :options="data.tags"
          option-label="label"
          option-value="id"
        />

        <BaseCheckbox
          id="portfolio_filter_highlighted"
          v-model="filters.highlighted"
          name="highlighted"
          :label="t('Highlighted')"
        />

        <div class="flex flex-wrap items-center gap-2 md:col-span-2 xl:col-span-3">
          <BaseButton
            icon="search"
            :label="t('Search')"
            is-submit
            type="primary"
          />
          <BaseButton
            icon="clear-all"
            :label="t('Clear')"
            type="secondary"
            @click="clearFilters"
          />
        </div>
      </form>
    </BaseCard>

    <div
      v-if="isLoading"
      class="rounded-xl border border-gray-20 bg-white p-6 text-center text-sm text-gray-600 shadow-sm"
      role="status"
    >
      {{ t("Loading...") }}
    </div>

    <div
      v-else-if="errorMessage"
      class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"
      role="alert"
    >
      {{ errorMessage }}
    </div>

    <template v-else>
      <div
        v-if="data.selectedUser"
        class="flex items-center gap-3 rounded-xl border border-gray-20 bg-white p-4 shadow-sm"
      >
        <BaseUserAvatar
          :alt="data.selectedUser.fullName || t('User')"
          :image-url="data.selectedUser.imageUrl || ''"
          size="large"
        />
        <div>
          <div class="font-semibold text-gray-90">
            {{ data.selectedUser.fullName }}
          </div>
          <div class="text-sm text-gray-500">
            {{ data.totalItems }} {{ t("Portfolio items") }}
          </div>
        </div>
      </div>

      <div
        v-if="data.commentMatches.length"
        class="space-y-3"
      >
        <h2 class="text-lg font-semibold text-gray-90">
          {{ t("Comments") }}
        </h2>
        <BaseCard
          v-for="comment in data.commentMatches"
          :key="`match-${comment.id}`"
          plain
        >
          <template #title>
            <RouterLink
              :to="itemRoute(comment.itemId)"
              class="hover:underline"
            >
              {{ comment.itemTitle }}
            </RouterLink>
          </template>
          <p class="text-sm text-gray-700">
            {{ comment.excerpt }}
          </p>
          <div class="mt-2 text-xs text-gray-500">
            {{ comment.author?.fullName }} · {{ formatDate(comment.date) }}
          </div>
        </BaseCard>
      </div>

      <div
        v-if="data.items.length === 0"
        class="rounded-xl border border-gray-20 bg-white px-6 py-10 text-center shadow-sm"
      >
        <BaseIcon
          class="mb-3 text-gray-500"
          icon="information"
          size="big"
        />
        <p class="text-sm italic text-gray-500">
          {{ t("No data available") }}
        </p>
      </div>

      <div
        v-else
        class="space-y-4"
      >
        <BaseCard
          v-for="item in data.items"
          :id="`portfolio-item-${item.id}`"
          :key="item.id"
        >
          <template #title>
            <div class="flex min-w-0 items-start gap-2">
              <div class="min-w-0 flex-1">
                <RouterLink
                  :to="itemRoute(item.id)"
                  class="break-words text-lg font-semibold text-gray-90 hover:underline"
                >
                  {{ item.title }}
                </RouterLink>
                <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-gray-500">
                  <span>{{ item.author?.fullName }}</span>
                  <span>·</span>
                  <span>{{ formatDate(item.createdAt) }}</span>
                  <span
                    v-if="item.category"
                    class="rounded-full bg-gray-15 px-2 py-0.5"
                  >
                    {{ item.category.label }}
                  </span>
                  <BaseIcon
                    v-if="item.isHighlighted"
                    icon="trophy"
                    size="small"
                    :tooltip="t('Highlighted')"
                  />
                </div>
              </div>

              <div class="flex shrink-0 items-center gap-1">
                <BaseButton
                  icon="eye-on"
                  :label="t('View')"
                  only-icon
                  size="small"
                  type="secondary-text"
                  :route="itemRoute(item.id)"
                />
                <BaseButton
                  v-if="item.canEdit"
                  icon="pencil"
                  :label="t('Edit')"
                  only-icon
                  size="small"
                  type="secondary-text"
                  :route="formRoute(item.id)"
                />
                <BaseButton
                  v-if="item.canChangeVisibility && !data.advancedSharingEnabled"
                  :icon="item.visibility === 0 ? 'eye-off' : 'eye-on'"
                  :label="t('Visibility')"
                  only-icon
                  size="small"
                  type="secondary-text"
                  @click="runItemAction(item, 'toggle_visibility')"
                />
                <BaseButton
                  v-if="item.canHighlight"
                  icon="trophy"
                  :label="t('Highlighted')"
                  only-icon
                  size="small"
                  :type="item.isHighlighted ? 'primary' : 'secondary-text'"
                  @click="runItemAction(item, 'toggle_highlight')"
                />
                <BaseButton
                  v-if="item.canDelete"
                  icon="delete"
                  :label="t('Delete')"
                  only-icon
                  size="small"
                  type="danger-text"
                  @click="confirmDelete(item)"
                />
              </div>
            </div>
          </template>

          <div class="break-words text-gray-700">
            {{ item.excerpt }}
          </div>

          <div
            v-if="item.tags?.length"
            class="mt-3 flex flex-wrap gap-2"
          >
            <span
              v-for="tag in item.tags"
              :key="tag.id"
              class="inline-flex items-center gap-1 rounded-full bg-gray-15 px-2.5 py-1 text-xs text-gray-700"
            >
              <BaseIcon
                icon="tag-outline"
                size="small"
              />
              {{ tag.label }}
            </span>
          </div>

          <div
            v-if="item.attachments?.length"
            class="mt-3 flex flex-wrap gap-3"
          >
            <a
              v-for="attachment in item.attachments"
              :key="attachment.id"
              :href="attachment.downloadUrl"
              class="inline-flex items-center gap-1 text-sm text-primary hover:underline"
            >
              <BaseIcon
                icon="attachment"
                size="small"
              />
              {{ attachment.filename }}
            </a>
          </div>

          <div
            v-if="item.lastComments?.length"
            class="mt-4 space-y-2 border-t border-gray-20 pt-3"
          >
            <div
              v-for="comment in item.lastComments"
              :key="comment.id"
              class="rounded-lg bg-gray-10 p-3 text-sm"
            >
              <div class="font-medium text-gray-90">
                {{ comment.author?.fullName }}
              </div>
              <div class="mt-1 text-gray-700">
                {{ comment.excerpt }}
              </div>
            </div>
          </div>

          <div class="mt-4 flex flex-wrap gap-x-4 gap-y-2 border-t border-gray-20 pt-3 text-xs text-gray-500">
            <span class="inline-flex items-center gap-1.5">
              <BaseIcon
                icon="comment"
                size="small"
              />
              {{ item.commentsCount }} {{ t("Comments") }}
            </span>
            <span v-if="item.updatedAt">
              {{ t("Updated at") }}: {{ formatDate(item.updatedAt) }}
            </span>
            <span v-if="item.score !== null && item.score !== undefined">
              {{ t("Score") }}: {{ item.score }}
            </span>
          </div>
        </BaseCard>
      </div>
    </template>
  </section>
</template>

<script setup>
import { computed, reactive, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useToast } from "primevue/usetoast"
import { useRoute, useRouter } from "vue-router"
import { useConfirmation } from "../../composables/useConfirmation"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseCalendar from "../../components/basecomponents/BaseCalendar.vue"
import BaseCard from "../../components/basecomponents/BaseCard.vue"
import BaseCheckbox from "../../components/basecomponents/BaseCheckbox.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseMultiSelect from "../../components/basecomponents/BaseMultiSelect.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue"
import BaseUserAvatar from "../../components/basecomponents/BaseUserAvatar.vue"
import portfolioService from "../../services/portfolioService"

const { t } = useI18n()
const toast = useToast()
const route = useRoute()
const router = useRouter()
const { requireConfirmation } = useConfirmation()

const isLoading = ref(false)
const errorMessage = ref("")
const filtersVisible = ref(hasActiveQueryFilters(route.query))
const data = reactive(emptyData())
const filters = reactive(filtersFromQuery(route.query))

const orderOptions = computed(() => [
  { label: t("View in chronological order"), value: "chronological" },
  { label: t("View in alphabetical order"), value: "alphabetical" },
])
const authorOptions = computed(() =>
  data.authors.map((author) => ({ label: author.fullName || author.username, value: author.id })),
)
const rootCategoryOptions = computed(() =>
  data.categories.filter((category) => !category.parentId).map((category) => ({ ...category, value: category.id })),
)
const childCategoryOptions = computed(() =>
  data.categories.filter((category) => Number(category.parentId) === Number(filters.categoryId)),
)

function emptyData() {
  return {
    mode: route.meta.portfolioMode || "personal",
    selectedUser: null,
    items: [],
    commentMatches: [],
    categories: [],
    tags: [],
    authors: [],
    csrfToken: "",
    advancedSharingEnabled: false,
    totalItems: 0,
    canCreate: false,
    canViewDetails: false,
    canManageCategories: false,
    canManageTags: false,
  }
}

function firstQueryValue(value) {
  return Array.isArray(value) ? value[0] : value
}

function numberArray(value) {
  const values = Array.isArray(value) ? value : value ? String(value).split(",") : []

  return values.map(Number).filter((item) => Number.isInteger(item) && item > 0)
}

function parseFilterDate(value) {
  const raw = String(value || "")
  if (!/^\d{4}-\d{2}-\d{2}$/.test(raw)) {
    return null
  }

  const date = new Date(`${raw}T00:00:00`)

  return Number.isNaN(date.getTime()) ? null : date
}

function formatFilterDate(value) {
  if (!(value instanceof Date) || Number.isNaN(value.getTime())) {
    return undefined
  }

  const year = value.getFullYear()
  const month = String(value.getMonth() + 1).padStart(2, "0")
  const day = String(value.getDate()).padStart(2, "0")

  return `${year}-${month}-${day}`
}

function filtersFromQuery(query) {
  return {
    text: String(firstQueryValue(query.text) || ""),
    date: parseFilterDate(firstQueryValue(query.date)),
    order: ["chronological", "alphabetical"].includes(String(firstQueryValue(query.order)))
      ? String(firstQueryValue(query.order))
      : "chronological",
    user: Number(firstQueryValue(query.user) || 0) || null,
    categoryId: Number(firstQueryValue(query.categoryId) || 0) || null,
    subCategoryIds: numberArray(firstQueryValue(query.subCategoryIds)),
    tags: numberArray(query.tags),
    highlighted: ["1", "true", "yes", "on"].includes(String(firstQueryValue(query.highlighted)).toLowerCase()),
  }
}

function hasActiveQueryFilters(query) {
  return ["text", "date", "order", "user", "categoryId", "subCategoryIds", "tags", "highlighted"].some(
    (key) => Object.prototype.hasOwnProperty.call(query, key),
  )
}

function contextParams() {
  const params = {}
  const cid = Number(firstQueryValue(route.query.cid) || 0)
  const sid = Number(firstQueryValue(route.query.sid) || 0)

  if (cid > 0) params.cid = cid
  if (sid > 0) params.sid = sid

  return params
}

function listParams() {
  return {
    ...contextParams(),
    text: filters.text.trim(),
    date: formatFilterDate(filters.date),
    order: filters.order,
    user: filters.user || undefined,
    categoryId: filters.categoryId || undefined,
    subCategoryIds: filters.subCategoryIds.length ? filters.subCategoryIds.join(",") : undefined,
    tags: filters.tags.length ? filters.tags : undefined,
    highlighted: filters.highlighted ? 1 : undefined,
  }
}

const routePrefix = computed(() => (data.mode === "course" ? "PortfolioCourse" : "PortfolioPersonal"))
const detailsRoute = computed(() => ({
  name: `${routePrefix.value}Details`,
  params: data.mode === "course" ? { node: route.params.node } : {},
  query: { ...contextParams(), ...(filters.user ? { user: filters.user } : {}) },
}))

function formRoute(id = null) {
  return {
    name: `${routePrefix.value}${id ? "Edit" : "Add"}`,
    params: data.mode === "course" ? { node: route.params.node, ...(id ? { id } : {}) } : id ? { id } : {},
    query: contextParams(),
  }
}

function managementRoute(type) {
  return {
    name: `${routePrefix.value}${type}`,
    params: data.mode === "course" ? { node: route.params.node } : {},
    query: contextParams(),
  }
}

function actionError(error) {
  return error?.response?.data?.detail || error?.response?.data?.["hydra:description"] || t("An error occurred")
}

async function runItemAction(item, action) {
  try {
    await portfolioService.itemAction(item.id, { action, csrfToken: data.csrfToken }, contextParams())
    toast.add({ severity: "success", summary: t("Success"), detail: t("Updated"), life: 2500 })
    await loadPortfolio()
  } catch (error) {
    toast.add({ severity: "error", summary: t("Error"), detail: actionError(error), life: 5000 })
  }
}

function confirmDelete(item) {
  requireConfirmation({
    message: t("Please confirm your choice"),
    accept: async () => {
      try {
        await portfolioService.itemAction(item.id, { action: "delete", csrfToken: data.csrfToken }, contextParams())
        toast.add({ severity: "success", summary: t("Success"), detail: t("Deleted"), life: 3000 })
        await loadPortfolio()
      } catch (error) {
        toast.add({ severity: "error", summary: t("Error"), detail: actionError(error), life: 5000 })
      }
    },
  })
}

function itemRoute(id) {
  return {
    name: data.mode === "course" ? "PortfolioCourseItem" : "PortfolioPersonalItem",
    params: data.mode === "course" ? { node: route.params.node, id } : { id },
    query: {
      ...route.query,
      ...contextParams(),
      ...(filters.user ? { user: filters.user } : {}),
    },
  }
}

function applyFilters() {
  router.replace({
    name: data.mode === "course" ? "PortfolioCourseList" : "PortfolioPersonalList",
    params: data.mode === "course" ? { node: route.params.node } : {},
    query: listParams(),
  })
}

function clearFilters() {
  Object.assign(filters, {
    text: "",
    date: null,
    order: "chronological",
    user: null,
    categoryId: null,
    subCategoryIds: [],
    tags: [],
    highlighted: false,
  })
  applyFilters()
}

function handleCategoryChange() {
  filters.subCategoryIds = []
}

function formatDate(value) {
  const date = new Date(value)

  return Number.isNaN(date.getTime())
    ? String(value || "")
    : new Intl.DateTimeFormat(undefined, {
        dateStyle: "medium",
        timeStyle: "short",
      }).format(date)
}

async function loadPortfolio() {
  isLoading.value = true
  errorMessage.value = ""

  try {
    const response = await portfolioService.getList(listParams())
    Object.assign(data, emptyData(), response)
  } catch (error) {
    console.error("Error loading Portfolio", error)
    errorMessage.value =
      error?.response?.data?.detail || error?.response?.data?.["hydra:description"] || t("An error occurred")
  } finally {
    isLoading.value = false
  }
}

watch(
  () => route.fullPath,
  () => {
    Object.assign(filters, filtersFromQuery(route.query))
    loadPortfolio()
  },
  { immediate: true },
)
</script>
