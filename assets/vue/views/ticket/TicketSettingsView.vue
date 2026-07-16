<template>
  <section class="space-y-4">
    <BaseToolbar>
      <template #start>
        <div class="flex flex-wrap items-center gap-2">
          <BaseButton
            icon="back"
            :label="t('Tickets')"
            only-icon
            size="normal"
            :route="{ name: 'TicketList', query: listQuery }"
            type="primary"
          />
          <BaseButton
            v-if="canAddItem"
            id="ticket-settings-add"
            icon="plus"
            :label="addLabel"
            only-icon
            size="normal"
            type="success"
            @click="openCreateDialog"
          />
        </div>
      </template>
    </BaseToolbar>

    <div
      v-if="errorMessage"
      class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700"
      role="alert"
    >
      {{ errorMessage }}
    </div>

    <div class="flex flex-wrap gap-2 rounded-xl border border-gray-20 bg-white p-4 shadow-sm">
      <BaseButton
        v-for="sectionOption in sectionOptions"
        :key="sectionOption.value"
        :id="`ticket-settings-section-${sectionOption.value}`"
        :label="sectionOption.label"
        :type="section === sectionOption.value ? 'primary' : 'plain'"
        @click="changeSection(sectionOption.value)"
      />
    </div>

    <div
      v-if="section === 'categories'"
      class="rounded-xl border border-gray-20 bg-white p-4 shadow-sm"
    >
      <BaseSelect
        id="ticket-settings-project"
        v-model="selectedProjectId"
        class="w-full lg:max-w-xl"
        :label="t('Project')"
        name="ticket_settings_project_id"
        option-label="title"
        option-value="id"
        :options="projects"
        @change="loadConfiguration"
      />
    </div>

    <BaseTable
      data-key="id"
      :is-loading="isLoading"
      :text-for-empty="t('No results found')"
      :values="currentItems"
    >
      <Column
        field="title"
        :header="t('Title')"
      />
      <Column :header="t('Description')">
        <template #body="{ data }">
          <div class="max-w-2xl break-words text-sm text-gray-700">
            {{ plainText(data.description) || "-" }}
          </div>
        </template>
      </Column>
      <Column
        v-if="section === 'projects'"
        field="categoryCount"
        :header="t('Categories')"
      />
      <Column
        v-if="section === 'projects' || section === 'categories' || section === 'statuses' || section === 'priorities'"
        field="ticketCount"
        :header="t('Tickets')"
      >
        <template #body="{ data }">
          {{ section === "projects" ? data.ticketCount : (data.ticketCount ?? data.totalTickets ?? 0) }}
        </template>
      </Column>
      <Column
        v-if="section === 'categories'"
        :header="t('Users')"
      >
        <template #body="{ data }">
          <span v-if="data.users?.length">{{ data.users.map((user) => user.label).join(", ") }}</span>
          <span v-else>-</span>
        </template>
      </Column>
      <Column
        v-if="section === 'statuses' || section === 'priorities'"
        field="code"
        :header="t('Code')"
      />
      <Column :header="t('Actions')">
        <template #body="{ data }">
          <div class="flex items-center gap-1">
            <BaseButton
              v-if="section === 'projects'"
              :id="`ticket-settings-project-tickets-${data.id}`"
              icon="ticket"
              :label="t('Tickets')"
              only-icon
              size="small"
              :route="{ name: 'TicketList', query: { project_id: String(data.id) } }"
              type="primary-text"
            />
            <BaseButton
              v-if="section === 'projects'"
              :id="`ticket-settings-project-categories-${data.id}`"
              icon="folder-open"
              :label="t('Categories')"
              only-icon
              size="small"
              type="primary-text"
              @click="openProjectCategories(data)"
            />
            <BaseButton
              v-if="section === 'categories' && data.editable"
              :id="`ticket-settings-category-users-${data.id}`"
              icon="assign-users"
              :label="t('Assign users')"
              only-icon
              size="small"
              type="primary-text"
              @click="openUsersDialog(data)"
            />
            <BaseButton
              v-if="data.editable && canEditItem"
              :id="`ticket-settings-edit-${section}-${data.id}`"
              icon="pencil"
              :label="t('Edit')"
              only-icon
              size="small"
              type="secondary-text"
              @click="openEditDialog(data)"
            />
            <BaseButton
              v-if="data.editable && canDelete(data)"
              :id="`ticket-settings-delete-${section}-${data.id}`"
              icon="delete"
              :label="t('Delete')"
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
      v-model:is-visible="isEditDialogVisible"
      :style="{ width: '720px' }"
      :title="editingItem ? t('Edit') : t('Add')"
    >
      <div class="space-y-4">
        <BaseInputText
          id="ticket-setting-title"
          v-model="form.title"
          :error-text="t('Required field')"
          :form-submitted="formSubmitted"
          :is-invalid="formSubmitted && !form.title.trim()"
          :label="t('Title')"
          name="title"
          required
        />
        <BaseTinyEditor
          v-model="form.description"
          editor-id="ticket-setting-description"
          :full-page="false"
          :title="t('Description')"
        />
      </div>
      <template #footer>
        <BaseButton
          icon="close"
          :label="t('Cancel')"
          type="plain"
          @click="isEditDialogVisible = false"
        />
        <BaseButton
          id="ticket-settings-save"
          icon="save"
          :is-loading="isSaving"
          :label="t('Save')"
          type="success"
          @click="saveItem"
        />
      </template>
    </BaseDialog>

    <BaseDialog
      v-model:is-visible="isUsersDialogVisible"
      :style="{ width: '720px' }"
      :title="t('Assign users')"
    >
      <BaseAutocomplete
        id="ticket-category-users"
        v-model="selectedUsers"
        :is-multiple="true"
        :label="t('Users')"
        option-label="label"
        :search="searchUsers"
      />
      <template #footer>
        <BaseButton
          icon="close"
          :label="t('Cancel')"
          type="plain"
          @click="isUsersDialogVisible = false"
        />
        <BaseButton
          id="ticket-settings-users-save"
          icon="save"
          :is-loading="isSaving"
          :label="t('Save')"
          type="success"
          @click="saveCategoryUsers"
        />
      </template>
    </BaseDialog>
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import BaseAutocomplete from "../../components/basecomponents/BaseAutocomplete.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseDialog from "../../components/basecomponents/BaseDialog.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import BaseTinyEditor from "../../components/basecomponents/BaseTinyEditor.vue"
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue"
import { useConfirmation } from "../../composables/useConfirmation"
import { useNotification } from "../../composables/notification"
import ticketService from "../../services/ticketService"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const { requireConfirmation } = useConfirmation()
const { showSuccessNotification, showErrorNotification } = useNotification()

