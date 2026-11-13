<template>
  <section class="space-y-6">
    <SectionHeader
      :title="t('Search certificates')"
      icon="gradebook"
    />

    <Message
      v-if="errorMessage"
      :closable="false"
      icon="mdi mdi-alert-circle"
      severity="error"
    >
      {{ errorMessage }}
    </Message>

    <form
      class="grid gap-4 rounded-lg border border-gray-25 bg-white p-4 shadow-sm md:grid-cols-[1fr_1fr_auto] md:items-start"
      @submit.prevent="searchUsers"
    >
      <BaseInputText
        id="certificate-search-firstname"
        v-model="filters.firstname"
        :label="t('First name')"
        name="firstname"
      />
      <BaseInputText
        id="certificate-search-lastname"
        v-model="filters.lastname"
        :label="t('Last name')"
        name="lastname"
      />
      <BaseButton
        :disabled="isLoading"
        :label="t('Search')"
        icon="search"
        is-submit
        name="search"
        type="primary"
      />
    </form>

    <Message
      v-if="isTruncated"
      :closable="false"
      icon="mdi mdi-alert"
      severity="warn"
    >
      {{ t(result.message) }}
    </Message>

    <EmptyState
      v-if="emptyMessage"
      :summary="t(emptyMessage)"
      icon="search"
    />

    <BaseTable
      v-if="isNameSearch && (isLoading || users.length > 0)"
      :aria-busy="isLoading ? 'true' : undefined"
      :is-loading="isLoading"
      :style="isLoading ? { minHeight: `${USERS_TABLE_LOADING_HEIGHT}px` } : undefined"
      :text-for-empty="isLoading ? '' : t('No results found')"
      :total-items="users.length"
      :values="users"
      data-key="id"
    >
      <Column :header="t('First name')">
        <template #body="{ data }">{{ data.firstname }}</template>
      </Column>
      <Column :header="t('Last name')">
        <template #body="{ data }">{{ data.lastname }}</template>
      </Column>
      <Column :header="t('Actions')">
        <template #body="{ data }">
          <div class="flex justify-end">
            <BaseButton
              :label="t('View')"
              :route="{ name: 'CertificateSearch', query: { userId: data.id } }"
              icon="eye-on"
              only-icon
              size="small"
              type="primary-text"
            />
          </div>
        </template>
      </Column>
    </BaseTable>

    <template v-if="isDetail && (isLoading || selectedUser)">
      <div class="flex flex-wrap items-center gap-2 rounded-lg border border-gray-25 bg-white p-4 shadow-sm">
        <BaseButton
          :label="t('Back')"
          :route="{ name: 'CertificateSearch' }"
          icon="back"
          only-icon
          size="small"
          type="primary-text"
        />
        <h2
          v-if="selectedUser"
          class="text-lg font-semibold text-gray-90"
        >
          {{ selectedUser.completeName }}
        </h2>
        <Skeleton
          v-else
          aria-busy="true"
          height="1.75rem"
          width="12rem"
        />
      </div>

      <section class="space-y-3">
        <h2 class="text-lg font-semibold text-gray-90">{{ t("Courses") }}</h2>
        <BaseTable
          :aria-busy="isLoading ? 'true' : undefined"
          :is-loading="isLoading"
          :style="isLoading ? { minHeight: `${COURSES_TABLE_LOADING_HEIGHT}px` } : undefined"
          :text-for-empty="isLoading ? '' : t('No results found')"
          :total-items="courseCertificates.length"
          :values="courseCertificates"
          data-key="id"
        >
          <Column :header="t('Course')">
            <template #body="{ data }">
              <div class="font-semibold text-gray-90">{{ data.course?.title || "-" }}</div>
              <div class="text-caption text-gray-50">{{ data.course?.code || "" }}</div>
            </template>
          </Column>
          <Column :header="t('Score')">
            <template #body="{ data }">{{ formatNumber(data.score) }}%</template>
          </Column>
          <Column :header="t('Date')">
            <template #body="{ data }">{{ formatDate(data.issuedAt) }}</template>
          </Column>
          <Column :header="t('Actions')">
            <template #body="{ data }">
              <div class="flex justify-end gap-1">
                <BaseButton
                  v-if="data.certificate?.viewUrl"
                  :label="t('View')"
                  :to-url="data.certificate.viewUrl"
                  icon="eye-on"
                  only-icon
                  size="small"
                  type="primary-text"
                />
                <BaseButton
                  v-if="data.certificate?.downloadUrl"
                  :label="t('Download')"
                  :to-url="data.certificate.downloadUrl"
                  icon="download"
                  only-icon
                  size="small"
                  type="primary-text"
                />
              </div>
            </template>
          </Column>
        </BaseTable>
      </section>

      <section class="space-y-3">
        <h2 class="text-lg font-semibold text-gray-90">{{ t("Course sessions") }}</h2>
        <BaseTable
          :aria-busy="isLoading ? 'true' : undefined"
          :is-loading="isLoading"
          :style="isLoading ? { minHeight: `${SESSIONS_TABLE_LOADING_HEIGHT}px` } : undefined"
          :text-for-empty="isLoading ? '' : t('No results found')"
          :total-items="sessionCertificates.length"
          :values="sessionCertificates"
          data-key="id"
        >
          <Column :header="t('Session')">
            <template #body="{ data }">{{ data.session?.title || "-" }}</template>
          </Column>
          <Column :header="t('Course')">
            <template #body="{ data }">
              <div class="font-semibold text-gray-90">{{ data.course?.title || "-" }}</div>
              <div class="text-caption text-gray-50">{{ data.course?.code || "" }}</div>
            </template>
          </Column>
          <Column :header="t('Score')">
            <template #body="{ data }">{{ formatNumber(data.score) }}%</template>
          </Column>
          <Column :header="t('Date')">
            <template #body="{ data }">{{ formatDate(data.issuedAt) }}</template>
          </Column>
          <Column :header="t('Actions')">
            <template #body="{ data }">
              <div class="flex justify-end">
                <BaseButton
                  v-if="data.certificate?.viewUrl"
                  :label="t('View')"
                  :to-url="data.certificate.viewUrl"
                  icon="eye-on"
                  only-icon
                  size="small"
                  type="primary-text"
                />
              </div>
            </template>
          </Column>
        </BaseTable>
      </section>
    </template>
  </section>
