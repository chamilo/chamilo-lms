<script setup>
import { onMounted, ref } from "vue"
import { useI18n } from "vue-i18n"
import { useRouter } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseDialog from "../../components/basecomponents/BaseDialog.vue"
import BaseFileUpload from "../../components/basecomponents/BaseFileUpload.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import { useConfirmation } from "../../composables/useConfirmation"
import { useNotification } from "../../composables/notification"
import supportBotService from "../../services/supportBotService"

const { t } = useI18n()
const router = useRouter()
const notification = useNotification()
const { requireConfirmation } = useConfirmation()

const isLoading = ref(false)
const busyTreeId = ref(null)
const trees = ref([])
const importDialogVisible = ref(false)
const importFile = ref(null)
const isImporting = ref(false)

async function loadTrees() {
  isLoading.value = true
  try {
    const data = await supportBotService.getTrees()
    trees.value = Array.isArray(data.trees) ? data.trees : []
  } catch (error) {
    notification.showErrorNotification(error)
  } finally {
    isLoading.value = false
  }
}

function statusLabel(status) {
  if (status === "published") {
    return t("Published")
  }
  if (status === "archived") {
    return t("Archived")
  }

  return t("Draft")
}

function statusClass(status) {
  if (status === "published") {
    return "bg-success/10 text-success"
  }
  if (status === "archived") {
    return "bg-gray-20 text-gray-70"
  }

  return "bg-gray-10 text-gray-700"
}

function formatDate(value) {
  if (!value) {
    return ""
  }

  const date = new Date(value)
  if (Number.isNaN(date.getTime())) {
    return String(value)
  }

  return new Intl.DateTimeFormat(undefined, {
    year: "numeric",
    month: "short",
    day: "2-digit",
    hour: "2-digit",
    minute: "2-digit",
  }).format(date)
}

async function runAction(tree, action) {
  busyTreeId.value = tree.id
  try {
    if (action === "publish") {
      await supportBotService.publishTree(tree.id)
      notification.showSuccessNotification(t("The support bot tree has been published."))
    } else if (action === "archive") {
      await supportBotService.archiveTree(tree.id)
      notification.showSuccessNotification(t("The support bot tree has been archived."))
    } else if (action === "duplicate") {
      const result = await supportBotService.duplicateTree(tree.id)
      notification.showSuccessNotification(t("The support bot tree has been duplicated."))
      await router.push({ name: "SupportBotTreeEdit", params: { id: result.id } })
      return
    } else if (action === "delete") {
      await supportBotService.deleteTree(tree.id)
      notification.showSuccessNotification(t("The support bot tree has been deleted."))
    }

    await loadTrees()
  } catch (error) {
    notification.showErrorNotification(error)
  } finally {
    busyTreeId.value = null
  }
}

function confirmDelete(tree) {
  requireConfirmation({
    message: t("Are you sure you want to delete this support bot tree?"),
    accept: () => runAction(tree, "delete"),
  })
}

function openImportDialog() {
  importFile.value = null
  importDialogVisible.value = true
}

function onImportFileSelected(file) {
  importFile.value = file || null
}

async function importTree() {
  if (!importFile.value || isImporting.value) {
    return
  }

  isImporting.value = true
  try {
    const result = await supportBotService.importTree(importFile.value)
    importDialogVisible.value = false
    notification.showSuccessNotification(t("The support bot tree has been imported."))
    await loadTrees()
    await router.push({ name: "SupportBotTreeEdit", params: { id: result.id } })
  } catch (error) {
    notification.showErrorNotification(error)
  } finally {
    isImporting.value = false
  }
}

onMounted(loadTrees)
</script>

