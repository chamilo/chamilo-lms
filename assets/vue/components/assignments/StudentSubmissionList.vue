<template>
  <DataTable
    v-model:rows="loadParams.itemsPerPage"
    :total-records="totalRecords"
    :value="submissions"
    :loading="loading"
    lazy
    paginator
    data-key="@id"
    striped-rows
    :rows-per-page-options="[10, 20, 50]"
    :multi-sort-meta="sortFields"
    current-page-report-template="Showing {first} to {last} of {totalRecords}"
    paginator-template="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
    @page="onPage"
    @sort="onSort"
  >
    <Column :header="t('Type')">
      <template #body="{}">
        <div class="flex justify-center">
          <i class="pi pi-file" />
        </div>
      </template>
    </Column>

    <Column
      field="title"
      :header="t('Title')"
    />

    <Column :header="t('Feedback')">
      <template #body="{ data }">
        <div class="flex justify-center">
          <span
            v-if="data.feedback"
            class="text-green-600"
          >
            <i class="pi pi-check-circle"></i>
          </span>
        </div>
      </template>
    </Column>

    <Column
      field="sentDate"
      :header="t('Date')"
    >
      <template #body="{ data }">
        {{ abbreviatedDatetime(data.sentDate) }}
      </template>
    </Column>

    <Column :header="t('Detail')">
      <template #body="{ data }">
        <div class="flex justify-center gap-2">
          <BaseButton
            icon="download"
            size="normal"
            only-icon
            :label="t('Download')"
            @click="downloadSubmission(data)"
            type="primary"
          />
          <BaseButton
            icon="view-table"
            size="normal"
            only-icon
            :label="t('View correction')"
            @click="viewCorrection(data)"
            type=""
          />
        </div>
      </template>
    </Column>
  </DataTable>
</template>

<script setup>
import { ref, onMounted, reactive, watch } from "vue"
import { useI18n } from "vue-i18n"
import DataTable from "primevue/datatable"
import Column from "primevue/column"
import BaseButton from "../basecomponents/BaseButton.vue"
import { useFormatDate } from "../../composables/formatDate"
import { useNotification } from "../../composables/notification"
import cStudentPublicationService from "../../services/cstudentpublication"

const props = defineProps({
  assignmentId: {
    type: Number,
    required: true,
  },
})

const { t } = useI18n()
const { abbreviatedDatetime } = useFormatDate()
const notification = useNotification()

const loading = ref(false)
const submissions = ref([])
const totalRecords = ref(0)

const sortFields = ref([{ field: "sentDate", order: -1 }])
const loadParams = reactive({
  page: 1,
  itemsPerPage: 10,
})

watch(loadParams, loadData)
onMounted(loadData)

async function loadData() {
  loading.value = true
  try {
    const response = await cStudentPublicationService.getAssignmentDetail({
      assignmentId: props.assignmentId,
      page: loadParams.page,
      itemsPerPage: loadParams.itemsPerPage,
      order: { sentDate: "desc" },
    })
    submissions.value = response["hydra:member"]
    totalRecords.value = response["hydra:totalItems"]
  } catch (error) {
    notification.showErrorNotification(error)
  } finally {
    loading.value = false
  }
}

function onPage(event) {
  loadParams.page = event.page + 1
}

function onSort(event) {
  Object.keys(loadParams)
    .filter((key) => key.startsWith("order["))
    .forEach((key) => delete loadParams[key])

  event.multiSortMeta.forEach((sortItem) => {
    loadParams[`order[${sortItem.field}]`] = sortItem.order === 1 ? "asc" : "desc"
  })
}

function downloadSubmission(item) {
  if (!item.resourceNode) {
    notification.showErrorNotification(t("No file available for download"))
    return
  }

  const link = document.createElement("a")
  link.href = `/api${item.resourceNode}/download`
  link.download = item.title || "file"
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
}

function viewCorrection(item) {
  if (item.correctionFileName) {
    notification.showSuccessNotification(t("Viewing correction:") + " " + item.correctionFileName)
  } else {
    notification.showInfoNotification(t("No correction uploaded yet"))
  }
}
</script>
