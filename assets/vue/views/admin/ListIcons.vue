<template>
  <div class="p-4">
    <h1 class="mb-4 text-2xl font-semibold">
      {{ t("List icons") }}
    </h1>

    <div
      v-if="isLoading"
      class="text-center py-8"
    >
      {{ t("Loading") }}...
    </div>

    <div
      v-for="group in groups"
      v-else
      :key="group.class"
      class="mb-8"
    >
      <h2 class="mb-2 text-lg font-medium">
        {{ group.class }}
        <span class="text-sm font-normal text-gray-50">({{ group.fqcn }})</span>
      </h2>

      <DataTable
        :value="group.icons"
        striped-rows
      >
        <Column
          field="icon"
          :header="t('Icon')"
        >
          <template #body="{ data }">
            <i :class="`mdi mdi-${data.value} text-2xl`" />
          </template>
        </Column>
        <Column
          field="name"
          :header="t('Constant name')"
        >
          <template #body="{ data }">
            {{ group.class }}::{{ data.name }}
          </template>
        </Column>
        <Column
          field="value"
          :header="t('MDI icon name')"
        />
      </DataTable>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue"
import { useI18n } from "vue-i18n"
import DataTable from "primevue/datatable"
import baseService from "../../services/baseService"

const { t } = useI18n()

const groups = ref([])
const isLoading = ref(true)

onMounted(async () => {
  try {
    const response = await baseService.get("/admin/list-icons-data")
    groups.value = response.groups
  } finally {
    isLoading.value = false
  }
})
</script>
