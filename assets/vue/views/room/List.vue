<template>
  <div>
    <div
      v-if="isLoading"
      class="text-center py-8"
    >
      <i class="mdi mdi-loading mdi-spin mdi-24px" />
    </div>

    <div v-else-if="items.length === 0">
      <p class="text-gray-500 py-4">{{ t("No rooms have been created yet") }}</p>
    </div>

    <BaseTable
      v-else
      :values="items"
      :is-loading="isLoading"
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
      <Column
        :header="t('Courses')"
        field="courseCount"
      />
      <Column :exportable="false">
        <template #body="slotProps">
          <div class="text-right space-x-2">
            <Button
              v-tooltip.top="t('Occupation')"
              class="p-button-icon-only p-button-plain p-button-outlined p-button-sm"
              icon="mdi mdi-calendar-clock"
              @click="goToOccupation(slotProps.data)"
            />
            <Button
              class="p-button-icon-only p-button-plain p-button-outlined p-button-sm"
              icon="mdi mdi-pencil"
              @click="goToEdit(slotProps.data)"
            />
            <Button
              class="p-button-icon-only p-button-danger p-button-outlined p-button-sm"
              icon="mdi mdi-delete"
              @click="confirmDelete(slotProps.data)"
            />
          </div>
        </template>
      </Column>
    </BaseTable>

    <Dialog
      v-model:visible="deleteDialog"
      :modal="true"
      :style="{ width: '450px' }"
      :header="t('Confirm')"
    >
      <div class="confirmation-content">
        <i
          class="mdi mdi-alert-circle-outline mr-2"
          style="font-size: 2rem"
        />
        <span>{{ t("Are you sure you want to delete this item?") }}</span>
      </div>
      <template #footer>
        <Button
          :label="t('No')"
          class="p-button-outlined p-button-plain"
          icon="pi pi-times"
          @click="deleteDialog = false"
        />
        <Button
          :label="t('Yes')"
          class="p-button-secondary"
          icon="pi pi-check"
          @click="performDelete"
        />
      </template>
    </Dialog>
  </div>
</template>

<script setup>
import { inject, onMounted, ref } from "vue"
import { useRouter } from "vue-router"
import { useI18n } from "vue-i18n"
import { useToast } from "primevue/usetoast"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import roomService from "../../services/roomService"
import baseService from "../../services/baseService"

const { t } = useI18n()
const router = useRouter()
const toast = useToast()
const layoutMenuItems = inject("layoutMenuItems")

const items = ref([])
const isLoading = ref(true)
const deleteDialog = ref(false)
const itemToDelete = ref(null)

async function loadItems() {
  isLoading.value = true
  try {
    items.value = await roomService.fetchWithCounts()
  } catch (e) {
    console.error(e)
  } finally {
    isLoading.value = false
  }
}

function goToEdit(item) {
  router.push({
    name: "RoomUpdate",
    query: { id: `/api/rooms/${item.id}` },
  })
}

function goToOccupation(item) {
  router.push({
    name: "RoomOccupation",
    params: { id: item.id },
  })
}

function confirmDelete(item) {
  itemToDelete.value = item
  deleteDialog.value = true
}

async function performDelete() {
  deleteDialog.value = false
  try {
    await baseService.delete(`/api/rooms/${itemToDelete.value.id}`)
    toast.add({ severity: "success", detail: t("Deleted"), life: 3500 })
    await loadItems()
  } catch (e) {
    const message = e?.response?.status === 500
      ? t("Cannot delete room with courses attached")
      : e.message
    toast.add({ severity: "error", detail: message, life: 5000 })
  }
}

onMounted(() => {
  layoutMenuItems.value = [
    {
      label: t("New room"),
      url: router.resolve({ name: "RoomCreate" }).href,
    },
  ]
  loadItems()
})
</script>
