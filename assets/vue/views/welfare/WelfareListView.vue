<template>
  <section class="space-y-5 pb-8">
    <header class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h1 class="text-2xl font-semibold">
          {{ investigationView ? t("Confidential investigations") : t("My confidential reports") }}
        </h1>
        <p class="mt-1 text-sm text-gray-600">
          {{
            t(
              "Only authorized investigators can review eligible reports. Reporter identities stay confidential by default.",
            )
          }}
        </p>
      </div>
      <BaseButton
        v-if="!investigationView"
        icon="plus"
        :label="t('New confidential report')"
        :route="{ name: 'WelfareCreate' }"
        type="primary"
      />
    </header>

    <p
      v-if="errorMessage"
      class="rounded-lg border border-red-200 p-3 text-red-700"
      role="alert"
    >
      {{ errorMessage }}
    </p>

    <section
      v-if="!investigationView"
      class="rounded-xl border border-gray-25 bg-white p-4"
    >
      <h2 class="mb-3 text-lg font-semibold">{{ t("My confidential reports") }}</h2>
      <BaseTable
        :values="myReports"
        :is-loading="loading"
        :text-for-empty="t('No results found')"
        data-key="reference"
      >
        <Column
          field="subject"
          :header="t('Subject')"
        />
        <Column
          field="targetName"
          :header="t('Reported user')"
        />
        <Column
          field="createdAt"
          :header="t('Created')"
        />
        <Column :header="t('Actions')">
          <template #body="{ data }">
            <BaseButton
              icon="preview"
              only-icon
              :label="t('View')"
              :route="{ name: 'WelfareDetail', params: { reference: data.reference } }"
              type="primary-text"
              size="small"
            />
          </template>
        </Column>
      </BaseTable>
    </section>

    <p
      v-if="canInvestigate && investigationView && notificationEnabled && investigations.length > 0"
      class="rounded-lg border border-yellow-200 bg-yellow-50 p-3 text-sm text-yellow-900"
      role="status"
    >
      {{ t("There are eligible confidential reports awaiting review.") }}
    </p>

    <section
      v-if="canInvestigate && investigationView"
      class="rounded-xl border border-gray-25 bg-white p-4"
    >
      <h2 class="mb-2 text-lg font-semibold">{{ t("Eligible investigations") }}</h2>
      <p class="mb-3 text-sm text-gray-600">
        {{
          t("A case appears here only after at least {min} different users report the same person.", {
            min: minReporters,
          })
        }}
      </p>
      <BaseTable
        :values="investigations"
        :text-for-empty="t('No results found')"
        data-key="targetId"
      >
        <Column
          field="targetName"
          :header="t('Reported user')"
        />
        <Column
          field="reporters"
          :header="t('Distinct reporters')"
        />
        <Column
          field="reports"
          :header="t('Reports')"
        />
        <Column :header="t('Actions')">
          <template #body="{ data }">
            <BaseButton
              icon="preview"
              only-icon
              :label="t('View')"
              type="primary-text"
              size="small"
              @click="loadTargetReports(data.targetId)"
            />
          </template>
        </Column>
      </BaseTable>

      <div
        v-if="selectedTargetId !== null"
        class="mt-5 border-t border-gray-25 pt-4"
      >
        <h3 class="mb-2 font-semibold">{{ t("Reports for selected user") }}</h3>
        <BaseTable
          :values="selectedReports"
          :text-for-empty="t('No results found')"
          data-key="reference"
        >
          <Column
            field="subject"
            :header="t('Subject')"
          />
          <Column
            field="createdAt"
            :header="t('Created')"
          />
          <Column :header="t('Actions')">
            <template #body="{ data }">
              <BaseButton
                icon="preview"
                only-icon
                :label="t('View')"
                :route="{ name: 'WelfareDetail', params: { reference: data.reference } }"
                type="primary-text"
                size="small"
              />
            </template>
          </Column>
        </BaseTable>
      </div>
    </section>
  </section>
</template>

<script setup>
import { onMounted, ref, computed, watch } from "vue"
import { useRoute } from "vue-router"
import { useI18n } from "vue-i18n"
import Column from "primevue/column"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import welfareService from "../../services/welfareService"

const { t } = useI18n()
const route = useRoute()
const investigationView = computed(() => route.query.view === "investigations")
const minReporters = ref(2)
const notificationEnabled = ref(false)
const myReports = ref([])
const investigations = ref([])
const selectedReports = ref([])
const selectedTargetId = ref(null)
const canInvestigate = ref(false)
const loading = ref(false)
const errorMessage = ref("")

async function load() {
  loading.value = true
  errorMessage.value = ""
  try {
    const result = await welfareService.list()
    myReports.value = result.mine || []
    canInvestigate.value = result.canInvestigate === true
    minReporters.value = result.minimumDistinctReporters || 2
    notificationEnabled.value = result.notificationEnabled === true
    if (canInvestigate.value && investigationView.value) {
      const cases = await welfareService.investigations()
      investigations.value = cases.items || []
    }
  } catch {
    errorMessage.value = t("Confidential reporting is currently unavailable.")
  } finally {
    loading.value = false
  }
}

async function loadTargetReports(id) {
  errorMessage.value = ""
  selectedTargetId.value = id
  selectedReports.value = []
  try {
    selectedReports.value = (await welfareService.targetReports(id)).items || []
  } catch {
    errorMessage.value = t("The investigation could not be loaded.")
  }
}

onMounted(load)
watch(() => route.query.view, load)
</script>
