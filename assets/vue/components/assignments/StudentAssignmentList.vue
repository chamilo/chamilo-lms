<template>
  <DataTable
    :value="assignments"
    :loading="loading"
    :paginator="true"
    :rows="10"
    data-key="id"
    striped-rows
  >
    <Column :header="t('Type')">
      <template #body>
        <BaseIcon
          icon="file-text"
          size="small"
        />
      </template>
    </Column>

    <Column :header="t('Title')">
      <template #body="slotProps">
        <a
          class="text-blue-600 hover:underline cursor-pointer flex items-center gap-1"
          @click="goToAssignmentDetail(slotProps.data)"
        >
          {{ slotProps.data.title }}
        </a>
      </template>
    </Column>

    <Column :header="t('Deadline')">
      <template #body="slotProps">
        {{ abbreviatedDatetime(slotProps.data.assignment?.expiresOn) || "-" }}
      </template>
    </Column>

    <Column :header="t('Feedback')">
      <template #body="slotProps">
        <span v-if="slotProps.data.commentsCount > 0"> {{ slotProps.data.commentsCount }} 💬 </span>
        <span v-else>-</span>
      </template>
    </Column>

    <Column :header="t('Last upload')">
      <template #body="slotProps">
        {{ abbreviatedDatetime(slotProps.data.sentDate) || "-" }}
      </template>
    </Column>
  </DataTable>
</template>

<script setup>
import DataTable from "primevue/datatable"
import Column from "primevue/column"
import { ref, onMounted } from "vue"
import { useI18n } from "vue-i18n"
import { useFormatDate } from "../../composables/formatDate"
import { useRouter } from "vue-router"
import { useCidReq } from "../../composables/cidReq"
import cStudentPublicationService from "../../services/cstudentpublication"
import BaseIcon from "../basecomponents/BaseIcon.vue"

const { t } = useI18n()
const { abbreviatedDatetime } = useFormatDate()
const router = useRouter()
const { cid, sid } = useCidReq()

const assignments = ref([])
const loading = ref(false)

onMounted(async () => {
  loading.value = true
  try {
    const response = await cStudentPublicationService.findStudentAssignments()
    assignments.value = response["hydra:member"].map((item) => ({
      ...item,
      id: item.iid,
    }))
  } catch (e) {
    console.error("Error loading student assignments", e)
  } finally {
    loading.value = false
  }
})

function goToAssignmentDetail(assignment) {
  if (!assignment?.id) return
  router.push({
    name: "AssignmentDetail",
    params: { id: assignment.id },
    query: { cid, sid },
  })
}
</script>
