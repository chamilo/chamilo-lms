<template>
  <BaseTable
    v-model:selected-items="selected"
    :is-loading="loading"
    :multi-sort-meta="sortFields"
    :rows="loadParams.itemsPerPage"
    :total-items="totalRecords"
    :values="assignments"
    data-key="@id"
    lazy
    removable-sort
    @page="onPage"
    @sort="onSort"
  >
    <Column selection-mode="multiple" />
    <Column
      :header="t('Title')"
      :sortable="true"
      field="title"
    >
      <template #body="slotProps">
        <RouterLink
          class="text-blue-600 hover:underline"
          :to="getAssignmentDetailLink(slotProps.data)"
        >
          {{ slotProps.data.title }}
        </RouterLink>
        <BaseTag
          v-if="slotProps.data.childFileCount > 0"
          :label="`${slotProps.data.childFileCount}`"
          type="success"
          class="ml-2"
        />
      </template>
    </Column>
    <Column
      :header="t('Sent date')"
      :sortable="true"
      field="sentDate"
    >
      <template #body="slotProps">
        {{ abbreviatedDatetime(slotProps.data.sentDate) }}
      </template>
    </Column>
    <Column
      :header="t('Deadline')"
      :sortable="true"
      field="assignment.expiresOn"
    >
      <template #body="slotProps">
    <span v-if="slotProps.data.assignment?.expiresOn">
      {{ formatStored(slotProps.data.assignment.expiresOn) }}
    </span>
        <span v-else class="text-gray-400 italic">No deadline</span>
      </template>
    </Column>

    <Column
      :header="t('End date')"
      :sortable="true"
      field="assignment.endsOn"
    >
      <template #body="slotProps">
    <span v-if="slotProps.data.assignment?.endsOn">
      {{ formatStored(slotProps.data.assignment.endsOn) }}
    </span>
        <span v-else class="text-gray-400 italic">—</span>
      </template>
    </Column>
    <Column :header="t('Number submitted')">
      <template #body="slotProps">
        <BaseTag
          :label="`${slotProps.data.uniqueStudentAttemptsTotal || 0} / ${slotProps.data.studentSubscribedToWork || 0}`"
          type="success"
        />
      </template>
    </Column>
    <Column
      :header="t('Actions')"
      body-class="space-x-2"
    >
      <template #body="slotProps">
        <div v-if="canEdit(slotProps.data)">
          <BaseButton
            :icon="
              RESOURCE_LINK_PUBLISHED === slotProps.data.firstResourceLink?.visibility
                ? 'eye-on'
                : RESOURCE_LINK_DRAFT === slotProps.data.firstResourceLink?.visibility
                  ? 'eye-off'
                  : ''
            "
            :label="t('Visibility')"
            only-icon
            size="normal"
            type="black"
            @click="onClickVisibility(slotProps.data)"
          />
          <BaseButton
            :label="t('Upload corrections package')"
            icon="zip-unpack"
            only-icon
            size="normal"
            type="success"
            :title="t('Each file name must match: YYYY-MM-DD_HH-MM_username_originalTitle.ext')"
            @click="() => uploadCorrections(slotProps.data)"
          />
          <BaseButton
            :disabled="0 === (slotProps.data.uniqueStudentAttemptsTotal || 0)"
            :label="t('Download assignments package')"
            icon="zip-pack"
            only-icon
            size="normal"
            type="primary"
            @click="() => downloadAssignments(slotProps.data)"
          />
          <BaseButton
            :label="t('Edit')"
            icon="edit"
            only-icon
            size="normal"
            type="black"
            @click="onClickEdit(slotProps.data)"
          />
        </div>
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
  </BaseTable>
</template>

