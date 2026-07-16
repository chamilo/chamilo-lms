<template>
  <section class="space-y-6">
    <BaseToolbar class="mb-4 border-b border-gray-25 bg-white">
      <template #start>
        <BaseButton
          icon="back"
          :label="t('Back')"
          only-icon
          size="large"
          type="primary-text"
          class="!flex !h-12 !w-12 !items-center !justify-center !rounded-xl !p-0 [&_.p-button-icon]:!text-2xl"
          :route="listRoute"
        />
        <BaseButton
          v-if="canManageCurrent"
          icon="plus"
          :label="t('Add')"
          only-icon
          size="large"
          type="success"
          class="!flex !h-12 !w-12 !items-center !justify-center !rounded-xl !p-0 [&_.p-button-icon]:!text-2xl"
          @click="openCreate"
        />
      </template>
    </BaseToolbar>

    <div
      v-if="isLoading"
      class="rounded-xl border border-gray-20 bg-white p-6 text-center text-sm text-gray-600 shadow-sm"
    >
      {{ t("Loading...") }}
    </div>
    <div
      v-else-if="errorMessage"
      class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"
    >
      {{ errorMessage }}
    </div>

    <BaseCard v-else>
      <template #title>
        <div class="flex items-center gap-2">
          <BaseIcon :icon="isCategories ? 'folder-generic' : 'tag-outline'" />
          <span>{{ isCategories ? t("Categories") : t("Tags") }}</span>
        </div>
      </template>

      <div
        v-if="rows.length === 0"
        class="py-8 text-center text-sm italic text-gray-500"
      >
        {{ t("No data available") }}
      </div>

      <div
        v-else
        class="space-y-2"
      >
        <div
          v-for="row in rows"
          :key="row.id"
          class="flex flex-wrap items-start justify-between gap-3 rounded-lg border border-gray-20 p-4"
        >
          <div class="min-w-0 flex-1">
            <div class="font-semibold text-gray-90">
              <span v-if="isCategories && row.parentId">— </span>{{ row.title }}
            </div>
            <div
              v-if="isCategories && row.description"
              class="mt-1 text-sm text-gray-600"
              v-html="row.description"
            ></div>
            <div class="mt-1 text-xs text-gray-500">
              <template v-if="isCategories">
                {{ row.itemsCount }} {{ t("Portfolio items") }} ·
                {{ row.visible ? t("Visible") : t("Hidden") }}
              </template>
              <template v-else>
                {{ row.count || 0 }}
              </template>
            </div>
          </div>

          <div class="flex shrink-0 items-center gap-1">
            <BaseButton
              v-if="canManageCurrent"
              icon="pencil"
              :label="t('Edit')"
              only-icon
              size="small"
              type="secondary-text"
              @click="openEdit(row)"
            />
            <BaseButton
              v-if="isCategories && data.canManageCategories"
              :icon="row.visible ? 'eye-off' : 'eye-on'"
              :label="row.visible ? t('Hide') : t('Show')"
              only-icon
              size="small"
              type="secondary-text"
              @click="toggleCategory(row)"
            />
            <BaseButton
              v-if="canManageCurrent"
              icon="delete"
              :label="t('Delete')"
              only-icon
              size="small"
              type="danger-text"
              @click="confirmDelete(row)"
            />
          </div>
        </div>
      </div>
    </BaseCard>

    <BaseDialog
      v-model:is-visible="dialogVisible"
      :title="editingId ? t('Edit') : t('Add')"
      :show-close-button="true"
    >
      <form
        class="space-y-4"
        @submit.prevent="saveEntity"
      >
        <BaseInputText
          id="portfolio_management_title"
          v-model="editor.title"
          :label="isCategories ? t('Title') : t('Tag')"
          name="title"
          required
        />
        <BaseTinyEditor
          v-if="isCategories"
          v-model="editor.description"
          editor-id="portfolio_category_description"
          :editor-config="descriptionEditorConfig"
          :full-page="false"
          :title="t('Description')"
        />
        <BaseSelect
          v-if="isCategories"
          id="portfolio_category_parent"
          v-model="editor.parentId"
          :label="t('Parent category')"
          name="parentId"
          :options="parentOptions"
          allow-clear
        />
        <BaseCheckbox
          v-if="isCategories"
          id="portfolio_category_visible"
          v-model="editor.visible"
          name="visible"
          :label="t('Visible')"
        />
      </form>
      <template #footer>
        <BaseButton
          icon="save"
          :is-loading="isSaving"
          :label="t('Save')"
          type="success"
          @click="saveEntity"
        />
      </template>
    </BaseDialog>
  </section>
</template>