</template>

<script setup>
import { computed, onMounted, ref, watch } from "vue"
import { useRoute, useRouter } from "vue-router"
import { useI18n } from "vue-i18n"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import EmptyState from "../../components/EmptyState.vue"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import Message from "primevue/message"
import Skeleton from "primevue/skeleton"
import gradebookService from "../../services/gradebookService"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()

const filters = ref({
  firstname: String(route.query.firstname || ""),
  lastname: String(route.query.lastname || ""),
})
const result = ref(null)
const isLoading = ref(false)
const errorMessage = ref("")

// Message of the API when the name search exceeds its row limit.
const TOO_MANY_RESULTS = "Too many results. Please refine your search."

// Heights of the loaded tables on the local portal at 1440 px, kept while a table loads.
// Three users; one course certificate; no session certificate.
const USERS_TABLE_LOADING_HEIGHT = 253
const COURSES_TABLE_LOADING_HEIGHT = 155
const SESSIONS_TABLE_LOADING_HEIGHT = 139

const isDetail = computed(() => Number(route.query.userId || 0) > 0)
const isNameSearch = computed(() => !isDetail.value && Boolean(route.query.firstname || route.query.lastname))
const isTruncated = computed(() => !isLoading.value && TOO_MANY_RESULTS === result.value?.message)
// "No user" and "No results found" replace the tables; the detail tables carry their own empty text.
const emptyMessage = computed(() =>
  isLoading.value || isTruncated.value || selectedUser.value ? "" : result.value?.message || "",
)

const users = computed(() => result.value?.users || [])
const selectedUser = computed(() => result.value?.selectedUser || null)
const courseCertificates = computed(() => result.value?.courseCertificates || [])
const sessionCertificates = computed(() => result.value?.sessionCertificates || [])

/**
 * Loads the search result that the route query asks for.
 */
async function load() {
  isLoading.value = true
  errorMessage.value = ""

  try {
    const params = {}
    const userId = Number(route.query.userId || 0)
    if (userId > 0) {
      params.userId = userId
    } else {
      const firstname = String(route.query.firstname || "").trim()
      const lastname = String(route.query.lastname || "").trim()
      if (firstname) {
        params.firstname = firstname
      }
      if (lastname) {
        params.lastname = lastname
      }
    }

    result.value = await gradebookService.searchCertificates(params)
  } catch (error) {
    result.value = null
    errorMessage.value = getErrorMessage(error)
  } finally {
    isLoading.value = false
  }
}

/**
 * Puts the name filters in the route query; the route watcher then loads the result.
 */
async function searchUsers() {
  const firstname = filters.value.firstname.trim()
  const lastname = filters.value.lastname.trim()
  const query = {}

  if (firstname) {
    query.firstname = firstname
  }
  if (lastname) {
    query.lastname = lastname
  }

  await router.push({ name: "CertificateSearch", query })
}

function formatNumber(value) {
  const number = Number(value)
  if (!Number.isFinite(number)) {
    return "0"
  }

  return new Intl.NumberFormat(undefined, { maximumFractionDigits: 2 }).format(number)
}

function formatDate(value) {
  if (!value) {
    return "-"
  }

  const date = new Date(value)
  if (Number.isNaN(date.getTime())) {
    return "-"
  }

  return new Intl.DateTimeFormat(undefined, { dateStyle: "medium" }).format(date)
}

function getErrorMessage(error) {
  return error?.response?.data?.detail || error?.response?.data?.message || error?.message || t("Error")
}

watch(
  () => route.query,
  () => {
    filters.value.firstname = String(route.query.firstname || "")
    filters.value.lastname = String(route.query.lastname || "")
    load()
  },
  { deep: true },
)

onMounted(load)
</script>