<script setup>
import Column from "primevue/column"
import { computed, onMounted, reactive, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import cStudentPublicationService from "../../services/cstudentpublication"
import { useCidReq } from "../../composables/cidReq"
import { useFormatDate } from "../../composables/formatDate"
import BaseTag from "../basecomponents/BaseTag.vue"
import BaseButton from "../basecomponents/BaseButton.vue"
import BaseTable from "../basecomponents/BaseTable.vue"
import { RESOURCE_LINK_DRAFT, RESOURCE_LINK_PUBLISHED } from "../../constants/entity/resourcelink"
import { useNotification } from "../../composables/notification"
import { useConfirm } from "primevue/useconfirm"
import resourceLinkService from "../../services/resourcelink"
import { useRoute, useRouter } from "vue-router"
import { checkIsAllowedToEdit } from "../../composables/userPermissions"
import { useSecurityStore } from "../../store/securityStore"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()

const assignments = ref([])
const selected = ref([])
const loading = ref(false)
const totalRecords = ref(0)

const { cid, sid, gid } = useCidReq()

const notification = useNotification()

const confirm = useConfirm()
const securityStore = useSecurityStore()
const isCurrentTeacher = computed(() => securityStore.isCurrentTeacher)

const { abbreviatedDatetime } = useFormatDate()

const sortFields = ref([{ field: "sentDate", order: -1 }])
const loadParams = reactive({
  page: 1,
  itemsPerPage: 10,
})

const isAllowedToEdit = ref(false)

onMounted(async () => {
  isAllowedToEdit.value = await checkIsAllowedToEdit(true, true, true)
  loadData()
})

watch(loadParams, () => {
  loadData()
})

async function loadData() {
  loading.value = true

  try {
    const response = await cStudentPublicationService.findAll({
      params: {
        ...loadParams,
        cid,
        sid,
        gid,
        "publicationParent.iid": false,
      },
    })
    const json = await response.json()

    assignments.value = json["hydra:member"]
    totalRecords.value = json["hydra:totalItems"]
  } catch (error) {
    notification.showErrorNotification(error)
  } finally {
    loading.value = false
  }
}

const onPage = (event) => {
  loadParams.page = event.page + 1
}

const onSort = (event) => {
  Object.keys(loadParams)
    .filter((key) => key.indexOf("order[") >= 0)
    .forEach((key) => delete loadParams[key])

  event.multiSortMeta.forEach((sortItem) => {
    loadParams[`order[${sortItem.field}]`] = -1 === sortItem.order ? "desc" : "asc"
  })
}

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

      loadData()

      notification.showSuccessNotification(t("Assignments deleted"))
    },
  })
}

function getAssignmentDetailLink(assignment) {
  const assignmentId = parseInt(assignment["@id"].split("/").pop(), 10)
  const nodeUrl = assignment.resourceNode?.["@id"]
  const nodeId = nodeUrl ? parseInt(nodeUrl.split("/").pop(), 10) : 0

  return {
    name: "AssignmentDetail",
    params: {
      node: nodeId,
      id: assignmentId,
    },
    query: route.query,
  }
}

async function onClickVisibility(assignment) {
  if (RESOURCE_LINK_PUBLISHED === assignment.firstResourceLink?.visibility) {
    assignment.firstResourceLink.visibility = RESOURCE_LINK_DRAFT
  } else if (RESOURCE_LINK_DRAFT === assignment.firstResourceLink?.visibility) {
    assignment.firstResourceLink.visibility = RESOURCE_LINK_PUBLISHED
  }

  try {
    await resourceLinkService.update(assignment.firstResourceLink)
  } catch (e) {
    notification.showErrorNotification(e)
  }
}

function onClickEdit(assignment) {
  router.push({
    name: "AssignmentsUpdate",
    params: { id: assignment["@id"] },
    query: {
      ...route.query,
      from: "AssignmentsList",
    },
  })
}

async function downloadAssignments(assignment) {
  const assignmentId = parseInt(assignment["@id"].split("/").pop(), 10)
  try {
    const blob = await cStudentPublicationService.downloadAssignments(assignmentId)
    const url = window.URL.createObjectURL(new Blob([blob]))
    const link = document.createElement("a")
    link.href = url
    link.setAttribute("download", `assignments_${assignmentId}.zip`)
    document.body.appendChild(link)
    link.click()
    link.remove()
  } catch (error) {
    notification.showErrorNotification(t("Failed to download package"))
    console.error("Download error", error)
  }
}

async function uploadCorrections(assignment) {
  const assignmentId = parseInt(assignment["@id"].split("/").pop(), 10)

  const input = document.createElement("input")
  input.type = "file"
  input.accept = ".zip"

  input.addEventListener("change", async () => {
    const file = input.files[0]
    if (!file) return

    try {
      const result = await cStudentPublicationService.uploadCorrectionsPackage(assignmentId, file)
      const uploaded = result.uploaded ?? 0
      const skipped = result.skipped ?? 0
      notification.showSuccessNotification(t(`Corrections uploaded: ${uploaded}. Skipped: ${skipped}.`))
      await loadData()
    } catch (error) {
      console.error("Upload corrections error", error)
      notification.showErrorNotification(t("Failed to upload corrections"))
    }
  })

  input.click()
}

const getSessionId = (item) => {
  if (!item.firstResourceLink || !item.firstResourceLink.session) {
    return null
  }

  const sessionParts = item.firstResourceLink.session.split("/")
  return parseInt(sessionParts[sessionParts.length - 1])
}

const canEdit = (item) => {
  const sessionId = getSessionId(item)

  const isSessionDocument = sessionId && sessionId === sid
  const isBaseCourse = !sessionId

  return (isSessionDocument && isAllowedToEdit.value) || (isBaseCourse && !sid && isCurrentTeacher.value)
}

function formatStored(val) {
  if (!val) return "—"
  const s = String(val)
  const m = s.match(/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})/)
  if (m) {
    return `${m[3]}/${m[2]}/${m[1]} ${m[4]}:${m[5]}`
  }
  return abbreviatedDatetime(s)
}
</script>
