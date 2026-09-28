<template>
  <div>
    <SectionHeader :title="t('Manage categories')" />

    <BaseToolbar>
      <BaseButton
        :label="t('Back')"
        icon="back"
        only-icon
        type="black"
        @click="goBack"
      />
    </BaseToolbar>

    <div class="mb-5 grid gap-3 md:grid-cols-[1fr_auto] md:items-start">
      <BaseInputText
        id="glossary-category-title"
        v-model="categoryTitle"
        :label="t('Category')"
        name="category_title"
        @keyup.enter="saveCategory"
      />
      <BaseButton
        :label="editingCategory ? t('Update category') : t('Add a category')"
        :icon="editingCategory ? 'edit' : 'plus'"
        type="success"
        @click="saveCategory"
      />
    </div>

    <BaseTable
      :values="categories"
      :total-items="categories.length"
      data-key="iid"
      :text-for-empty="t('No available options')"
    >
      <Column
        :header="t('Category')"
        field="title"
      />
      <Column :header="t('Actions')">
        <template #body="{ data }">
          <div class="flex items-center gap-2">
            <BaseButton
              v-if="canEditCategory(data)"
              :label="t('Edit')"
              icon="edit"
              only-icon
              size="small"
              type="tertiary-text"
              @click="startEdit(data)"
            />
            <BaseButton
              v-if="canEditCategory(data)"
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

    <BaseDialogDelete
      v-model:is-visible="deleteDialogVisible"
      :item-to-delete="categoryToDelete?.title || ''"
      @confirm-clicked="deleteCategory"
      @cancel-clicked="deleteDialogVisible = false"
    />
  </div>
</template>

<script setup>
import Column from "primevue/column"
import { onMounted, ref } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseDialogDelete from "../../components/basecomponents/BaseDialogDelete.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import { useNotification } from "../../composables/notification"
import glossaryService from "../../services/glossaryService"

const route = useRoute()
const router = useRouter()
const { t } = useI18n()
const notifications = useNotification()

const categories = ref([])
const categoryTitle = ref("")
const editingCategory = ref(null)
const categoryToDelete = ref(null)
const deleteDialogVisible = ref(false)

const contextParams = () => ({
  cid: Number(route.query.cid || 0),
  sid: Number(route.query.sid || 0),
})

onMounted(fetchCategories)

async function fetchCategories() {
  try {
    categories.value = await glossaryService.getCategories(contextParams())
  } catch (error) {
    console.error("[GlossaryCategories] Failed to fetch categories:", error)
    categories.value = []
  }
}

function canEditCategory(category) {
  const sid = Number(route.query.sid || 0)
  const categorySid = Number(category.sessionId || 0)
  return sid > 0 ? categorySid === sid : categorySid === 0
}

function startEdit(category) {
  if (!canEditCategory(category)) return
  editingCategory.value = category
  categoryTitle.value = category.title
}

function resetForm() {
  editingCategory.value = null
  categoryTitle.value = ""
}

async function saveCategory() {
  const title = categoryTitle.value.trim()
  if (!title) return

  try {
    if (editingCategory.value) {
      await glossaryService.updateCategory(editingCategory.value.iid, { title }, contextParams())
    } else {
      await glossaryService.createCategory({ title }, contextParams())
    }

    notifications.showSuccessNotification(t("Category saved"))
    resetForm()
    await fetchCategories()
  } catch (error) {
    console.error("[GlossaryCategories] Failed to save category:", error)
    notifications.showErrorNotification(t("Could not save category"))
  }
}

function confirmDelete(category) {
  if (!canEditCategory(category)) return
  categoryToDelete.value = category
  deleteDialogVisible.value = true
}

async function deleteCategory() {
  if (!categoryToDelete.value) return

  try {
    await glossaryService.deleteCategory(categoryToDelete.value.iid, contextParams())
    notifications.showSuccessNotification(t("Category deleted"))
    deleteDialogVisible.value = false
    categoryToDelete.value = null
    resetForm()
    await fetchCategories()
  } catch (error) {
    console.error("[GlossaryCategories] Failed to delete category:", error)
    notifications.showErrorNotification(t("Could not delete category"))
  }
}

function goBack() {
  router.push({
    name: "GlossaryList",
    query: route.query,
  })
}
</script>
