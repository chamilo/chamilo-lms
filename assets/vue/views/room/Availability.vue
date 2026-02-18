<template>
  <div class="flex flex-col gap-4">
    <div class="flex flex-wrap gap-4 items-end">
      <div class="flex flex-col">
        <label class="mb-1 font-semibold">{{ t("Start") }}</label>
        <DatePicker
          v-model="startDate"
          show-time
          hour-format="24"
          date-format="yy-mm-dd"
        />
      </div>
      <div class="flex flex-col">
        <label class="mb-1 font-semibold">{{ t("End") }}</label>
        <DatePicker
          v-model="endDate"
          show-time
          hour-format="24"
          date-format="yy-mm-dd"
        />
      </div>
      <Button
        :label="t('Find available rooms')"
        icon="mdi mdi-magnify"
        :loading="isLoading"
        @click="search"
      />
    </div>

    <div v-if="searched && !isLoading">
      <h3 class="text-lg font-semibold mt-4 mb-2">{{ t("Available rooms") }}</h3>
      <div v-if="results.available.length === 0">
        <p class="text-gray-500">{{ t("No available rooms found for this time range") }}</p>
      </div>
      <BaseTable
        v-else
        :values="results.available"
      >
        <Column
          :header="t('Title')"
          field="title"
        >
          <template #body="slotProps">
            <router-link
              :to="{ name: 'RoomOccupation', params: { id: slotProps.data.id } }"
              class="text-primary hover:underline"
            >
              {{ slotProps.data.title }}
            </router-link>
          </template>
        </Column>
        <Column :header="t('Branch')">
          <template #body="slotProps">
            {{ slotProps.data.branch?.title || "-" }}
          </template>
        </Column>
      </BaseTable>

      <h3 class="text-lg font-semibold mt-6 mb-2">{{ t("Occupied rooms") }}</h3>
      <div v-if="results.occupied.length === 0">
        <p class="text-gray-500">-</p>
      </div>
      <BaseTable
        v-else
        :values="results.occupied"
      >
        <Column
          :header="t('Title')"
          field="title"
        />
        <Column :header="t('Branch')">
          <template #body="slotProps">
            {{ slotProps.data.branch?.title || "-" }}
          </template>
        </Column>
        <Column :header="t('Conflicts')">
          <template #body="slotProps">
            <ul class="list-disc pl-4">
              <li
                v-for="(conflict, idx) in slotProps.data.conflicts"
                :key="idx"
              >
                {{ conflict.courseTitle }} ({{ formatDateTime(conflict.start) }} - {{ formatDateTime(conflict.end) }})
              </li>
            </ul>
          </template>
        </Column>
      </BaseTable>
    </div>
  </div>
</template>

<script setup>
import { ref } from "vue"
import { useI18n } from "vue-i18n"
import DatePicker from "primevue/datepicker"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import roomService from "../../services/roomService"

const { t } = useI18n()

const startDate = ref(new Date())
const endDate = ref(new Date(Date.now() + 3600000))
const isLoading = ref(false)
const searched = ref(false)
const results = ref({ available: [], occupied: [] })

function formatDateTime(isoString) {
  const d = new Date(isoString)
  return d.toLocaleString()
}

async function search() {
  if (!startDate.value || !endDate.value) return

  isLoading.value = true
  try {
    results.value = await roomService.findAvailable(startDate.value, endDate.value)
    searched.value = true
  } catch (e) {
    console.error(e)
  } finally {
    isLoading.value = false
  }
}
</script>