<script setup>
import { computed, reactive, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useToast } from "primevue/usetoast"
import { useRoute } from "vue-router"
import { useConfirmation } from "../../composables/useConfirmation"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseCard from "../../components/basecomponents/BaseCard.vue"
import BaseCheckbox from "../../components/basecomponents/BaseCheckbox.vue"
import BaseDialog from "../../components/basecomponents/BaseDialog.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseTinyEditor from "../../components/basecomponents/BaseTinyEditor.vue"
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue"
import portfolioService from "../../services/portfolioService"

const { t } = useI18n()
const toast = useToast()
const route = useRoute()
const { requireConfirmation } = useConfirmation()

const isLoading = ref(false)
const isSaving = ref(false)
const errorMessage = ref("")
const dialogVisible = ref(false)
const editingId = ref(null)
const data = reactive({ categories: [], tags: [], canManageCategories: false, canManageTags: false, csrfTokenValue: "" })
const editor = reactive({ title: "", description: "", parentId: null, visible: true })
const descriptionEditorConfig = { toolbar: "undo redo | bold italic underline | bullist numlist | link unlink", menubar: false, height: 180 }

const mode = computed(() => route.meta.portfolioMode || "personal")
const prefix = computed(() => (mode.value === "course" ? "PortfolioCourse" : "PortfolioPersonal"))
const isCategories = computed(() => route.meta.portfolioManagement === "categories")
const rows = computed(() => (isCategories.value ? data.categories : data.tags))
const canManageCurrent = computed(() => (isCategories.value ? data.canManageCategories : data.canManageTags))
const listRoute = computed(() => ({
  name: `${prefix.value}List`,
  params: mode.value === "course" ? { node: route.params.node } : {},
  query: contextParams(),
}))
const parentOptions = computed(() =>
  data.categories
    .filter((category) => !category.parentId && Number(category.id) !== Number(editingId.value))
    .map((category) => ({ label: category.title, value: category.id })),
)

function firstQueryValue(value) {
  return Array.isArray(value) ? value[0] : value
}

function contextParams() {
  const params = {}
  const cid = Number(firstQueryValue(route.query.cid) || 0)
  const sid = Number(firstQueryValue(route.query.sid) || 0)
  if (cid > 0) params.cid = cid
  if (sid > 0) params.sid = sid

  return params
}

function errorText(error) {
  return error?.response?.data?.detail || error?.response?.data?.["hydra:description"] || t("An error occurred")
}

function openCreate() {
  editingId.value = null
  Object.assign(editor, { title: "", description: "", parentId: null, visible: true })
  dialogVisible.value = true
}

function openEdit(row) {
  editingId.value = row.id
  Object.assign(editor, {
    title: row.title || "",
    description: row.description || "",
    parentId: row.parentId || null,
    visible: Boolean(row.visible ?? true),
  })
  dialogVisible.value = true
}

async function loadData() {
  isLoading.value = true
  errorMessage.value = ""
  try {
    Object.assign(data, await portfolioService.getManagement(contextParams()))
  } catch (error) {
    console.error("Error loading Portfolio management", error)
    errorMessage.value = errorText(error)
  } finally {
    isLoading.value = false
  }
}

async function saveEntity() {
  if (!editor.title.trim()) {
    toast.add({ severity: "warn", summary: t("Warning"), detail: t("Title is required"), life: 4000 })
    return
  }
  isSaving.value = true
  try {
    await portfolioService.managementAction(
      {
        action: isCategories.value ? "save_category" : "save_tag",
        entityId: editingId.value,
        parentId: editor.parentId,
        title: editor.title,
        description: editor.description,
        visible: editor.visible,
        csrfToken: data.csrfTokenValue,
      },
      contextParams(),
    )
    dialogVisible.value = false
    toast.add({ severity: "success", summary: t("Success"), detail: t("Saved"), life: 3000 })
    await loadData()
  } catch (error) {
    toast.add({ severity: "error", summary: t("Error"), detail: errorText(error), life: 5000 })
  } finally {
    isSaving.value = false
  }
}

async function toggleCategory(row) {
  try {
    await portfolioService.managementAction(
      { action: "toggle_category", entityId: row.id, csrfToken: data.csrfTokenValue },
      contextParams(),
    )
    await loadData()
  } catch (error) {
    toast.add({ severity: "error", summary: t("Error"), detail: errorText(error), life: 5000 })
  }
}

function confirmDelete(row) {
  requireConfirmation({
    message: t("Please confirm your choice"),
    accept: async () => {
      try {
        await portfolioService.managementAction(
          {
            action: isCategories.value ? "delete_category" : "delete_tag",
            entityId: row.id,
            csrfToken: data.csrfTokenValue,
          },
          contextParams(),
        )
        toast.add({ severity: "success", summary: t("Success"), detail: t("Deleted"), life: 3000 })
        await loadData()
      } catch (error) {
        toast.add({ severity: "error", summary: t("Error"), detail: errorText(error), life: 5000 })
      }
    },
  })
}

watch(
  () => route.fullPath,
  () => loadData(),
  { immediate: true },
)
</script>
