<template>
  <BaseTable
    :is-loading="loading"
    :total-items="totalRecords"
    :values="attendances"
    data-key="id"
    @page="onPageChange"
  >
    <!-- Name -->
    <Column
      field="title"
      header="Name"
      sortable
    >
      <template #body="slotProps">
        <RouterLink
          :to="{
            name: 'AttendanceSheetList',
            params: {
              node: getNodeId(slotProps.data.resourceNode),
              id: slotProps.data.id,
            },
            query: {
              cid: route.query.cid,
              sid: route.query.sid,
              gid: route.query.gid,
            },
          }"
          class="text-blue-500 underline"
        >
          {{ slotProps.data.title }}
        </RouterLink>
      </template>
    </Column>

    <!-- Description -->
    <Column
      field="description"
      header="Description"
      sortable
    >
      <template #body="slotProps">
        <div v-html="slotProps.data.description"></div>
      </template>
    </Column>

    <!-- # attended -->
    <Column
      field="doneCalendars"
      header="# attended"
      sortable
    >
      <template #body="slotProps">
        <div class="text-center">{{ slotProps.data.doneCalendars ?? 0 }}</div>
      </template>
    </Column>

    <!-- Detail -->
    <Column
      v-if="showActions"
      header="Detail"
    >
      <template #body="slotProps">
        <div class="flex gap-2 justify-center">
          <Button
            icon="pi pi-pencil"
            class="p-button-rounded p-button-sm p-button-info"
            @click="onEdit(slotProps.data)"
            tooltip="Edit"
          />
          <Button
            :icon="getVisibilityIcon(slotProps.data)"
            class="p-button-rounded p-button-sm"
            :class="getVisibilityClass(slotProps.data)"
            @click="onView(slotProps.data)"
            :tooltip="getVisibilityTooltip(slotProps.data)"
          />
          <Button
            icon="pi pi-trash"
            class="p-button-rounded p-button-sm p-button-danger"
            @click="onDelete(slotProps.data)"
            tooltip="Delete"
          />
        </div>
      </template>
    </Column>
  </BaseTable>
</template>
<script setup>
import { useRoute } from "vue-router"
import { computed } from "vue"
import { useSecurityStore } from "../../store/securityStore"
import BaseTable from "../basecomponents/BaseTable.vue"

const route = useRoute()
const securityStore = useSecurityStore()

const props = defineProps({
  attendances: { type: Array, required: true },
  loading: { type: Boolean, default: false },
  totalRecords: { type: Number, default: 0 },
  readonly: { type: Boolean, default: false },
})

const emit = defineEmits(["edit", "view", "delete", "pageChange"])

const isAdminOrTeacher = computed(() => securityStore.isAdmin || securityStore.isTeacher)
const showActions = computed(() => !props.readonly && isAdminOrTeacher.value)

const onEdit = (attendance) => emit("edit", attendance)
const onView = (attendance) => emit("view", attendance)
const onDelete = (attendance) => emit("delete", attendance)
const onPageChange = (event) => emit("pageChange", event)

const getVisibilityIcon = (attendance) => {
  const visibility = attendance.resourceLinkListFromEntity?.[0]?.visibility || 0
  return visibility === 2 ? "pi pi-eye" : "pi pi-eye-slash"
}

const getVisibilityClass = (attendance) => {
  const visibility = attendance.resourceLinkListFromEntity?.[0]?.visibility || 0
  return visibility === 2 ? "p-button-success" : "p-button-secondary opacity-50"
}

const getVisibilityTooltip = (attendance) => {
  const visibility = attendance.resourceLinkListFromEntity?.[0]?.visibility || 0
  return visibility === 2 ? "Visible" : "Hidden"
}

function getNodeId(resourceNode) {
  if (!resourceNode || !resourceNode["@id"]) return 0
  const parts = resourceNode["@id"].split("/")
  return parseInt(parts[parts.length - 1])
}
</script>