const allowedSections = ["projects", "categories", "statuses", "priorities"]
const requestedSection = String(route.query.section || "projects")
const section = ref(allowedSections.includes(requestedSection) ? requestedSection : "projects")
const selectedProjectId = ref(Number(route.query.project_id || 0) || null)
const projects = ref([])
const categories = ref([])
const statuses = ref([])
const priorities = ref([])
const csrfToken = ref("")
const allowCategoryEdition = ref(false)
const isLoading = ref(false)
const isSaving = ref(false)
const errorMessage = ref("")
const isEditDialogVisible = ref(false)
const isUsersDialogVisible = ref(false)
const editingItem = ref(null)
const editingCategory = ref(null)
const selectedUsers = ref([])
const formSubmitted = ref(false)
const form = reactive({ title: "", description: "" })

const sectionOptions = computed(() => [
  { value: "projects", label: t("Projects") },
  { value: "categories", label: t("Categories") },
  { value: "statuses", label: t("Status") },
  { value: "priorities", label: t("Priorities") },
])
const currentItems = computed(() => {
  if (section.value === "categories") return categories.value
  if (section.value === "statuses") return statuses.value
  if (section.value === "priorities") return priorities.value
  return projects.value
})
const listQuery = computed(() => (selectedProjectId.value ? { project_id: String(selectedProjectId.value) } : {}))
const addLabel = computed(() => {
  if (section.value === "categories") return t("Add category")
  if (section.value === "statuses") return t("Add status")
  if (section.value === "priorities") return t("Add priority")
  return t("Add project")
})
const selectedProject = computed(() =>
  projects.value.find((project) => Number(project.id) === Number(selectedProjectId.value)),
)
const canAddItem = computed(() => {
  if (section.value !== "categories") return true
  return Boolean(selectedProject.value?.editable)
})
const canEditItem = computed(() => section.value !== "categories" || allowCategoryEdition.value)

