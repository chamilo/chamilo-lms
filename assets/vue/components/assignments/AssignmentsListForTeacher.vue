<template>
  <DataTable
    ref="dt"
    v-model:selection="selected"
    :loading="loading"
    :rows="10"
    :rows-per-page-options="[10, 20, 50]"
    :sort-order="-1"
    :total-records="totalRecords"
    :value="assignments"
    current-page-report-template="Showing {first} to {last} of {totalRecords}"
    data-key="@id"
    lazy
    paginator
    paginator-template="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
    sort-field="sendDate"
    sort-mode="multiple"
    table-style="min-width: 50rem"
    @page="onPage"
    @sort="onSort"
  >
    <Column selection-mode="multiple" />
    <Column
      :header="t('Title')"
      :sortable="true"
      field="title"
    />
    <Column
      :header="t('Send date')"
      :sortable="true"
      class="text-center"
      field="sentDate"
      header-class="text-center"
    >
      <template #body="slotProps">
        {{ useAbbreviatedDatetime(slotProps.data.sentDate) }}
      </template>
    </Column>
    <Column
      :header="t('Deadline')"
      :sortable="true"
      field="assignment.expiresOn"
    >
      <template #body="slotProps">
        {{ useAbbreviatedDatetime(slotProps.data.assignment.expiresOn) }}
      </template>
    </Column>
    <Column :header="t('Number submitted')">
      <template #body="slotProps">
        <BaseTag
          :label="`${slotProps.data.uniqueStudentAttemptsTotal} / ${slotProps.data.studentSubscribedToWork}`"
          type="success"
        />
      </template>
    </Column>
    <Column
      :header="t('Actions')"
      body-class="space-x-2"
    >
      <template #body="slotProps">
        <BaseButton
          :icon="
            RESOURCE_LINK_PUBLISHED === slotProps.data.firstResourceLink.visibility
              ? 'eye-on'
              : RESOURCE_LINK_DRAFT === slotProps.data.firstResourceLink.visibility
              ? 'eye-off'
              : ''
          "
          :label="t('Visibility')"
          only-icon
          size="small"
          type="black"
          @click="onClickVisibility(slotProps.data)"
        />
        <BaseButton
          :label="t('Upload corrections')"
          icon="file-upload"
          only-icon
          size="small"
          type="black"
        />
        <BaseButton
          :disabled="0 === slotProps.data.uniqueStudentAttemptsTotal"
          :label="t('Save')"
          icon="download"
          only-icon
          size="small"
          type="black"
        />
        <BaseButton
          :label="t('Edit')"
          icon="edit"
          only-icon
          size="small"
          type="black"
        />
      </template>
    </Column>

    <template #footer>
      <BaseButton
        :disabled="0 === selected.length || loading"
        :label="t('Delete selected')"
        icon="delete"
        type="danger"
        @click="onClickMultipleDelete()"
      />
    </template>
  </DataTable>
</template>

<script setup>
import DataTable from "primevue/datatable"
import Column from "primevue/column"
import { onMounted, reactive, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import cStudentPublicationService from "../../services/cstudentpublication"
import { useCidReq } from "../../composables/cidReq"
import { useAbbreviatedDatetime } from "../../composables/formatDate"
import BaseTag from "../basecomponents/BaseTag.vue"
import BaseButton from "../basecomponents/BaseButton.vue"
import { RESOURCE_LINK_DRAFT, RESOURCE_LINK_PUBLISHED } from "../resource_links/visibility"
import { useNotification } from "../../composables/notification"
import { useConfirm } from "primevue/useconfirm"
import resourceLinkService from "../../services/resourcelink"

const { t } = useI18n()

const assignments = ref([])
const selected = ref([])
const loading = ref(false)
const totalRecords = ref(0)

const { cid, sid, gid } = useCidReq()

const notification = useNotification()

const confirm = useConfirm()

const dt = ref()
const loadParams = reactive({
  page: 1,
  itemsPerPage: 0,
})

const loadData = (params) => {
  loading.value = true

  cStudentPublicationService
    .findAll({
      params: { ...params, cid, sid, gid },
    })
    .then((response) => response.json())
    .then((json) => {
      assignments.value = json["hydra:member"]
      totalRecords.value = json["hydra:totalItems"]

      loading.value = false
    })
}

const onPage = (event) => {
  loadParams.page = event.page + 1
  loadParams.itemsPerPage = event.rows
}

const onSort = (event) => {
  Object.keys(loadParams)
    .filter((key) => key.indexOf("order[") >= 0)
    .forEach((key) => delete loadParams[key])

  event.multiSortMeta.forEach((sortItem) => {
    loadParams[`order[${sortItem.field}]`] = -1 === sortItem.order ? "desc" : "asc"
  })

  loadParams.itemsPerPage = event.rows
}

onMounted(() => {
  loadParams.itemsPerPage = dt.value.rows
})

watch(loadParams, (newLoadParams) => {
  loadData(newLoadParams)
})

function onClickMultipleDelete() {
  confirm.require({
    header: t("Confirmation"),
    message: t("Are you sure you want to delete the selected items?"),
    accept: async () => {
      loading.value = true

      try {
        for (const assignment of selected.value) {
          await cStudentPublicationService.del(assignment)
        }
      } catch (e) {
        notification.showErrorNotification(e)

        loading.value = false

        return
      } finally {
        selected.value = []
      }

      loadData(loadParams)

      notification.showSuccessNotification(t("Assignments deleted"))
    },
  })
}

async function onClickVisibility(assignment) {
  if (RESOURCE_LINK_PUBLISHED === assignment.firstResourceLink.visibility) {
    assignment.firstResourceLink.visibility = RESOURCE_LINK_DRAFT
  } else if (RESOURCE_LINK_DRAFT === assignment.firstResourceLink.visibility) {
    assignment.firstResourceLink.visibility = RESOURCE_LINK_PUBLISHED
  }

  try {
    await resourceLinkService.update(assignment.firstResourceLink)
  } catch (e) {
    notification.showErrorNotification(e)
  }
}
</script>
