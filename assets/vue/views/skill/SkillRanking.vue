<template>
  <div class="flex flex-col gap-8">
    <div class="flex items-center justify-between">
      <h2 class="text-2xl font-semibold text-gray-800">{{ t("Skills ranking") }}</h2>
    </div>

    <BaseTable
      :values="rows"
      :is-loading="isLoading"
      :text-for-empty="t('No data available')"
    >
      <Column :header="t('Photo')" field="avatarUrl">
        <template #body="{ data }">
          <img
            :src="data.avatarUrl"
            :alt="data.firstname + ' ' + data.lastname"
            class="w-8 h-8 rounded-full object-cover"
          />
        </template>
      </Column>
      <Column
        :header="t('First name')"
        field="firstname"
        sortable
      />
      <Column
        :header="t('Last name')"
        field="lastname"
        sortable
      />
      <Column
        :header="t('Skills acquired')"
        field="skillsAcquired"
        sortable
        class="text-center"
      />
      <Column
        :header="t('Currently learning')"
        field="currentlyLearning"
        sortable
        class="text-center"
      />
      <Column
        :header="t('Rank')"
        field="rank"
        sortable
        class="text-center"
      />
    </BaseTable>
  </div>
</template>

<script setup>
import { onMounted, ref } from "vue"
import { useI18n } from "vue-i18n"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import baseService from "../../services/baseService"

const { t } = useI18n()

const rows = ref([])
const isLoading = ref(true)

async function load() {
  isLoading.value = true
  try {
    rows.value = await baseService.get("/skill/ranking")
  } catch (e) {
    console.error(e)
  } finally {
    isLoading.value = false
  }
}

onMounted(load)
</script>