<template>
  <div class="flex flex-col gap-6">
    <SectionHeader :title="t('Support bot')">
      <div class="flex gap-2">
        <BaseButton
          :label="t('Import')"
          icon="import"
          only-icon
          type="success"
          @click="openImportDialog"
        />
        <BaseButton
          :label="t('Create support bot tree')"
          :route="{ name: 'SupportBotTreeCreate' }"
          icon="plus"
          only-icon
          type="success"
        />
      </div>
    </SectionHeader>

    <div class="rounded-xl border border-gray-20 bg-white p-4 text-sm text-gray-600 shadow-sm">
      {{ t("Build a fixed decision tree that guides users through support questions and can escalate to Tickets.") }}
    </div>

    <BaseTable
      :is-loading="isLoading"
      :lazy="false"
      :text-for-empty="t('No results found')"
      :total-items="trees.length"
      :values="trees"
      data-key="id"
    >
      <Column
        field="title"
        :header="t('Title')"
        sortable
      />
      <Column :header="t('Status')">
        <template #body="{ data }">
          <span
            :class="statusClass(data.status)"
            class="inline-flex rounded-full px-2 py-1 text-xs font-medium"
          >
            {{ statusLabel(data.status) }}
          </span>
        </template>
      </Column>
      <Column
        field="version"
        :header="t('Version')"
        sortable
      />
      <Column
        field="scopeLabel"
        :header="t('Portal scope')"
      />
      <Column :header="t('Updated at')">
        <template #body="{ data }">
          {{ formatDate(data.updatedAt) }}
        </template>
      </Column>
      <Column
        :header="t('Actions')"
        class="w-56"
      >
        <template #body="{ data }">
          <div class="flex flex-nowrap justify-end gap-1">
            <BaseButton
              v-if="data.canEdit"
              :label="t('Edit')"
              :route="{ name: 'SupportBotTreeEdit', params: { id: data.id } }"
              icon="pencil"
              only-icon
              size="small"
              type="secondary-text"
            />
            <BaseButton
              :disabled="busyTreeId === data.id"
              :label="t('Duplicate')"
              icon="copy"
              only-icon
              size="small"
              type="secondary-text"
              @click="runAction(data, 'duplicate')"
            />
            <BaseButton
              v-if="data.canPublish"
              :disabled="busyTreeId === data.id"
              :label="t('Publish')"
              icon="send"
              only-icon
              size="small"
              type="secondary-text"
              @click="runAction(data, 'publish')"
            />
            <BaseButton
              v-if="data.canArchive"
              :disabled="busyTreeId === data.id"
              :label="t('Archive')"
              icon="eye-off"
              only-icon
              size="small"
              type="secondary-text"
              @click="runAction(data, 'archive')"
            />
            <BaseButton
              v-if="Number(data.conversationCount || 0) > 0"
              :label="t('View conversations')"
              :route="{ name: 'SupportBotConversationList', params: { id: data.id } }"
              icon="preview"
              only-icon
              size="small"
              type="secondary-text"
            />
            <BaseButton
              :label="t('Export')"
              :to-url="supportBotService.exportTreeUrl(data.id)"
              icon="export"
              only-icon
              size="small"
              type="secondary-text"
            />
            <BaseButton
              v-if="data.canDelete"
              :disabled="busyTreeId === data.id"
              :label="t('Delete')"
              icon="delete"
              only-icon
              size="small"
              type="danger-text"
              @click="confirmDelete(data)"
            />
          </div>
        </template>
      </Column>
    </BaseTable>

    <BaseDialog
      v-model:is-visible="importDialogVisible"
      :title="t('Import support bot tree')"
      header-icon="import"
    >
      <div class="space-y-4">
        <p class="text-sm text-gray-600">
          {{ t("Import a JSON export or a CSV decision tree. The imported tree is always created as a new draft.") }}
        </p>
        <BaseFileUpload
          id="support_bot_import_file"
          accept=".json,.csv,application/json,text/csv"
          :field-label="t('File')"
          :label="t('Select a file')"
          name="file"
          @file-selected="onImportFileSelected"
        />
      </div>
      <template #footer>
        <BaseButton
          :disabled="!importFile"
          :is-loading="isImporting"
          :label="t('Import')"
          icon="import"
          type="success"
          @click="importTree"
        />
      </template>
    </BaseDialog>
  </div>
</template>
