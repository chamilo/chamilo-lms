<template>
  <section class="space-y-6">
    <div
      v-if="errorMessage"
      class="rounded-xl border border-danger/30 bg-danger/10 p-4 text-sm text-danger"
      role="alert"
    >
      {{ errorMessage }}
    </div>

    <section class="rounded-xl border border-gray-20 bg-white p-4 shadow-sm">
      <h1 class="text-xl font-semibold text-gray-90">{{ t("Search certificates") }}</h1>

      <form
        class="mt-4 grid gap-4 md:grid-cols-[1fr_1fr_auto] md:items-end"
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
          type="primary"
        />
      </form>
    </section>

    <div
      v-if="isLoading"
      class="rounded-xl border border-gray-20 bg-white p-6 text-center text-sm text-gray-600 shadow-sm"
      role="status"
    >
      {{ t("Loading...") }}
    </div>

    <template v-else>
      <div
        v-if="result?.message"
        class="rounded-xl border border-gray-20 bg-white p-4 text-sm text-gray-600 shadow-sm"
      >
        {{ t(result.message) }}
      </div>

      <section
        v-if="!selectedUser && users.length > 0"
        class="space-y-3"
      >
        <BaseTable
          :text-for-empty="t('No results found')"
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
      </section>

      <template v-if="selectedUser">
        <section class="rounded-xl border border-gray-20 bg-white p-4 shadow-sm">
          <div class="flex flex-wrap items-center gap-2">
            <BaseButton
              :label="t('Back')"
              :route="{ name: 'CertificateSearch' }"
              icon="back"
              only-icon
              size="small"
              type="primary-text"
            />
            <h2 class="text-lg font-semibold text-gray-90">{{ selectedUser.completeName }}</h2>
          </div>
        </section>

        <section class="space-y-3">
          <h2 class="text-lg font-semibold text-gray-90">{{ t("Courses") }}</h2>
          <BaseTable
            :text-for-empty="t('No results found')"
            :total-items="courseCertificates.length"
            :values="courseCertificates"
            data-key="id"
          >
            <Column :header="t('Course')">
              <template #body="{ data }">
                <div class="font-semibold text-gray-90">{{ data.course?.title || "-" }}</div>
                <div class="text-xs text-gray-500">{{ data.course?.code || "" }}</div>
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
            :text-for-empty="t('No results found')"
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
                <div class="text-xs text-gray-500">{{ data.course?.code || "" }}</div>
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

const users = computed(() => result.value?.users || [])
const selectedUser = computed(() => result.value?.selectedUser || null)
const courseCertificates = computed(() => result.value?.courseCertificates || [])
const sessionCertificates = computed(() => result.value?.sessionCertificates || [])

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
