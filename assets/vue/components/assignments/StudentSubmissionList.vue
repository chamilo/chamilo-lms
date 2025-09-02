<template>
  <div>
    <BaseTable
      :is-loading="loading"
      :multi-sort-meta="sortFields"
      :rows="loadParams.itemsPerPage"
      :total-items="totalRecords"
      :values="submissions"
      data-key="@id"
      lazy
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
          <div class="flex justify-center items-center gap-2">
            <span
              v-if="data.correctionTitle"
              class="text-green-600"
            >
              <a
                v-if="data.correctionDownloadUrl"
                :href="data.correctionDownloadUrl"
                target="_blank"
                download
                class="text-green-50 hover:underline"
              >
                <i class="pi pi-check-circle"></i>
              </a>
              <i
                v-else
                class="pi pi-check-circle"
              ></i>
            </span>

            <span
              v-if="flags.allowText && data.comments && data.comments.length > 0"
              class="flex items-center gap-1 text-gray-600 text-sm cursor-pointer hover:underline"
              @click="openCommentDialog(data)"
            >
              <i class="pi pi-comment"></i> {{ data.comments.length }}
            </span>
            <span
              v-else
              class="text-gray-400"
              >—</span
            >
          </div>
        </template>
      </Column>

      <Column :header="t('Score')">
        <template #body="{ data }">
          <template v-if="data.qualification !== null && data.publicationParent?.qualification">
            <span
              :class="{
                'bg-success/10 text-success font-semibold text-sm px-2 py-1 rounded':
                  data.qualification > data.publicationParent.qualification / 2,
                'bg-danger/10 text-danger font-semibold text-sm px-2 py-1 rounded':
                  data.qualification <= data.publicationParent.qualification / 2,
              }"
            >
              {{ data.qualification.toFixed(1) }} / {{ data.publicationParent.qualification.toFixed(1) }}
            </span>
          </template>
          <template v-else>
            <span class="text-gray-50">
              {{ t("Not graded yet") }}
            </span>
          </template>
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

      <Column :header="t('Actions')">
        <template #body="{ data }">
          <div class="flex justify-center gap-2">
            <BaseButton
              v-if="flags.allowFile"
              icon="save"
              only-icon
              :label="t('Download')"
              @click="downloadSubmission(data)"
              type="primary"
            />
            <BaseButton
              v-if="flags.allowText"
              icon="reply-all"
              only-icon
              :label="t('Comment')"
              @click="correctAndRate(data)"
              type="success"
            />
            <span
              v-if="!flags.allowFile && !flags.allowText"
              class="text-gray-400"
              >—</span
            >
          </div>
        </template>
      </Column>
    </BaseTable>

    <CorrectAndRateModal
      v-model="showCorrectAndRateDialog"
      :item="correctingItem"
      :flags="flags"
      @commentSent="loadData"
      @update:modelValue="handleDialogVisibility"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, reactive, watch, nextTick } from "vue"
import { useI18n } from "vue-i18n"
import DataTable from "primevue/datatable"
import Column from "primevue/column"
import BaseButton from "../basecomponents/BaseButton.vue"
import CorrectAndRateModal from "./CorrectAndRateModal.vue"
import { useFormatDate } from "../../composables/formatDate"
import { useNotification } from "../../composables/notification"
import cStudentPublicationService from "../../services/cstudentpublication"
import BaseTable from "../basecomponents/BaseTable.vue"

const props = defineProps({
  assignmentId: { type: Number, required: true },
  flags: {
    type: Object,
    default: () => ({ allowText: true, allowFile: true }),
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

const showCorrectAndRateDialog = ref(false)
const correctingItem = ref(null)

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
  if (item?.downloadUrl) {
    const link = document.createElement("a")
    link.href = item.downloadUrl
    link.download = ""
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
  } else {
    notification.showErrorNotification(t("No download available"))
  }
}

function correctAndRate(item) {
  correctingItem.value = null
  nextTick(() => {
    correctingItem.value = item
    showCorrectAndRateDialog.value = true
  })
}

function openCommentDialog(item) {
  correctingItem.value = null
  nextTick(() => {
    correctingItem.value = item
    showCorrectAndRateDialog.value = true
  })
}

function handleDialogVisibility(newVal) {
  showCorrectAndRateDialog.value = newVal
  if (!newVal) {
    correctingItem.value = null
  }
}
</script>