onMounted(loadConfiguration)

async function loadConfiguration() {
  isLoading.value = true
  errorMessage.value = ""
  try {
    const response = await ticketService.getAdminConfiguration({ projectId: selectedProjectId.value || undefined })
    projects.value = response.projects || []
    categories.value = response.categories || []
    statuses.value = response.statuses || []
    priorities.value = response.priorities || []
    selectedProjectId.value = Number(response.projectId || 0) || null
    csrfToken.value = response.csrfToken || ""
    allowCategoryEdition.value = Boolean(response.allowCategoryEdition)
    syncRoute()
  } catch (error) {
    console.error("[TicketSettings] Failed to load configuration", error)
    errorMessage.value = getErrorMessage(error)
  } finally {
    isLoading.value = false
  }
}

function changeSection(value) {
  section.value = allowedSections.includes(value) ? value : "projects"
  syncRoute()
}

function syncRoute() {
  const query = { section: section.value }
  if (selectedProjectId.value) query.project_id = String(selectedProjectId.value)
  router.replace({ name: "TicketSettings", query })
}

function openProjectCategories(project) {
  selectedProjectId.value = Number(project.id)
  section.value = "categories"
  loadConfiguration()
}

function openCreateDialog() {
  if (section.value === "categories" && !selectedProjectId.value) return
  editingItem.value = null
  form.title = ""
  form.description = ""
  formSubmitted.value = false
  isEditDialogVisible.value = true
}

function openEditDialog(item) {
  editingItem.value = item
  form.title = item.title || ""
  form.description = item.description || ""
  formSubmitted.value = false
  isEditDialogVisible.value = true
}

async function saveItem() {
  formSubmitted.value = true
  if (!form.title.trim()) return
  isSaving.value = true
  try {
    const payload = { title: form.title.trim(), description: form.description, csrfToken: csrfToken.value }
    const response = editingItem.value
      ? await ticketService.updateAdminItem(section.value, editingItem.value.id, payload)
      : await ticketService.createAdminItem(section.value, selectedProjectId.value, payload)
    showSuccessNotification(response.message || t("Saved."))
    isEditDialogVisible.value = false
    await loadConfiguration()
  } catch (error) {
    console.error("[TicketSettings] Failed to save item", error)
    showErrorNotification(getErrorMessage(error))
  } finally {
    isSaving.value = false
  }
}

function canDelete(item) {
  if (section.value === "categories" && !allowCategoryEdition.value) return false
  return !item.protected
}

function confirmDelete(item) {
  requireConfirmation({
    message: t("Are you sure you want to delete this item?"),
    accept: () => deleteItem(item),
  })
}

async function deleteItem(item) {
  try {
    const response = await ticketService.deleteAdminItem(section.value, item.id, csrfToken.value)
    showSuccessNotification(response.message || t("Deleted"))
    await loadConfiguration()
  } catch (error) {
    console.error("[TicketSettings] Failed to delete item", error)
    showErrorNotification(getErrorMessage(error))
  }
}

function openUsersDialog(category) {
  editingCategory.value = category
  selectedUsers.value = Array.isArray(category.users) ? [...category.users] : []
  isUsersDialogVisible.value = true
}

async function searchUsers(query) {
  const response = await ticketService.searchUsers(query || "")
  return Array.isArray(response.items) ? response.items : []
}

async function saveCategoryUsers() {
  if (!editingCategory.value) return
  isSaving.value = true
  try {
    const response = await ticketService.updateCategoryUsers(
      editingCategory.value.id,
      selectedUsers.value.map((user) => Number(user.id)),
      csrfToken.value,
    )
    showSuccessNotification(response.message || t("Update successful"))
    isUsersDialogVisible.value = false
    await loadConfiguration()
  } catch (error) {
    console.error("[TicketSettings] Failed to save category users", error)
    showErrorNotification(getErrorMessage(error))
  } finally {
    isSaving.value = false
  }
}

function getErrorMessage(error) {
  return (
    error?.response?.data?.detail ||
    error?.response?.data?.error ||
    error?.response?.data?.["hydra:description"] ||
    t("An error occurred")
  )
}

function plainText(value) {
  const element = document.createElement("div")
  element.innerHTML = String(value || "")
  return element.textContent || element.innerText || ""
}
</script>
