<template>
  <SectionHeader :title="t('Layout Templates')">
    <div class="flex gap-4">
      <BaseButton
        :label="t('Create Template')"
        icon="file-add"
        type="success"
        @click="goToCreate"
      />
      <BaseButton
        :label="t('Back to Layouts')"
        icon="arrow-left"
        type="secondary"
        @click="goToLayouts"
      />
    </div>
  </SectionHeader>

  <DataTable
    :value="templates"
    :rows="10"
    paginator
    striped-rows
    data-key="id"
  >
    <Column headerStyle="width: 80px">
      <template #body="{ data }">
        <BaseIcon
          icon="template"
          size="normal"
          class="text-primary"
        />
      </template>
    </Column>

    <Column
      field="id"
      :header="t('ID')"
    />

    <Column :header="t('Columns')">
      <template #body="{ data }">
        {{ getColumnsCount(data) }}
      </template>
    </Column>

    <Column :header="t('Blocks')">
      <template #body="{ data }">
        {{ getBlocksCount(data) }}
      </template>
    </Column>

    <Column
      :header="t('Actions')"
      :exportable="false"
      headerStyle="width: 250px"
    >
      <template #body="{ data }">
        <div class="flex gap-2">
          <BaseButton
            icon="eye-on"
            size="normal"
            type="primary"
            @click="showJsonDialog(data)"
            :title="t('View JSON')"
          />
          <BaseButton
            icon="edit"
            size="normal"
            type="secondary"
            @click="goToEdit(data)"
            :title="t('Edit')"
          />
          <BaseButton
            icon="delete"
            size="normal"
            type="danger"
            @click="confirmDelete(data)"
            :title="t('Delete')"
          />
        </div>
      </template>
    </Column>
  </DataTable>

  <!-- Dialog to show JSON -->
  <BaseDialogConfirmCancel
    v-model:is-visible="isJsonDialogVisible"
    :title="t('Layout JSON Details')"
    hide-cancel
    confirm-label="Copy JSON"
    @confirm-clicked="copyJsonToClipboard"
  >
    <pre class="text-xs whitespace-pre-wrap break-words max-h-[400px] overflow-auto">
{{ selectedJson }}
    </pre>
  </BaseDialogConfirmCancel>

  <BaseDialogConfirmCancel
    v-model:is-visible="isDeleteDialogVisible"
    :title="t('Confirm Deletion')"
    @confirm-clicked="deleteTemplate"
    @cancel-clicked="isDeleteDialogVisible = false"
  >
    <span>{{ t("Are you sure you want to delete this template?") }}</span>
  </BaseDialogConfirmCancel>
</template>
<script setup>
import { ref, onMounted } from "vue"
import { useRouter } from "vue-router"
import { useI18n } from "vue-i18n"

import SectionHeader from "../../components/layout/SectionHeader.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseDialogConfirmCancel from "../../components/basecomponents/BaseDialogConfirmCancel.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"

import DataTable from "primevue/datatable"
import Column from "primevue/column"

import pageService from "../../services/pageService.js"

const { t } = useI18n()
const router = useRouter()

const templates = ref([])
const isDeleteDialogVisible = ref(false)
const templateToDelete = ref(null)

const isJsonDialogVisible = ref(false)
const selectedJson = ref("")

onMounted(loadTemplates)

async function loadTemplates() {
  try {
    templates.value = await pageService.getPageLayoutTemplates()
  } catch (e) {
    console.error("Failed to load templates", e)
  }
}

function goToCreate() {
  router.push({ name: "PageLayoutTemplateCreate" })
}

function goToLayouts() {
  router.push({ name: "PageLayoutList" })
}

function goToEdit(template) {
  router.push({ name: "PageLayoutTemplateEdit", params: { id: template.id } })
}

function confirmDelete(template) {
  templateToDelete.value = template
  isDeleteDialogVisible.value = true
}

async function deleteTemplate() {
  try {
    await pageService.deletePageLayoutTemplate(
      `/api/page_layout_templates/${templateToDelete.value.id}`
    )
    templates.value = templates.value.filter((t) => t.id !== templateToDelete.value.id)
    isDeleteDialogVisible.value = false
  } catch (e) {
    console.error("Failed to delete template", e)
  }
}

/**
 * Helper to extract number of columns from the JSON layout.
 */
function getColumnsCount(template) {
  try {
    const parsed = JSON.parse(template.layout)
    return parsed?.page?.layout?.columns?.length || 0
  } catch (e) {
    return "-"
  }
}

/**
 * Helper to extract total blocks across all columns.
 */
function getBlocksCount(template) {
  try {
    const parsed = JSON.parse(template.layout)
    return parsed?.page?.layout?.columns?.reduce(
      (total, col) => total + (col.blocks?.length || 0),
      0
    ) || 0
  } catch (e) {
    return "-"
  }
}

function showJsonDialog(template) {
  selectedJson.value = JSON.stringify(JSON.parse(template.layout), null, 2)
  isJsonDialogVisible.value = true
}

function copyJsonToClipboard() {
  navigator.clipboard.writeText(selectedJson.value)
}
</script>
